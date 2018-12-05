<?php
/**
 * @file config forms.
 */


/**
 * The main landing page for the configuration form.
 * @param  [type] $form        [description]
 * @param  [type] &$form_state [description]
 * @return [type]              [description]
 */
function stanford_ssp_configuration_form($form = array(), &$form_state) {

  // Vertical Tab container.
  $form['togglers'] = array(
    '#type' => 'vertical_tabs',
    '#default_tab' => 'edit-general-config',
  );

  // Tab Groups
  // ----------------------------------------------------------------

  // Tab for the general options.
  $form['general-config'] = array(
    '#type' => 'fieldset',
    '#title' => t('General Configuration'),
    '#collapsible' => TRUE,
    '#description' => "<h4>" . t('Here you can configure the general settings for the use of this module. Please read the descriptions of each option carefully as it is possible to lock yourself out of this website if improperly configured.') . "</h4>",
    '#group' => 'togglers',
  );

  // Tab for the user options.
  $form['user-config'] = array(
    '#type' => 'fieldset',
    '#title' => t('User Account Configuration'),
    '#collapsible' => TRUE,
    '#description' => t('Here you can configure the general settings for how global interactions happen with users.'),
    '#group' => 'togglers',
  );

  // Tab for the user options.
  $form['saml-config'] = array(
    '#type' => 'fieldset',
    '#title' => t('SAML Configuration'),
    '#collapsible' => TRUE,
    '#description' => t('SimpleSAMLPHP configuration options are set here.'),
    '#group' => 'togglers',
  );

  // General Config
  // ---------------------------------------------------------------

  // Enables / Disables user authentication by simplesamlphp.
  $form['general-config']['stanford_simplesamlphp_auth_activate'] = array(
    '#type' => 'switch',
    '#title' => t('Enable authentication by SSO'),
    '#description' => t("Turn on to allow users to sign in to this website using single sign on. <br />Warning: Enabling before configuring this module could lock you out of your website."),
    '#default_value' => variable_get('stanford_simplesamlphp_auth_activate', FALSE),
  );

  // Enables / Disables user authentication by Drupal.
  $form['general-config']['stanford_simplesamlphp_auth_allowdefaultlogin'] = array(
    '#type' => 'switch',
    '#title' => t('Allow authentication with local Drupal accounts'),
    '#default_value' => variable_get('stanford_simplesamlphp_auth_allowdefaultlogin', TRUE),
    '#description' => t('Turn on if you want to let people log in with local Drupal accounts (Not SSO).'),
  );

  // Enables automatic login when hitting a 403.
  $form['general-config']['stanford_ssp_automagic_login'] = array(
    '#type' => 'switch',
    '#title' => t('Enable automatic login'),
    '#description' => t("Try to automatically authenticate the user if they are presented with a (403) access denied page."),
    '#default_value' => variable_get('stanford_ssp_automagic_login', TRUE),
  );

  // Force users to log in over HTTPS.
  $form['general-config']['stanford_ssp_force_https'] = array(
    '#type' => 'switch',
    '#title' => t('Force users to log in with HTTPS.'),
    '#default_value' => variable_get('stanford_ssp_force_https', FALSE),
    '#description' => t("Enforce that authentication and authenticated transactions happen over HTTPS. This should be enabled on production websites."),
  );

  $form['general-config'] ['stanford_ssp_redirect_on_login'] = array(
    '#type' => 'textfield',
    '#title' => t('Redirect all users on successful login to this url.'),
    '#default_value' => variable_get('stanford_ssp_redirect_on_login', FALSE),
    '#description' => t("Force the redirect of all users to a specific url no matter where they logged in from. This should be entered as a relative url. eg: admin/config/stanford"),
  );

  // User Config
  // ------------------------------------------------------------------

  // Auto create an account for users on auth.
  $form['user-config']['stanford_simplesamlphp_auth_registerusers'] = array(
    '#type' => 'switch',
    '#title' => t('Register users (i.e., auto-provisioning)'),
    '#default_value' => variable_get('stanford_simplesamlphp_auth_registerusers', TRUE),
    '#description' => t("Automatically create an account for a new user on successful log in. Without this enabled only those with user accounts already created in this website can log in."),
  );

  // Connect existing user accounts.
  $form['user-config']['stanford_simplesamlphp_auth_autoenablesaml'] = array(
    '#type' => 'switch',
    '#title' => t('Automatically enable SSO authentication for existing users upon successful login. This will update existing accounts to be able to authenticate with SSO.'),
    '#default_value' => variable_get('stanford_simplesamlphp_auth_autoenablesaml', FALSE),
  );

  // Prevent the default cache for auth users.
  $form['user-config']['stanford_ssp_prevent_cache'] = array(
    '#type' => 'switch',
    '#title' => t('Prevent the browser from caching pages for authenticated users.'),
    '#default_value' => variable_get('stanford_ssp_prevent_cache', FALSE),
    '#description' => t("Pages for logged in users can be dynamic and if the browser caches them, there could be stale information displayed."),
  );

  // Allow for both local passwords and SAML based auth.
  $form['user-config']['stanford_simplesamlphp_auth_allowsetdrupalpwd'] = array(
    '#type' => 'switch',
    '#title' => t('Allow SSO users to set Drupal passwords.'),
    '#default_value' => variable_get('stanford_simplesamlphp_auth_allowsetdrupalpwd', FALSE),
    '#description' => t('Set this to ON if you want to let users set passwords for their local Drupal accounts. This will allow users to log in using either SSO or a local Drupal account password. Disabling this removes the password change fields from the user profile form.'),
    '#states' => array(
      'enabled' => array(
        ':input[name="stanford_simplesamlphp_auth_allowdefaultlogin"]' => array('checked' => TRUE),
      ),
    ),
  );

  // SAML Config
  // ------------------------------------------------------------------

  $form['saml-config']['stanford_simplesamlphp_auth_installdir'] = array(
    '#type' => 'textfield',
    '#title' => t('Installation directory'),
    '#default_value' => variable_get('stanford_simplesamlphp_auth_installdir', '/usr/share/simplesamlphp'),
    '#description' => t('The base directory of simpleSAMLphp. Absolute path with no trailing slash. default: /usr/share/simplesamlphp'),
  );

  $form['saml-config']['stanford_simplesamlphp_auth_authsource'] = array(
    '#type' => 'textfield',
    '#title' => t('Authenticaton source for this service provider'),
    '#default_value' => variable_get('stanford_simplesamlphp_auth_authsource', 'default-sp'),
    '#description' => t('The name of the source to use from //config/authsources.php. default: default-sp'),
  );

  $form['saml-config']['stanford_simplesamlphp_auth_user_name'] = array(
    '#type' => 'textfield',
    '#title' => t("Which attribute should be used as user's name"),
    '#default_value' => variable_get('stanford_simplesamlphp_auth_user_name', 'displayName'),
    '#description' => t('Example: <i>eduPersonPrincipalName</i> or <i>displayName</i><br />If the attribute is multivalued, the first value will be used.'),
    '#required' => TRUE,
  );

  $form['saml-config']['stanford_simplesamlphp_auth_unique_id'] = array(
    '#type' => 'textfield',
    '#title' => t('Which attribute should be used as unique identifier for the user'),
    '#default_value' => variable_get('stanford_simplesamlphp_auth_unique_id', 'eduPersonPrincipalName'),
    '#description' => t('Example: <i>eduPersonPrincipalName</i> or <i>eduPersonTargetedID</i><br />If the attribute is multivalued, the first value will be used.'),
    '#required' => TRUE,
  );

  $form['saml-config']['stanford_simplesamlphp_auth_mailattr'] = array(
    '#type' => 'textfield',
    '#title' => t('Which attribute from simpleSAMLphp should be used as user mail address'),
    '#default_value' => variable_get('stanford_simplesamlphp_auth_mailattr', 'mail'),
    '#description' => t('Example: <i>mail</i><br />If the user attribute is multivalued, the first value will be used.'),
  );

  return system_settings_form($form);
}


