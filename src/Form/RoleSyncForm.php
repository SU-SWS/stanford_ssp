<?php

namespace Drupal\stanford_ssp\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\key\Entity\Key;
use Drupal\simplesamlphp_auth\Form\SyncingSettingsForm;
use Drupal\stanford_ssp\Service\StanfordSSPDrupalAuth;
use Drupal\stanford_ssp\Service\StanfordSSPWorkgroupApiInterface;
use Drupal\user\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RoleSyncForm overrides simplesamlphp_auth form for easier UI.
 *
 * @package Drupal\stanford_ssp\Form
 */
class RoleSyncForm extends SyncingSettingsForm {

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Workgroup API service.
   *
   * @var \Drupal\stanford_ssp\Service\StanfordSSPWorkgroupApiInterface
   */
  protected $workgroupApi;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('stanford_ssp.workgroup_api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, StanfordSSPWorkgroupApiInterface $workgroup_api) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
    $this->workgroupApi = $workgroup_api;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    $names = parent::getEditableConfigNames();
    $names[] = 'stanford_ssp.settings';
    return $names;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $stanford_config = $this->config('stanford_ssp.settings');

    if (!$form_state->get('mappings')) {
      $mappings = explode('|', $form['user_info']['role_population']['#default_value']);
      $form_state->set('mappings', array_filter(array_combine($mappings, $mappings)));
    }

    $form['user_info']['role_population'] = [
      '#type' => 'table',
      '#header' => $this->getRoleHeaders(),
      '#attributes' => ['id' => 'role-mapping-table'],
    ];

    $form['user_info']['role_population']['add']['#tree'] = TRUE;
    $form['user_info']['role_population']['add']['role_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Add Role'),
      '#options' => user_role_names(TRUE),
    ];

    $form['user_info']['role_population']['add']['workgroup'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Workgroup'),
    ];
    $form['user_info']['role_population']['add']['add_mapping'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Mapping'),
      '#limit_validation_errors' => [],
      '#submit' => ['::addMappingCallback'],
      '#ajax' => [
        'callback' => '::addMapping',
        'wrapper' => 'role-mapping-table',
      ],
    ];

    foreach ($form_state->get('mappings', []) as $role_mapping) {
      $form['user_info']['role_population'][$role_mapping] = $this->buildRoleRow($role_mapping);
    }

    $form['user_info']['role_eval_every_time']['#type'] = 'radios';
    $form['user_info']['role_eval_every_time']['#options'] = [
      0 => $this->t('Do not adjust roles. Allow local administration of roles only.'),
      StanfordSSPDrupalAuth::ROLE_REEVALUATE => $this->t('Re-evaluate roles on every log in. This will grant and remove roles.'),
      StanfordSSPDrupalAuth::ROLE_ADDITIVE => $this->t('Grant new roles only. Will only add roles based on role assignments.'),
    ];

    $form['user_info']['use_workgroup_api'] = [
      '#type' => 'radios',
      '#title' => $this->t('Source to validate role mapping groups against.'),
      '#default_value' => $stanford_config->get('use_workgroup_api') ?: 0,
      '#options' => [
        $this->t('SAML Attribute'),
        $this->t('Workgroup API'),
      ],
      '#attributes' => ['disabled' => TRUE],
    ];

    if (!$this->moduleHandler->moduleExists('key')) {
      $form['user_info']['use_workgroup_api']['#attributes']['disabled'] = TRUE;
      $form['user_info']['workgroup_api']['#markup'] = $this->t('To use workgroup API as a mapping source, please install the <a href="https://www.drupal.org/project/key">Key module</a>.');
      return $form;
    }

    $keys = Key::loadMultiple();
    // Change the key object into just the label for use in the select elements.
    foreach ($keys as &$key) {
      $key = $key->label();
      unset($form['user_info']['use_workgroup_api']['#attributes']['disabled']);
    }

    $form['user_info']['workgroup_api_cert'] = [
      '#type' => 'select',
      '#title' => $this->t('Key to Workgroup API SSL Certificate.'),
      '#description' => $this->t('Choose an available key. If the desired key is not listed, <a href=":link">create a new key</a>.<br>For more information on how to get a certificate please see: https://uit.stanford.edu/service/registry/certificates.', [
        ':link' => Url::fromRoute('entity.key.add_form')->toString(),
      ]),
      '#options' => $keys,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $stanford_config->get('workgroup_api_cert'),
      '#states' => ['visible' => ['input[name="use_workgroup_api"]' => ['value' => 1]]],
    ];

