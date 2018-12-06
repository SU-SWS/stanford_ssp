<?php

namespace Drupal\stanford_ssp\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simplesamlphp_auth\Form\SyncingSettingsForm;
use Drupal\user\Entity\Role;

/**
 * Class RoleSyncForm overrides simplesamlphp_auth form for easier UI.
 *
 * @package Drupal\stanford_ssp\Form
 */
class RoleSyncForm extends SyncingSettingsForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    if (!$form_state->get('mappings')) {
      $mappings = explode('|', $form['user_info']['role_population']['#default_value']);
      $form_state->set('mappings', array_combine($mappings, $mappings));
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
   * @param $role_mapping_string
   *
   * @return array
   */
  protected function buildRoleRow($role_mapping_string) {
    list($role_id, $comparison) = explode(':', $role_mapping_string, 2);
    list(, , $value) = explode(',', $comparison);
    $role = Role::load($role_id);
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
  }

}
