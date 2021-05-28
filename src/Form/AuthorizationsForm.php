<?php

namespace Drupal\stanford_ssp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class AuthorizationsForm to configure workgroup/user restrictions..
 *
 * @package Drupal\stanford_ssp\Form
 */
class AuthorizationsForm extends ConfigFormBase {

  const ALLOW_ALL = 'all';

  const RESTRICT = 'restrict';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'stanford_ssp_authorization';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['stanford_ssp.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('stanford_ssp.settings');
    $form['restriction'] = [
      '#type' => 'radios',
      '#title' => $this->t('SSO Authorization Restrictions'),
      '#options' => [
        self::ALLOW_ALL => $this->t('Allow any valid SSO (SUNet) user.'),
        self::RESTRICT => $this->t('Restrict access to specific users and groups.'),
      ],
      '#required' => TRUE,
      '#default_value' => $config->get('restriction') ?: self::ALLOW_ALL,
    ];
    $states = [
      'visible' => [
        'input[name="restriction"]' => ['value' => self::RESTRICT],
      ],
    ];

    $url = Url::fromUri('https://uit.stanford.edu/service/saml/arp/edupa');
    $affiliation_link = Link::fromTextAndUrl('SAML Affiliation Information', $url)
      ->toString();
    $form['allowed_affiliations'] = [
      '#type' => 'select',
      '#title' => $this->t('Affiliation'),
      '#description' => $this->t("Restrict to the user's affiliation to Stanford. View what these affiliations entail at @link.", ['@link' => $affiliation_link]),
      '#multiple' => TRUE,
      '#states' => $states,
      '#options' => [
        'affiliate' => $this->t('Affiliate'),
        'staff' => $this->t('Staff'),
        'student' => $this->t('Students'),
        'faculty' => $this->t('Faculty'),
        'member' => $this->t('Member'),
      ],
      '#default_value' => $config->get('allowed.affiliations') ?? [],
    ];

    $form['allowed_groups'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed Workgroups'),
      '#description' => $this->t('A comma-separated list of Workgroups that should be allowed to login with simpleSAMLphp. If left blank, any workgroup can login.'),
      '#default_value' => implode(',', $config->get('allowed.groups') ?? []),
      '#states' => $states,
    ];

    $form['allowed_users'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed Users'),
      '#description' => $this->t('A comma-separated list of SUNet IDs that should be allowed to login with simpleSAMLphp. If left blank, any valid SUNet ID user can login.'),
      '#default_value' => implode(',', $config->get('allowed.users') ?? []),
      '#states' => $states,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if ($form_state->getValue('restriction') == self::RESTRICT) {
      if (empty($form_state->getValue('allowed_groups')) && empty($form_state->getValue('allowed_users')) && empty($form_state->getValue('allowed_affiliations'))) {
        $form_state->setError($form['restriction'], $this->t('If restricting to users or groups, you must provided the allowed information'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('stanford_ssp.settings')
      ->set('restriction', $form_state->getValue('restriction'))
      ->set('allowed.affiliations', array_values($form_state->getValue('allowed_affiliations')))
      ->set('allowed.groups', explode(',', $form_state->getValue('allowed_groups')))
      ->set('allowed.users', explode(',', $form_state->getValue('allowed_users')))
      ->save();
  }

}
