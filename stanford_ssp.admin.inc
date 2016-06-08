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
    '#description' => t('Here you can configure the general settings for the use of this module.'),
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
  $form['general-config']['simplesamlphp_auth_activate'] = array(
    '#type' => 'switch',
    '#title' => t('Enable authentication by SSO'),
    '#description' => t("Enabling before configuring the module could lock you out of your website."),
    '#default_value' => variable_get('simplesamlphp_auth_activate', FALSE),
  );

  // Enables / Disables user authentication by Drupal.
  $form['general-config']['simplesamlphp_auth_allowdefaultlogin'] = array(
    '#type' => 'switch',
    '#title' => t('Allow authentication with local Drupal accounts'),
    '#default_value' => variable_get('simplesamlphp_auth_allowdefaultlogin', TRUE),
    '#description' => t('Check this box if you want to let people log in with local Drupal accounts (without using simpleSAMLphp). If you want to restrict this privilege to certain users you can enter the Drupal user IDs in the authorizations tab.'),
  );

  // Enables automatic login when hitting a 403.
  $form['general-config']['stanford_ssp_automagic_login'] = array(
    '#type' => 'switch',
    '#title' => t('Enable automatic login'),
    '#description' => t("Try to authenticate the user if they are presented with a 403 access denied response."),
    '#default_value' => variable_get('stanford_ssp_automagic_login', TRUE),
  );

  // Force users to log in over HTTPS.
  $form['general-config']['stanford_ssp_force_https'] = array(
    '#type' => 'switch',
    '#title' => t('Force users to log in with HTTPS.'),
    '#default_value' => variable_get('stanford_ssp_force_https', FALSE),
    '#description' => t("Should be enabled on production websites."),
  );

  $form['general-config'] ['stanford_ssp_redirect_on_login'] = array(
    '#type' => 'textfield',
    '#title' => t('Redirect all users on successfull login to this url.'),
    '#default_value' => variable_get('stanford_ssp_redirect_on_login', FALSE),
    '#description' => t("Force the redirect of all users to a specific url no matter where they logged in from."),
  );

  // User Config
  // ------------------------------------------------------------------

  // Auto create an account for users on auth.
  $form['user-config']['simplesamlphp_auth_registerusers'] = array(
    '#type' => 'switch',
    '#title' => t('Register users (i.e., auto-provisioning)'),
    '#default_value' => variable_get('simplesamlphp_auth_registerusers', TRUE),
    '#description' => t("Determines wether or not the module should automatically create/register new Drupal accounts for users that authenticate using SimpleSAMLphp. Unless you've done some custom work to provision Drupal accounts with the necessary authmap entries you will want this checked. NOTE: If unchecked each user must already have been provisioned a Drupal account with an appropriate entry in the authmap table before logging in. Otherwise they will receive a notice and be denied access. Be aware that simply creating a Drupal account will not create the necessary entry in the authmap table."),
  );

  // Connect existing user accounts.
  $form['user-config']['simplesamlphp_auth_autoenablesaml'] = array(
    '#type' => 'switch',
    '#title' => t('Automatically enable SAML authentication for existing users upon successful login'),
    '#default_value' => variable_get('simplesamlphp_auth_autoenablesaml', FALSE),
  );

  // Prevent the default cache for auth users.
  $form['user-config']['stanford_ssp_prevent_cache'] = array(
    '#type' => 'switch',
    '#title' => t('Prevent the browser from caching pages for authenticated users.'),
    '#default_value' => variable_get('stanford_ssp_prevent_cache', FALSE),
    '#description' => t("Pages for logged in users can be dynamic and if the browser caches them, there could be stale information displayed."),
  );

  // Allow for both local passwords and SAML based auth.
  $form['user-config']['simplesamlphp_auth_allowsetdrupalpwd'] = array(
    '#type' => 'switch',
    '#title' => t('Allow SAML users to set Drupal passwords.'),
    '#default_value' => variable_get('simplesamlphp_auth_allowsetdrupalpwd', FALSE),
    '#description' => t('Set this to ON if you want to let people set passwords for their local Drupal accounts. This will allow users to log in using either SAML or a local Drupal account. Disabling this removes the password change fields from the user profile form.<br/>NOTE: In order for them to login using their local Drupal password you must allow local logins with the settings below.'),
    '#states' => array(
      'enabled' => array(
        ':input[name="simplesamlphp_auth_allowdefaultlogin"]' => array('checked' => TRUE),
      ),
    ),
  );

  // SAML Config
  // ------------------------------------------------------------------

  $form['saml-config']['simplesamlphp_auth_installdir'] = array(
    '#type' => 'textfield',
    '#title' => t('Installation directory (default: /usr/share/simplesamlphp)'),
    '#default_value' => variable_get('simplesamlphp_auth_installdir', '/usr/share/simplesamlphp'),
    '#description' => t('The base directory of simpleSAMLphp. Absolute path with no trailing slash.'),
  );

  $form['saml-config']['simplesamlphp_auth_authsource'] = array(
    '#type' => 'textfield',
    '#title' => t('Authenticaton source for this SP (default: default-sp)'),
    '#default_value' => variable_get('simplesamlphp_auth_authsource', 'default-sp'),
    '#description' => t('The name of the source to use from /usr/share/simplesamlphp/config/authsources.php'),
  );

  $form['saml-config']['simplesamlphp_auth_user_name'] = array(
    '#type' => 'textfield',
    '#title' => t("Which attribute from simpleSAMLphp should be used as user's name"),
    '#default_value' => variable_get('simplesamlphp_auth_user_name', 'displayName'),
    '#description' => t('Example: <i>eduPersonPrincipalName</i> or <i>displayName</i><br />If the attribute is multivalued, the first value will be used.'),
    '#required' => TRUE,
  );

  $form['saml-config']['simplesamlphp_auth_unique_id'] = array(
    '#type' => 'textfield',
    '#title' => t('Which attribute from simpleSAMLphp should be used as unique identifier for the user'),
    '#default_value' => variable_get('simplesamlphp_auth_unique_id', 'eduPersonPrincipalName'),
    '#description' => t('Example: <i>eduPersonPrincipalName</i> or <i>eduPersonTargetedID</i><br />If the attribute is multivalued, the first value will be used.'),
    '#required' => TRUE,
  );

  $form['saml-config']['simplesamlphp_auth_mailattr'] = array(
    '#type' => 'textfield',
    '#title' => t('Which attribute from simpleSAMLphp should be used as user mail address'),
    '#default_value' => variable_get('simplesamlphp_auth_mailattr', 'mail'),
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
    '#title' => t('SAML Accounts'),
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

  // SAML AUTH CONFIG
  // -------------------------------------------------------------------

  $options = array(
    'any' => t("Allow any valid SUNet user."),
    'restrict' => t("Restrict access to specific users and groups."),
  );

  $form['saml-config']['stanford_ssp_auth_restrictions'] = array(
    '#type' => 'radios',
    '#title' => t('SAML Authorization Restrictions.'),
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
  $form['local-config']['simplesamlphp_auth_allowdefaultloginroles'] = array(
    '#type' => 'select',
    '#size' => 7,
    '#options' => $roles,
    '#multiple' => TRUE,
    '#title' => t('Which roles should be allowed to login with local accounts?'),
    '#default_value' => variable_get('simplesamlphp_auth_allowdefaultloginroles', array()),
    '#description' => t('Roles that should be allowed to login without simpleSAMLphp. Examples are dev/admin roles or guest roles.'),
  );

  $form['local-config']['simplesamlphp_auth_allowdefaultloginusers'] = array(
    '#type' => 'textfield',
    '#title' => t('Which users should be allowed to login with local accounts?'),
    '#default_value' => variable_get('simplesamlphp_auth_allowdefaultloginusers', ''),
    '#description' => t('Example: <i>1,2,3</i><br />A comma-separated list of user IDs that should be allowed to login without simpleSAMLphp. If left blank, all local accounts can login.'),
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
    'merge' => t("Merge role mapping assignments with current account roles."),
    'reassign' => t("Re-evaluate roles on every log in."),
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

  $form['new_rid'] = array(
    '#name' => 'new_rid',
    '#type' => 'select',
    '#options' => $roles,
  );

  $form['new_group'] = array(
    '#name' => 'new_group',
    '#type' => 'textfield',
    '#default_value' => '',
  );

  $form['new_submit'] = array(
    '#name' => 'new_submit',
    '#type' => 'submit',
    '#value' => t('Add Mapping'),
  );

  if (!$submitted) {
    $table[] = array(drupal_render($form['new_rid']), drupal_render($form['new_group']), drupal_render($form['new_submit']));
  }
  else {
    // Keep the UI consistent.
    $form['new_rid']['#prefix'] = '<table><tr class="odd"><td>';
    $form['new_rid']['#suffix'] = '</td><td>';
    $form['new_group']['#suffix'] = '</td><td>';
    $form['new_submit']['#suffix'] = '</td><td>';
    $form['cancel'] = array(
      '#type' => 'markup',
      '#value' => l(t('Cancel'), 'admin/config/webauth/mappings'),
      '#suffix' => '</td></tr></table>',
    );
  }
  $header = array(
    t('Drupal Role'),
    t('Workgroup (e.g. stanford:staff)'),
    t('Action')
  );
  $form['add_roles'] = array(
    '#markup' => theme('table', array(
      'header' => $header,
      'rows' => $table,
    )),
  );

  //$result = db_query("SELECT wr.warid, r.name, wr.wa_group as `group` FROM {webauth_roles} wr INNER JOIN {role} r ON wr.rid = r.rid WHERE r.rid > 2");
  // while ($group = $result->fetchObject()) {
  //   $button_id = 'remove_warid_' . $group->warid;
  //   $form[$button_id] = array(
  //     '#name' => $button_id,
  //     '#type' => 'submit',
  //     '#value' => t('Remove Mapping'),
  //   );
  //   $row = array($group->name, $group->group, drupal_render($form[$button_id]));
  //   $table[] = $row;
  // }


  // END ROLE MAPPING FORM.

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
    '#title' => t('Enable local login form on user page'),
    '#default_value' => variable_get('stanford_ssp_show_local_login', TRUE),
    '#description' => t("Turn on to show the local Drupal account login option on the user page."),
  );

  $form['stanford_ssp_show_sso_login'] = array(
    '#type' => 'switch',
    '#title' => t('Enable SSO login link on user page'),
    '#default_value' => variable_get('stanford_ssp_show_sso_login', TRUE),
    '#description' => t("Turn on to show the SSO (SAML) login link on the user page."),
  );

  $form['stanford_ssp_sso_link_text'] = array(
    '#type' => 'textfield',
    '#title' => t('SSO (SAML) login link text?'),
    '#default_value' => variable_get('stanford_ssp_sso_link_text', t("Log in with your SUNet ID »")),
    '#description' => t('Override the link text for the SSO login on the user page.'),
  );


  return system_settings_form($form);
}


/**
 * Add a new SSO user account.
 * @param  [type] $form        [description]
 * @param  [type] &$form_state [description]
 * @return [type]              [description]
 */
function stanford_ssp_add_sso_user($form, &$form_state) {

  $form['sunetid'] = array(
    '#type' => 'textfield',
    '#title' => t('SUNetID'),
    '#description' => t('Enter the SUNetID of the user you wish to add.'),
    '#required' => TRUE,
  );

  $form['name']  = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#description' => t('If you wish to specify the user\'s preferred name (instead of sunetid@stanford.edu), enter it here.'),
  );

  $form['email']  = array(
    '#type' => 'textfield',
    '#title' => t('Email Address'),
    '#description' => t('If you wish to specify an alternate email address (instead of sunetid@stanford.edu), enter it here.'),
  );

  $roles = user_roles(TRUE);
  // Remove authenticated user has this will be defaulted to true.
  unset($roles[2]);

  // Only show the option if there is an option.
  if (!empty($roles)) {
    $form["roles"] = array(
      '#title' => "Roles",
      '#description' => t("Add roles to the new user account."),
      '#type' => 'select',
      '#options' => $roles,
      '#default_value' => array(1),
      '#multiple' => TRUE,
      '#size' => 7,
    );
  }

  if (variable_get("simplesamlphp_auth_allowsetdrupalpwd", FALSE)) {
    $form['pass'] = array(
      '#type' => 'password_confirm',
      '#size' => 25,
    );
  }

  $form['notify'] = array(
    '#type' => 'checkbox',
    '#title' => t('Notify user of new account'),
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Add SSO User'),
  );

  return $form;
}

