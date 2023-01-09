<?php

namespace Drupal\stanford_ssp\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simplesamlphp_auth\Form\SyncingSettingsForm;
use Drupal\stanford_ssp\Service\StanfordSSPDrupalAuth;
use Drupal\stanford_ssp\Service\StanfordSSPWorkgroupApiInterface;
use Drupal\user\RoleInterface;
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
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('stanford_ssp.workgroup_api'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, StanfordSSPWorkgroupApiInterface $workgroup_api, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
    $this->workgroupApi = $workgroup_api;
    $this->entityTypeManager = $entity_type_manager;
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

    // If the form is newly built, the form state storage will be null. If the
    // form is being rebuilt from an ajax, the storage will be some type of
    // array.
    if (is_null($form_state->get('mappings'))) {
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
    unset($form['user_info']['role_population']['add']['role_id']['#options'][RoleInterface::AUTHENTICATED_ID]);
    unset($form['user_info']['role_population']['add']['role_id']['#options'][RoleInterface::ANONYMOUS_ID]);

    $form['user_info']['role_population']['add']['attribute'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Attribute Key'),
      '#description' => $this->t('The value in the SAML data to use as the key for matching. eg: eduPersonEnttitlement'),
      '#attributes' => ['placeholder' => $this->getDefaultSamlAttribute()],
    ];

    $form['user_info']['role_population']['add']['workgroup'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Attribute Value'),
      '#description' => $this->t('The value in the SAML data to use as the value for matching. eg: uit:sws'),
      '#element_validate' => [[$this, 'validateWorkgroup']],
    ];
    $form['user_info']['role_population']['add']['add_mapping'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Mapping'),
      '#submit' => ['::addMappingCallback'],
      '#ajax' => [
        'callback' => '::addMapping',
        'wrapper' => 'role-mapping-table',
      ],
    ];

    foreach ($form_state->get('mappings') as $role_mapping) {
      $form['user_info']['role_population'][$role_mapping] = $this->buildRoleRow($role_mapping);
    }

    $form['user_info']['role_eval_every_time']['#type'] = 'radios';
    $form['user_info']['role_eval_every_time']['#options'] = [
      0 => $this->t('Do not adjust roles. Allow local administration of roles only.'),
      StanfordSSPDrupalAuth::ROLE_REEVALUATE => $this->t('Re-evaluate roles on every log in. This will grant and remove roles.'),
      StanfordSSPDrupalAuth::ROLE_ADDITIVE => $this->t('Grant new roles only. Will only add roles based on role assignments.'),
    ];

    $this->buildWorkgroupApiForm($form, $form_state);
    return $form;
  }

  /**
   * Build the workgroup api form portion.
   *
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  protected function buildWorkgroupApiForm(array &$form, FormStateInterface $form_state) {
    $stanford_config = $this->config('stanford_ssp.settings');
    $form['user_info']['use_workgroup_api'] = [
      '#type' => 'radios',
      '#title' => $this->t('Source to validate role mapping groups against.'),
      '#default_value' => $stanford_config->get('use_workgroup_api') ?: 0,
      '#options' => [
        $this->t('SAML Attribute'),
        $this->t('Workgroup API'),
      ],
    ];

    $states = [
      'visible' => [
        'input[name="use_workgroup_api"]' => ['value' => 1],
      ],
    ];

    $form['user_info']['workgroup_api_cert'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path to Workgroup API SSL Certificate.'),
      '#description' => $this->t('For more information on how to get a certificate please see: https://uit.stanford.edu/service/registry/certificates.'),
      '#default_value' => $stanford_config->get('workgroup_api_cert'),
      '#states' => $states,
    ];

    $form['user_info']['workgroup_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key to Workgroup API SSL Key.'),
      '#description' => $this->t('For more information on how to get a key please see: https://uit.stanford.edu/service/registry/certificates.'),
      '#default_value' => $stanford_config->get('workgroup_api_key'),
      '#states' => $states,
    ];
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
      $this->t('Attribute'),
      $this->t('Workgroup'),
      $this->t('Actions'),
    ];
  }

  /**
   * Build the table row for the role mapping string.
   *
   * @param string $role_mapping_string
   *   Formatted role mapping string.
   *
   * @return array
   *   Table render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function buildRoleRow(string $role_mapping_string): array {
    [$role_id, $comparison] = explode(':', $role_mapping_string, 2);

    $exploded_comparison = explode(',', $comparison, 3);

    $value = end($exploded_comparison);
    $role = $this->entityTypeManager->getStorage('user_role')
      ->load($role_id);

    return [
      ['#markup' => $role ? $role->label() : $this->t('Broken: @id', ['@id' => $role_id])],
      ['#markup' => reset($exploded_comparison)],
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
    $attribute = trim(Html::escape($user_input['role_population']['add']['attribute']));
    if ($role_id && $workgroup) {
      // If the user didn't enter an attribute, use the default one from config.
      $attribute = $attribute ?: $this->getDefaultSamlAttribute();

      $mapping_string = "$role_id:$attribute,=,$workgroup";
      $form_state->set(['mappings', $mapping_string], $mapping_string);

      $this->messenger()
        ->addWarning($this->t('These settings have not been saved yet.'));
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
    $mappings = $form_state->get('mappings');
    unset($mappings[$form_state->getTriggeringElement()['#mapping']]);
    $form_state->set('mappings', $mappings);
    $form_state->setRebuild();
  }

  /**
   * Validate the workgroup textfield input.
   *
   * @param array $element
   *   Field render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  public function validateWorkgroup(array $element, FormStateInterface $form_state) {
    $workgroup = $form_state->getValue($element['#parents']);
    if ($workgroup && $this->workgroupApi->isWorkgroupValid($workgroup) === FALSE) {
      $form_state->setError($element, $this->t('Workgroup is not accessible. Please verify permissions are public.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $mappings = $form_state->get('mappings');

    // Add the role mapping that wasn't added via the ajax callback.
    if ($workgroup = $form_state->getValue(['role_population', 'add', 'workgroup'])) {
      $role_id = $form_state->getValue(['role_population', 'add', 'role_id']);
      $attribute = $form_state->getValue(['role_population', 'add', 'attribute']) ?: 'eduPersonEnttitlement';
      $mappings[] = "$role_id:$attribute,=,$workgroup";
    }

    $form_state->setValue('role_population', implode('|', $mappings));
    parent::validateForm($form, $form_state);

    // If using SAML attributes, unset api settings.
    if (!$form_state->getValue('use_workgroup_api')) {
      $form_state->setValue('workgroup_api_cert', '');
      $form_state->setValue('workgroup_api_key', '');
      return;
    }

    $cert_path = $form_state->getValue('workgroup_api_cert');
    $key_path = $form_state->getValue('workgroup_api_key');

    // When the cert values are overridden by settings.php, we will skip
    // validating the files are accurate.
    // @codeCoverageIgnoreStart
    if (self::hasOverriddenApiCert()) {
      return;
    }
    // @codeCoverageIgnoreEnd

    // Both cert and Key have to be populated.
    if (!$cert_path || !$key_path) {
      $form_state->setError($form['user_info']['workgroup_api_cert'], $this->t('Cert and Key are required if using workgroup API.'));
    }

    // User error when they put in the same path for both cert and key.
    if ($cert_path == $key_path) {
      $form_state->setError($form['user_info']['workgroup_api_cert'], $this->t('Cert and Key must be different.'));
    }

    if (!is_file($cert_path)) {
      $form_state->setError($form['user_info']['workgroup_api_cert'], $this->t('Cert must be a file path.'));
    }

    if (!is_file($key_path)) {
      $form_state->setError($form['user_info']['workgroup_api_key'], $this->t('Cert must be a file path.'));
    }

    // Dont bother testing the workgroup api connection if there are any errors.
    if (!$form_state::hasAnyErrors()) {
      $this->workgroupApi->setCert($cert_path);
      $this->workgroupApi->setKey($key_path);
      if (!$this->workgroupApi->connectionSuccessful()) {
        $form_state->setError($form['user_info']['workgroup_api_cert'], $this->t('Cert information invalid. See database logs for more information.'));
      }
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

  /**
   * Get the default value of the saml attribute from config.
   *
   * @return string
   *   Default attribute.
   */
  protected function getDefaultSamlAttribute() {
    return $this->config('stanford_ssp.settings')
      ->get('saml_attribute') ?: 'eduPersonEntitlement';
  }

  /**
   * Check if the api cert paths are overridden by some other manner.
   *
   * @return bool
   *   If the cert and key paths are overridden.
   *
   * @codeCoverageIgnore
   *   Ignore so that we can simulate this in the test.
   */
  protected static function hasOverriddenApiCert() {
    $config = \Drupal::config('stanford_ssp.settings');
    return $config->hasOverrides('workgroup_api_cert') && $config->hasOverrides('workgroup_api_key');
  }

}