    $form['user_info']['workgroup_api_key'] = [
      '#type' => 'select',
      '#title' => $this->t('Key to Workgroup API SSL Key.'),
      '#description' => $this->t('Choose an available key. If the desired key is not listed, <a href=":link">create a new key</a>.<br>For more information on how to get a key please see: https://uit.stanford.edu/service/registry/certificates.', [
        ':link' => Url::fromRoute('entity.key.add_form')->toString(),
      ]),
      '#options' => $keys,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $stanford_config->get('workgroup_api_key'),
      '#states' => ['visible' => ['input[name="use_workgroup_api"]' => ['value' => 1]]],
    ];
    return $form;
  }

  /**
   * Get the role mapping table headers.
   *
   * @return array
   *   Array of table header labels.
   */
  protected function getRoleHeaders() {
    return [
      $this->t('Role'),
      $this->t('Workgroup'),
      $this->t('Actions'),
    ];
  }

  /**
   * Build the table row for the role mapping string.
   *
   * @param $role_mapping_string
   *   Formatted role mapping string.
   *
   * @return array
   *   Table render array.
   */
  protected function buildRoleRow($role_mapping_string) {
    list($role_id, $comparison) = explode(':', $role_mapping_string, 2);
    list(, , $value) = explode(',', $comparison);
    if ($role = Role::load($role_id)) {
      return [
        ['#markup' => $role->label()],
        ['#markup' => $value],
        [
          '#type' => 'submit',
          '#value' => $this->t('Remove Mapping'),
          '#name' => $role_mapping_string,
          '#submit' => ['::removeMappingCallback'],
          '#mapping' => $role_mapping_string,
          '#ajax' => [
            'callback' => '::addMapping',
            'wrapper' => 'role-mapping-table',
          ],
        ],
      ];
    }
    return [
      ['#markup' => $this->t('Broken @id', ['@id' => $role_id])],
      ['#markup' => $value],
      [
        '#type' => 'submit',
        '#value' => $this->t('Remove Mapping'),
        '#name' => $role_mapping_string,
        '#submit' => ['::removeMappingCallback'],
        '#mapping' => $role_mapping_string,
        '#ajax' => [
          'callback' => '::addMapping',
          'wrapper' => 'role-mapping-table',
        ],
      ],
    ];
  }

  /**
   * Add/remove a new workgroup mapping callback.
   *
   * @param array $form
   *   Compolete Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return array
   *   Form element.
   */
  public function addMapping(array &$form, FormStateInterface $form_state) {
    return $form['user_info']['role_population'];
  }

  /**
   * Add a new workgroup mapping submit callback.
   *
   * @param array $form
   *   Compolete Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  public function addMappingCallback(array $form, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();
    $role_id = $user_input['role_population']['add']['role_id'];
    $workgroup = trim(Html::escape($user_input['role_population']['add']['workgroup']));
    if ($role_id && $workgroup) {
      $mapping_string = "$role_id:eduPersonEntitlement,=,$workgroup";
      $form_state->set(['mappings', $mapping_string], $mapping_string);
    }
    $form_state->setRebuild();
  }

  /**
   * Remove a workgroup mapping submit callback.
   *
   * @param array $form
   *   Compolete Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  public function removeMappingCallback(array $form, FormStateInterface $form_state) {
    $mappings = $form_state->get('mappings', []);
    unset($mappings[$form_state->getTriggeringElement()['#mapping']]);
    $form_state->set('mappings', $mappings);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('role_population', implode('|', $form_state->get('mappings')));
    parent::validateForm($form, $form_state);

    // If using SAML attributes, unset api settings.
    if (!$form_state->getValue('use_workgroup_api')) {
      $form_state->setValue('workgroup_api_cert', '');
      $form_state->setValue('workgroup_api_key', '');
      return;
    }

    $cert_key_id = $form_state->getValue('workgroup_api_cert');
    $key_id = $form_state->getValue('workgroup_api_key');

    // Both cert and Key have to be selected.
    if (!$cert_key_id || !$key_id) {
      $form_state->setError($form['user_info']['workgroup_api_cert'], $this->t('Cert and Key are required if using workgroup API.'));
    }

    $cert = Key::load($cert_key_id);
    if (!is_file($cert->getKeyValue())) {
      $form_state->setError($form['user_info']['workgroup_api_cert'], $this->t('Cert must be a file path.'));
    }

    $key = Key::load($key_id);
    if (!is_file($key->getKeyValue())) {
      $form_state->setError($form['user_info']['workgroup_api_key'], $this->t('Cert must be a file path.'));
    }

    if (!$this->workgroupApi->connectionSuccessful($cert->getKeyValue(), $key->getKeyValue())) {
      $form_state->setError($form['user_info']['workgroup_api_cert'], $this->t('Cert information invalid. See database logs for more information.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('stanford_ssp.settings')
      ->set('use_workgroup_api', $form_state->getValue('use_workgroup_api'))
      ->set('workgroup_api_cert', $form_state->getValue('workgroup_api_cert'))
      ->set('workgroup_api_key', $form_state->getValue('workgroup_api_key'))
      ->save();
  }

}
