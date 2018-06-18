<?php

/**
 * @file
 * Non administrative page callbacks for SimpleSamlPHP Auth module.
 */

/**
 * Handles the login/registration of SAML users.
 *
 * Represents the Drupal page (saml_login), which triggers user authentication
 * against the SimpleSAMLphp service provider.
 */
function stanford_simplesamlphp_auth_loginpage() {

  // Get the simplesamlphp session. Throws error if there is an issue.
  stanford_simplesamlphp_auth_autoload();

  // Do nothing if config says we are not enabled.
  if (!stanford_simplesamlphp_auth_is_enabled()) {
    drupal_not_found();
  }

  global $user;
  module_load_include('inc', 'stanford_simplesamlphp_auth');
  $saml = stanford_simplesamlphp_auth_get_saml_info();

  // If the Drupal user object is available prompt a message to log out first.
  if ($user->uid > 1) {
    drupal_set_message(t("You are already logged in an cannot log in again. Please log out first to switch users."), "error");
    drupal_goto("user/" . $user->uid);
  }

  // If a Drupal user is not available and there is no SAML information then we
  // need to kick off the request.
  if (user_is_anonymous() && empty($saml['attributes'])) {

    // If goto is set then create a cookie we can use to redirect the user
    // after login. We cannot use SAML's ReturnTo option as we need to hit
    // the endpoint again to log the user in with Drupal. After we log the user
    // in we can redirect them to the correct destination.
    if (isset($_GET['goto'])) {
      setcookie("stanford_ssp_goto", check_plain($_GET['goto']), time() + 120);  /* expire in 2 min */
    }
    // Fire up a request to the IDP. This leaves Drupal and ends here.
    $saml['source']->requireAuth();
  }

  // If a Drupal user is not available but there is SAML attributes then we need
  // to either log in that user or register a new one pending the setting for
  // auto registration.
  if (user_is_anonymous() && !empty($saml['attributes'])) {
    stanford_simplesamlphp_handle_user_login_register();
  }

  // After all of that log the sucker in!
  stanford_simplesamlphp_auth_log_the_user_in();

  // And redirect to somewhere.
  drupal_goto("user/" . $user->uid);
}