/**
 * Authorization and authentication settings form.
 * @param  [type] $form        [description]
 * @param  [type] &$form_state [description]
 * @return [type]              [description]
 */
function stanford_ssp_authorizations_form($form, &$form_state) {

  // Vertical Tab container.
  $form['togglers'] = array(
    '#type' => 'vertical_tabs',
    '#default_tab' => 'edit-general-config',
  );

  // Tab Groups
  // ----------------------------------------------------------------

  // Tab for the general options.
  $form['saml-config'] = array(
    '#type' => 'fieldset',
    '#title' => t('SSO Accounts'),
    '#collapsible' => TRUE,
    '#group' => 'togglers',
  );

  // Tab for the user options.
  $form['local-config'] = array(
    '#type' => 'fieldset',
    '#title' => t('Local Drupal Accounts'),
    '#collapsible' => TRUE,
    '#group' => 'togglers',
  );

  // SSO AUTH CONFIG
  // -------------------------------------------------------------------

  $options = array(
    'any' => t("Allow any valid SSO (SUNet) user."),
    'restrict' => t("Restrict access to specific users and groups."),
  );

  $form['saml-config']['stanford_ssp_auth_restrictions'] = array(
    '#type' => 'radios',
    '#title' => t('SSO Authorization Restrictions.'),
    '#default_value' => variable_get('stanford_ssp_auth_restrictions', 'any'),
    '#options' => $options,
  );

  $form['saml-config']['stanford_ssp_auth_restriction_sunet'] = array(
    '#type' => 'textfield',
    '#title' => t('Allowed SUNet IDs'),
    '#default_value' => variable_get('stanford_ssp_auth_restriction_sunet', ''),
    '#description' => t('A comma-separated list of SUNet IDs that should be allowed to login with simpleSAMLphp. If left blank, any valid SUNet ID user can login.'),
    '#states' => array(
      'visible' => array(
        ':input[name="stanford_ssp_auth_restrictions"]' => array('value' => 'restrict'),
      ),
    ),
  );

  $form['saml-config']['stanford_ssp_auth_restriction_group'] = array(
    '#type' => 'textfield',
    '#title' => t('Allowed Workgroups'),
    '#default_value' => variable_get('stanford_ssp_auth_restriction_group', ''),
    '#description' => t('A comma-separated list of Workgroups that should be allowed to login with simpleSAMLphp. If left blank, any workgroup can login.'),
    '#states' => array(
      'visible' => array(
        ':input[name="stanford_ssp_auth_restrictions"]' => array('value' => 'restrict'),
      ),
    ),
  );

  // LOCAL AUTH CONFIG
  // -------------------------------------------------------------------

  $roles = user_roles(TRUE);
  $form['local-config']['stanford_simplesamlphp_auth_allowdefaultloginroles'] = array(
    '#type' => 'select',
    '#size' => 7,
    '#options' => $roles,
    '#multiple' => TRUE,
    '#title' => t('Which roles should be allowed to login with local accounts?'),
    '#default_value' => variable_get('stanford_simplesamlphp_auth_allowdefaultloginroles', array()),
    '#description' => t('Roles that should be allowed to login without SSO. Examples are dev/admin roles.'),
  );

  $form['local-config']['stanford_simplesamlphp_auth_allowdefaultloginusers'] = array(
    '#type' => 'textfield',
    '#title' => t('Which users should be allowed to login with local accounts?'),
    '#default_value' => variable_get('stanford_simplesamlphp_auth_allowdefaultloginusers', ''),
    '#description' => t('Example: <i>1,2,3</i><br />A comma-separated list of user IDs that should be allowed to login without SSO. If left blank, all local accounts can login.'),
  );

  return system_settings_form($form);
}

