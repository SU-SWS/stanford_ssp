<?php

/**
 * @file
 * Role Mapping.
 */

/**
 * Role mapping form for mapping a role to an authenticated user.
 *
 * @param  [type] $form        [description]
 * @param  [type] &$form_state [description]
 * @return [type]              [description]
 */
function stanford_ssp_role_mapping_role_mappings_form($form, &$form_state) {

  $options = array(
    'none' => t("Do not adjust roles. Allow local administration of roles only."),
    'grant' => t("Grant new roles only. Will only add roles based on role assignments."),
    'reassign' => t("Re-evaluate roles on every log in. This will grant and remove roles."),
  );

  $form['stanford_ssp_auth_role_map'] = array(
    '#type' => 'radios',
    '#title' => 'Action to perform on successful authentication with SUNet ID',
    '#options' => $options,
    '#default_value' => variable_get("stanford_ssp_auth_role_map", 'none'),
  );

  // ROLE MAPPING FORM.
  $table = array();
  $submitted = !empty($form_state['post']);
  $roles = user_roles(TRUE);
  // Remove authenticated user has this will be defaulted to true.
  unset($roles[2]);

  $form['role_id'] = array(
    '#name' => 'role_id',
    '#type' => 'select',
    '#options' => $roles,
  );

  $form['entitlement'] = array(
    '#name' => 'entitlement',
    '#type' => 'textfield',
    '#default_value' => '',
  );

  $form['new_submit'] = array(
    '#name' => 'new_submit',
    '#type' => 'submit',
    '#value' => t('Add Mapping'),
  );

  $table[] = array(drupal_render($form['role_id']), drupal_render($form['entitlement']), drupal_render($form['new_submit']));

  $header = array(
    t('Drupal Role'),
    t('Workgroup (e.g. anchorage_humanbiology-admins)'),
    t('Action')
  );

  $rolemaps = variable_get("stanford_simplesamlphp_auth_rolepopulation", "");
  if (!empty($rolemaps)) {
    $xp = explode("|", $rolemaps);
    foreach ($xp as $index => $rule) {
      $button_id = 'remove_warid_' . $index;
      $form[$button_id] = array(
        '#name' => $button_id,
        '#type' => 'submit',
        '#value' => t('Remove Mapping'),
        '#submit' => array('stanford_ssp_remove_waird'),
      );

      // Special hidden element.
      $form[$button_id . "_value"] = array(
        '#type' => 'value',
        '#value' => $index,
      );

      // $rule = rid:expression.
      $bits = explode(':', $rule, 2);
      $role = user_role_load($bits[0]);
      $eval = str_getcsv($bits[1]);

      $row = array($role->name, $eval[2], drupal_render($form[$button_id]));
      $table[] = $row;
    }
  }

  $form['add_roles'] = array(
    '#markup' => theme('table', array(
      'header' => $header,
      'rows' => $table,
      'attributes' => array(
        "class" => array(
          'add-roles-table',
        ),
      ),
    )),
    // Unfortunately states don't work here so we added a bit of JS to account
    // for the loss.
    '#attached' => array(
      'js' => array(
        drupal_get_path('module', 'stanford_ssp') . '/js/stanford_ssp.js',
      ),
    ),
  );

  // END ROLE MAPPING FORM.
  $form["#submit"][] = "stanford_ssp_role_mappings_form_submit";
  return system_settings_form($form);
}