/**
 * ***************************************************************
 * VALIDATE FUNCTIONS
 * ***************************************************************
 */

/**
 * Validation function for Add WebAuth User form
 */
function stanford_ssp_add_sso_user_validate($form, &$form_state) {

  $sunet = strtolower(trim(check_plain($form_state['values']['sunetid'])));

  // Make sure there isn't an entry in the authmap table.
  // Authmap entries use the SUNetID@stanford.edu format.
  $authname = $sunet . '@stanford.edu';

  // user_get_authmaps returns 0 if there are no authmaps,
  // or a keyed array if there are authmap entries.
  $authmaps = user_get_authmaps($authname);

  if ((($authmaps) !== 0) && ($authmaps['authname_simplesamlphp_auth'] == $authname)) {
    form_set_error('sunetid', 'Could not create user. Authname ' . $authname . ' already exists. Has the user already been created with a different username but the same SUNetID?');
  }

  // If no name is specified, use the default name (sunetid + @stanford.edu).
  $name = trim(check_plain($form_state['values']['name']));
  if (empty($name)) {
    $name  = $authname;
  }

  // Check that there is no user with the same name.
  if (user_load_by_name($name)) {
    form_set_error('name', 'Could not create user. Username ' . $name . ' already exists.');
  }

  // If no email was specified, we'll use the default (sunetid + @stanford.edu).
  $default_email = $sunet . '@stanford.edu';
  $email = strtolower(trim($form_state['values']['email']));

  if (!empty($email) && !valid_email_address($email)) {
    form_set_error('email', t('The e-mail address %email is not valid.', array('%email' => $email)));
  }

  if (empty($email)) {
    $email = $default_email;
  }

  // Check that there is no user with the same email
  // Drupal will let us create the user with a duplicate email, but
  // the user will run into issues when making changes to their profile.
  if (user_load_by_mail($email)) {
    form_set_error('email', 'Could not create user. Email ' . $email . ' already in use.');
  }

}