/**
 * Role mapping form for mapping a role to an authenticated user.
 * @param  [type] $form        [description]
 * @param  [type] &$form_state [description]
 * @return [type]              [description]
 */
function stanford_ssp_role_mappings_form($form, &$form_state) {

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


/**
 * The login block and forms form form.
 * @param  [type] $form        [description]
 * @param  [type] &$form_state [description]
 * @return [type]              [description]
 */
function stanford_ssp_login_block_forms_form($form, &$form_state) {

  $form['stanford_ssp_show_local_login'] = array(
    '#type' => 'switch',
    '#title' => t('Show the local login form on the user page'),
    '#default_value' => variable_get('stanford_ssp_show_local_login', TRUE),
    '#description' => t("Turn on to show the local Drupal account login option on the !link.", array("!link" => l("user page", "user"))),
  );

  $form['stanford_ssp_show_sso_login'] = array(
    '#type' => 'switch',
    '#title' => t('Show the SSO login link on user page'),
    '#default_value' => variable_get('stanford_ssp_show_sso_login', TRUE),
    '#description' => t("Turn on to show the SSO login link on the !link.", array("!link" => l("user page", "user"))),
  );

  $form['stanford_ssp_sso_link_text'] = array(
    '#type' => 'textfield',
    '#title' => t('SSO login link text'),
    '#default_value' => variable_get('stanford_ssp_sso_link_text', t("Log in with your SUNet ID »")),
    '#description' => t("Override the link text for the SSO login link on the !link.", array("!link" => l("user page", "user"))),
  );


  return system_settings_form($form);
}

/**
 * ***************************************************************
 * VALIDATE FUNCTIONS
 * ***************************************************************
 */

/**
 * Validation function for stanford_ssp_configuration_form().
 */
function stanford_ssp_configuration_form_validate($form, &$form_state) {
  $values = $form_state['values'];

  // Prevent both ssp and local login options from being disabled.
  if ($values["stanford_simplesamlphp_auth_activate"] === 0 && $values["stanford_simplesamlphp_auth_allowdefaultlogin"] === 0) {
    form_set_error('stanford_simplesamlphp_auth_allowdefaultlogin');
    form_set_error('stanford_simplesamlphp_auth_activate', "Both SSO and Local log in cannot be disabled or you will lock yourself out of your site. Please enable one.");
  }

}

/**
 * Validation hook for stanford_ssp_authorizations_form.
 */
function stanford_ssp_authorizations_form_validate(&$form, &$form_state) {
  $values = $form_state["values"];
  $workgroups = stanford_ssp_format_entitlement($values['stanford_ssp_auth_restriction_group']);
  form_set_value($form['saml-config']['stanford_ssp_auth_restriction_group'], $workgroups, $form_state);
}


/**
 * ***************************************************************
 * SUBMIT FUNCTIONS
 * ***************************************************************
 */

/**
 *
 * Implements hook_form_submit().
 *
 * @param $form
 * @param $form_state
 */
function stanford_ssp_role_mappings_form_submit($form, &$form_state) {

  $entitlement = $form_state['values']['entitlement'];
  $role = $form_state['values']['role_id'];

  $entitlement = stanford_ssp_format_entitlement($entitlement);

  // The silly save button could trigger this if there is not a value.
  if (!empty($entitlement) && !empty($role)) {
    stanford_ssp_map_entitlement_to_role($entitlement, $role);
  }

}

/**
 * Maps an entitlement to a role.
 *
 * @param string $entitlement
 *   A value in eduPersonEntitlement, e.g., anchorage_support.
 * @param mixed $role
 *   Either the role id or a name.
 */
function stanford_ssp_map_entitlement_to_role($entitlement, $role) {
  $entitlement = check_plain($entitlement);
  // Look up rid.

  $role_object = FALSE;
  if (is_numeric($role)) {
    $role_object = user_role_load($role);
  }
  elseif (is_scalar($role)) {
    $role_object = user_role_load_by_name($role);
  }

  if (!$role_object) {
    drupal_set_message(t('No role exists with the name "@role"', array('@role' => $role)), 'error');
  }
  else {
    $rid = $role_object->rid;
    // Look up current role mapping, if any.
    $role_mapping = variable_get('stanford_simplesamlphp_auth_rolepopulation');
    if (!empty($role_mapping)) {
      $role_mapping .= "|";
    }
    // Add our mapping.
    $role_mapping .= $rid . ":eduPersonEntitlement,=," . $entitlement;
    // Save our mapping.
    variable_set('stanford_simplesamlphp_auth_rolepopulation', $role_mapping);
    $message = t('Mapped the "@entitlement" entitlement to the "@role" role.', array('@entitlement' => $entitlement, '@role' => $role_object->name));
    drupal_set_message($message);
    watchdog('stanford_ssp', $message);
  }
}

/**
 * @param $form
 * @param $form_state
 */
function stanford_ssp_remove_waird($form, &$form_state) {
  $trigger = $form_state["triggering_element"]['#name'];
  $index = $form_state['values'][$trigger . "_value"];
  $rolemaps = variable_get("stanford_simplesamlphp_auth_rolepopulation", array());
  $xp = explode("|", $rolemaps);
  if (isset($xp[$index])) {
    unset($xp[$index]);
  }
  $imp = implode("|", $xp);
  variable_set("stanford_simplesamlphp_auth_rolepopulation", $imp);
  drupal_set_message("Role mapping was removed");
}

/**
 * *****************************************************************
 * HELPER / UTIL
 * *****************************************************************
 */


