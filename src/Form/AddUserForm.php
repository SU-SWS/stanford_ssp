<?php

namespace Drupal\stanford_ssp\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Password\PasswordGeneratorInterface;
use Drupal\externalauth\AuthmapInterface;
use Drupal\stanford_ssp\Service\StanfordSSPWorkgroupApiInterface;
use Drupal\user\Entity\User;
use Drupal\Component\Utility\EmailValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AddUser to add a SSP user without them logging in first.
 *
 * @package Drupal\stanford_ssp\Form
 */
class AddUserForm extends FormBase {

  /**
   * External authmap service.
   *
   * @var \Drupal\externalauth\AuthmapInterface
   */
  protected $authmap;

  /**
   * Email Validator service.
   *
   * @var \Drupal\Component\Utility\EmailValidatorInterface
   */
  protected $emailValidator;

  /**
   * Core password generator service.
   *
   * @var \Drupal\Core\Password\PasswordGeneratorInterface
   */
  protected $passwordGenerator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('externalauth.authmap'),
      $container->get('email.validator'),
      $container->get('password_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(AuthmapInterface $authmap, EmailValidatorInterface $email_validator, PasswordGeneratorInterface $password_generator) {
    $this->authmap = $authmap;
    $this->emailValidator = $email_validator;
    $this->passwordGenerator = $password_generator;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'stanford_ssp_add_user_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['sunetid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SUNetID'),
      '#description' => $this->t('Enter the SUNetID of the user you wish to add.'),
      '#required' => TRUE,
      '#element_validate' => [[static::class, 'validateSunetId']],
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#description' => $this->t("If you wish to specify the user's preferred name (instead of sunetid), enter it here."),
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email Address'),
      '#description' => $this->t('If you wish to specify an alternate email address (instead of sunetid@stanford.edu), enter it here.'),
    ];

    if ($this->config('simplesamlphp_auth.settings')
      ->get('allow.set_drupal_pwd')) {
      $form['pass'] = [
        '#type' => 'password_confirm',
        '#size' => 25,
      ];
    }

    $form['roles'] = [
      '#type' => 'select',
      '#title' => $this->t('Roles'),
      '#description' => $this->t('Add roles to the new user account.'),
      '#options' => self::getAvailableRoles(),
      '#multiple' => TRUE,
    ];

    $form['notify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify user of new account'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add SUNetID User'),
    ];
    return $form;
  }

  /**
   * SunetID input validation.
   *
   * @param array $element
   *   Field element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Submitted form state.
   * @param array $complete_form
   *   Complete form.
   */
  public static function validateSunetId(array &$element, FormStateInterface $form_state, array &$complete_form){
    $value = $element['#value'];
    if (!preg_match('/^[a-z0-9]*$/', $value)) {
      $form_state->setError($element, t('Invalid SunetID'));
      return;
    }

    /** @var \Drupal\stanford_ssp\Service\StanfordSSPWorkgroupApiInterface $workgroup_api */
    $workgroup_api = \Drupal::service('stanford_ssp.workgroup_api');
    if ($workgroup_api->connectionSuccessful() && !$workgroup_api->isSunetValid($value)) {
      $form_state->setError($element, t('Invalid SunetID'));
    }
  }

  /**
   * Get available roles, limited if the role_delegation module is enabled.
   *
   * @return array
   *   Keyed array of role id and role label.
   */
  protected static function getAvailableRoles() {
    if (\Drupal::moduleHandler()->moduleExists('role_delegation')) {
      /** @var \Drupal\role_delegation\DelegatableRolesInterface $role_delegation */
      $role_delegation = \Drupal::service('delegatable_roles');
      return $role_delegation->getAssignableRoles(\Drupal::currentUser());
    }
    return user_role_names(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $sunet = strtolower(trim(Html::escape(($form_state->getValue('sunetid')))));
    $form_state->setValue('sunetid', $sunet);

    if ($this->authmap->getUid($sunet, 'simplesamlphp_auth')) {
      $form_state->setError($form['sunetid'], $this->t('Could not create user. Authname %name already exists. Has the user already been created with a different username but the same SUNetID?', ['%name' => $sunet]));
      return;
    }

    // If no name is specified, use the default name (sunetid + @stanford.edu).
    $name = trim(Html::escape($form_state->getValue('name'))) ?: $sunet;
    $form_state->setValue('name', $name);

    // Check that there is no user with the same name.
    if (user_load_by_name($name)) {
      $form_state->setError($form['name'], $this->t('Could not create user. Username %name already exists.', ['%name' => $name]));
    }

    // If no email was specified, we use the default ([sunetid]@stanford.edu).
    $default_email = $sunet . '@stanford.edu';
    $email = trim($form_state->getValue('email')) ?: $default_email;
    $form_state->setValue('email', $email);

    if (!$this->emailValidator->isValid($email)) {
      $form_state->setError($form['email'], $this->t('The e-mail address %email is not valid.', ['%email' => $email]));
    }

    // Check that there is no user with the same email
    // Drupal will let us create the user with a duplicate email, but
    // the user will run into issues when making changes to their profile.
    if (user_load_by_mail($email)) {
      $form_state->setError($form['email'], $this->t('Could not create user. Email %email already in use.', ['%email' => $email]));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $new_user = User::create([
      'name' => $form_state->getValue('name'),
      'pass' => $form_state->getValue('pass', $this->passwordGenerator->generate()),
      'mail' => $form_state->getValue('email'),
      'roles' => array_values($form_state->getValue('roles')),
      'status' => 1,
    ]);
    $success = $new_user->save();

    $this->authmap->save($new_user, 'simplesamlphp_auth', $form_state->getValue('sunetid'));
    $this->messenger()
      ->addStatus($this->t('Successfully created SSO account for %user', ['%user' => $new_user->getAccountName()]));
    $this->logger('stanford_ssp')
      ->info('Created User %name', ['%name' => $new_user->getAccountName()]);

    // Was the notify checkbox checked?
    if ($form_state->getValue('notify') && $success) {
      _user_mail_notify('register_admin_created', $new_user);
      $this->messenger()->addStatus($this->t('Email sent to user'));
    }
  }

}
