<?php

namespace Drupal\stanford_ssp\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Site\Settings;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Workgroup api service class to connect to the API.
 *
 * @package Drupal\stanford_ssp\Service
 */
class StanfordSSPWorkgroupApi implements StanfordSSPWorkgroupApiInterface {

  const WORKGROUP_API = 'https://workgroupsvc.stanford.edu/workgroups/2.0';

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Guzzle client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $guzzle;

  /**
   * Logger channel service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Path to cert file.
   *
   * @var string
   */
  protected $cert;

  /**
   * Path to key file.
   *
   * @var string
   */
  protected $key;

  /**
   * StanfordSSPWorkgroupApi constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \GuzzleHttp\ClientInterface $guzzle
   *   Http client guzzle service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Logger channel factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $guzzle, LoggerChannelFactoryInterface $logger) {
    $this->configFactory = $config_factory;
    $this->guzzle = $guzzle;
    $this->logger = $logger->get('stanford_ssp');

    $config = $this->configFactory->get('stanford_ssp.settings');
    $cert_path = $config->get('workgroup_api_cert');
    $key_path = $config->get('workgroup_api_key');

    if ($cert_path && is_file($cert_path) && $key_path && is_file($key_path)) {
      $this->setCert($cert_path);
      $this->setKey($key_path);
    }
  }


  /**
   * {@inheritdoc}
   */
  public function setCert(string $cert_path): void {
    $this->cert = $cert_path;
  }

  /**
   * {@inheritdoc}
   */
  public function setKey(string $key_path): void {
    $this->key = $key_path;
  }

  /**
   * {@inheritdoc}
   */
  public function getCert(): ?string {
    return $this->cert;
  }

  /**
   * {@inheritdoc}
   */
  public function getKey(): ?string {
    return $this->key;
  }

  /**
   * {@inheritdoc}
   */
  public function connectionSuccessful(): bool {
    return !empty($this->callApi('uit:sws'));
  }

  /**
   * {@inheritdoc}
   */
  public function getRolesFromAuthname($authname): array {
    $roles = [];
    if (!$this->cert || !$this->key) {
      return $roles;
    }
    $config = $this->configFactory->get('simplesamlphp_auth.settings');
    $role_population = array_filter(explode('|', $config->get('role.population') ?: ''));
    $workgroup_mappings = [];

    // Convert the simplesamlphp_auth role mapping to a nested array of
    // workgroup name => array of roles.
    foreach ($role_population as $rule) {
      [$role, $mapping] = explode(':', $rule, 2);
      // We ignore the eduEntitlement equation since its only a yes or no if the
      // user is in the group.
      $workgroup = substr($mapping, strrpos($mapping, ',') + 1);
      $workgroup_mappings[$workgroup][] = $role;
    }

    $users_workgroups = $this->getAllUserWorkgroups($authname);

    // Loop through the workgroup mappings to find the ones the current user
    // is a member of and add those roles to the list.
    foreach ($workgroup_mappings as $workgroup => $workgroup_roles) {
      if (in_array($workgroup, $users_workgroups)) {
        $roles = array_merge($roles, $workgroup_roles);
      }
    }
    return array_unique($roles);
  }

  /**
   * {@inheritdoc}
   */
  public function userInGroup(string $workgroup, string $name): bool {
    return in_array($workgroup, $this->getAllUserWorkgroups($name));
  }

  /**
   * {@inheritdoc}
   */
  public function userInAnyGroup(array $workgroups, string $name): bool {
    return !empty(array_intersect($workgroups, $this->getAllUserWorkgroups($name)));
  }

  /**
   * {@inheritdoc}
   */
  public function userInAllGroups(array $workgroups, string $name): bool {
    return count(array_intersect($workgroups, $this->getAllUserWorkgroups($name))) == count($workgroups);
  }

  /**
   * {@inheritDoc}
   */
  public function isWorkgroupValid(string $workgroup): bool {
    return !empty($this->callApi($workgroup));
  }

  /**
   * Call the workgroup api and get the response for the workgroup.
   *
   * @param string|null $workgroup
   *   Workgroup name like uit:sws.
   * @param string|null $sunet
   *   User sunetid.
   *
   * @return bool|array
   *   API response or false if fails.
   */
  protected function callApi(string $workgroup = NULL, string $sunet = NULL): ?array {
    $options = [
      'cert' => $this->getCert(),
      'ssl_key' => $this->getKey(),
      'verify' => TRUE,
      'timeout' => 5,
      'query' => [
        'type' => $workgroup ? 'workgroup' : 'user',
        'id' => $workgroup ?: $sunet,
      ],
    ];
    $api_url = Settings::get('stanford_ssp.workgroup_api', self::WORKGROUP_API);
    try {
      $result = $this->guzzle->request('GET', $api_url, $options);
      return json_decode($result->getBody(), TRUE);
    }
    catch (GuzzleException $e) {
      $this->logger->error('Unable to connect to workgroup api. @message', ['@message' => $e->getMessage()]);
    }
    return NULL;
  }

  /**
   * Get a flat list of all the given user's workgroups.
   *
   * @param string $authname
   *   Sunet id.
   *
   * @return string[]
   *   Array of the user's workgroups.
   */
  protected function getAllUserWorkgroups(string $authname): array {
    $workgroup_names = [];
    if ($user_data = $this->callApi(NULL, $authname)) {
      foreach ($user_data['results'] as $user_member) {
        $workgroup_names[] = $user_member['name'];
      }
    }
    return $workgroup_names;
  }

}
