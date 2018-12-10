<?php

namespace Drupal\stanford_ssp\Service;

/**
 * Interface StanfordSSPWorkgroupApiInterface.
 *
 * @package Drupal\stanford_ssp\Service
 */
interface StanfordSSPWorkgroupApiInterface {

  /**
   * Set the certificate path.
   *
   * @param string $cert_path
   *   Path to file.
   */
  public function setCert($cert_path);

  /**
   * Set the key file path.
   *
   * @param string $key_path
   *   Path to file.
   */
  public function setKey($key_path);

  /**
   * Check if a given cert and key will connect to the workgroup api.
   *
   * @return bool
   *   If the connection was successful.
   */
  public function connectionSuccessful();

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
