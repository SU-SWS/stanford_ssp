<?php

namespace Drupal\stanford_ssp\Service;

/**
 * Interface StanfordSSPWorkgroupApiInterface.
 *
 * @package Drupal\stanford_ssp\Service
 */
interface StanfordSSPWorkgroupApiInterface {

  /**
   * Check if a given cert and key will connect to the workgroup api.
   *
   * @param string $cert
   *   Absolute path to the cert file.
   * @param string $key
   *   Absolute path to key file.
   *
   * @return boolean
   *   If the connection was successful.
   */
  public function connectionSuccessful($cert, $key);

  /**
   * Get an array of roles for the user based on the saml role mapping.
   *
   * @param string $authname
   *   User sunetid.
   *
   * @return array
   *   Array of role ids.
   */
  public function getRolesFromAuthname($authname);

}
