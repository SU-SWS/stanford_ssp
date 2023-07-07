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
  public function setCert(string $cert_path): void;

  /**
   * Get the current cert path.
   *
   * @return string|null
   *   Absolute cert path.
   */
  public function getCert(): ?string;

  /**
   * Set the key file path.
   *
   * @param string $key_path
   *   Path to file.
   */
  public function setKey(string $key_path): void;

  /**
   * Get the current cert key path.
   *
   * @return string|null
   *   Absolute key path.
   */
  public function getKey(): ?string;

  /**
   * Check if a given cert and key will connect to the workgroup api.
   *
   * @return bool
   *   If the connection was successful.
   */
  public function connectionSuccessful(): bool;

  /**
   * Get an array of roles for the user based on the saml role mapping.
   *
   * @param string $authname
   *   User sunetid.
   *
   * @return string[]
   *   Array of role ids.
   */
  public function getRolesFromAuthname($authname): array;

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
  public function userInGroup(string $workgroup, string $name): bool;

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
  public function userInAnyGroup(array $workgroups, string $name): bool;

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
  public function userInAllGroups(array $workgroups, string $name): bool;

  /**
   * Check if the workgroup is valid and is public.
   *
   * @param string $workgroup
   *   Workgroup name to test.
   *
   * @return bool
   *   True if the given workgroup is valid, false if unknown.
   */
  public function isWorkgroupValid(string $workgroup): bool;

  /**
   * Check if the provided sunet exists.
   *
   * @param string $sunet
   *   Sunet String to check.
   *
   * @return bool
   *   If the user exists.
   */
  public function isSunetValid(string $sunet): bool;

}
