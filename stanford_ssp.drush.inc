<?php
/**
 * @file
 * drush commands for Stanford SimpleSAML PHP.
 */

/**
 * Implements hook_drush_command().
 *
 * @return array List of drush commands.
 */
function stanford_ssp_drush_command() {
  $items = array();

  $items['saml-entitlement-role'] = array(
    'description' => 'Map a SAML entitlement to a role',
    'arguments' => array(
      'entitlement' => 'A value from eduPersonEntitlement, e.g., "anchorage_support"',
      'role' => 'The name of the role, e.g., "administrator"',
    ),
    'aliases' => array('ser'),
  );
  return $items;
}

function drush_stanford_ssp_saml_entitlement_role($entitlement, $role) {
  stanford_ssp_map_entitlement_to_role($entitlement, $role);
}