/**
 * ***************************************************************
 * SUBMIT FUNCTIONS
 * ***************************************************************
 */

/**
 * Submit handler for stanford_ssp_add_sso_user form.
 * @param  [type] &$form       [description]
 * @param  [type] &$form_state [description]
 * @return [type]              [description]
 */
function stanford_ssp_add_sso_user_submit(&$form, &$form_state) {

  // Sunet.
  $sunet = strtolower(trim(check_plain($form_state['values']['sunetid'])));
  $authname = $sunet . '@stanford.edu';

  // If no name is specified, use the default name (sunetid + @stanford.edu).
  $name = trim(check_plain($form_state['values']['name']));
  if (empty($name)) {
    $name  = $authname;
  }

  // Email and default.
  $default_email = $sunet . '@stanford.edu';
  $email = strtolower(trim($form_state['values']['email']));
  if (empty($email)) {
    $email = $default_email;
  }

  $account = new stdClass();
  $account->is_new = TRUE;
  $account->name = $name;
  $account->pass = user_password();
  $account->mail = $email;
  $account->init = $sunet . '@stanford.edu';
  $account->status = TRUE;
  $roles = array(
    DRUPAL_AUTHENTICATED_RID => TRUE
  );

  // Merge in roles from config form.
  $config_roles = array_keys($form_state["values"]["roles"]);
  foreach ($config_roles as $rid) {
    $roles[$rid] = TRUE;
  }

  $account->roles = $roles;
  $account->timezone = variable_get('date_default_timezone', date_default_timezone_get());
  $account = user_save($account);

  // Account was not created.
  if (!isset($account->uid)) {
    drupal_set_message("User account creation went wrong. Please try again.", "error");
    return;
  }

  user_set_authmaps($account, array('authname_simplesamlphp_auth' => $authname));
  watchdog('Stanford SSP', 'Created user: %user', array('%user' => $name));
  $message = t('Successfully created SAML account for %user', array('%user' => $name));
  drupal_set_message($message);

  // Was the notify checkbox checked?
  if ($form_state["values"]["notify"] && $account) {
    _user_mail_notify('status_activated', $account);
    drupal_set_message("Email sent to user", "status");
  }

}


/**
 * *****************************************************************
 * HELPER / UTIL
 * *****************************************************************
 */


