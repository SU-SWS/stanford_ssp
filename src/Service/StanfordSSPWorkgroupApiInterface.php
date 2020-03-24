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
   * Get the current cert path.
   *
   * @return string|null
   *   Absolute cert path.
   */
  public function getCert();

  /**
   * Set the key file path.
   *
   * @param string $key_path
   *   Path to file.
   */
  public function setKey($key_path);

  /**
   * Get the current cert key path.
   *
   * @return string|null
   *   Absolute key path.
   */
  public function getKey();

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

  /**
   * Check if the given name is part of the workgroup provided.
   *
   * @param string $workgroup
   *   Workgroup name like uit:sws.
   * @param string $name
   *   User's sunetid.
   *
   * @return bool
   *   If the user is part of the group.
   */
  public function userInGroup($workgroup, $name);

  /**
   * Check if the given name is part of any workgroup provided.
   *
   * @param array $workgroups
   *   Array of Workgroups name like uit:sws.
   * @param string $name
   *   User's sunetid.
   *
   * @return bool
   *   If the user is part of any group.
   */
  public function userInAnyGroup(array $workgroups, $name);

  /**
   * Check if the given name is part of all workgroups provided.
   *
   * @param array $workgroups
   *   Array of Workgroups name like uit:sws.
   * @param string $name
   *   User's sunetid.
   *
   * @return bool
   *   If the user is part of all groups.
   */
  public function userInAllGroups(array $workgroups, $name);

  /**
   * Check if the workgroup is valid and is public.
   *
   * @param string $workgroup
   *   Workgroup name to test.
   *
   * @return bool|null
   *   True if the given workgroup is valid, null if unknown.
   */
  public function isWorkgroupValid($workgroup);

}
