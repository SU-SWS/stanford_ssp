<?php

namespace Drupal\stanford_ssp\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Workgroup api service class to connect to the API.
 *
 * @package Drupal\stanford_ssp\Service
 */
class StanfordSSPWorkgroupApi implements StanfordSSPWorkgroupApiInterface {

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
   * Keyed array of workgroup responses with the group as the key.
   *
   * @var array
   */
  protected $workgroupResponses;

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
  public function setCert($cert_path) {
    $this->cert = $cert_path;
  }

  /**
   * {@inheritdoc}
   */
  public function setKey($key_path) {
    $this->key = $key_path;
  }

  /**
   * {@inheritdoc}
   */
  public function getCert() {
    return $this->cert;
  }

  /**
   * {@inheritdoc}
   */
  public function getKey() {
    return $this->key;
  }

  /**
   * {@inheritdoc}
   */
  public function connectionSuccessful() {
    $response = $this->getWorkgroupApiResponse('uit:sws');
    return $response && $response->getStatusCode() == 200;
  }

  /**
   * {@inheritdoc}
   */
  public function getRolesFromAuthname($authname) {
    $roles = [];
    if (!$this->cert || !$this->key) {
      return $roles;
    }
    $config = $this->configFactory->get('simplesamlphp_auth.settings');;
    $workgroup_mappings = array_filter(explode('|', $config->get('role.population') ?: ''));

    // Loop through each workgroup mapping and find out if the given user exists
    // within each group.
    foreach ($workgroup_mappings as $workgroup_mapping) {
      [$role, $mapping] = explode(':', $workgroup_mapping, 2);

      // We ignore the eduEntitlement equation since its only a yes or no if the
      // user is in the group.
      $workgroup = substr($mapping, strrpos($mapping, ',') + 1);
      if ($this->userInGroup($workgroup, $authname)) {
        $roles[] = $role;
      }
    }
    return $roles;
  }

  /**
   * {@inheritdoc}
   */
  public function userInGroup($workgroup, $name) {
    if ($response = $this->getWorkgroupApiResponse($workgroup)) {
      $dom = new \DOMDocument();
      $dom->loadXML((string) $response->getBody());
      $xpath = new \DOMXPath($dom);

      // Use xpath to find if the sunetid is one of the members.
      return $xpath->query("//members/member[@id='$name']")->length > 0;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function userInAnyGroup(array $workgroups, $name) {
    foreach ($workgroups as $workgroup) {
      if ($this->userInGroup($workgroup, $name)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function userInAllGroups(array $workgroups, $name) {
    foreach ($workgroups as $workgroup) {
      if (!$this->userInGroup($workgroup, $name)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function isWorkgroupValid($workgroup) {
    $response = $this->getWorkgroupApiResponse($workgroup);
    $dom = new \DOMDocument();
    $dom->loadXML((string) $response->getBody());
    $xpath = new \DOMXPath($dom);
    if ($xpath->query('//visibility')->item(0)->nodeValue != 'PRIVATE') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Call the workgroup api and get the response for the workgroup.
   *
   * @param string $workgroup
   *   Workgroup name like uit:sws.
   *
   * @return bool|\Psr\Http\Message\ResponseInterface
   *   API response or false if fails.
   */
  protected function getWorkgroupApiResponse($workgroup) {
    // We've already called the API for this group, use that result.
    if (isset($this->workgroupResponses[$workgroup])) {
      return $this->workgroupResponses[$workgroup];
    }
    $options = [
      'cert' => $this->getCert(),
      'ssl_key' => $this->getKey(),
      'verify' => TRUE,
      'timeout' => 5,
    ];

    $base_url = $this->configFactory->get('stanford_ssp.settings')
      ->get('workgroup_api_url');
    $base_url = trim($base_url, '/') ?: 'https://workgroupsvc.stanford.edu/v1/workgroups';

    try {
      $result = $this->guzzle->request('GET', "$base_url/$workgroup", $options);
      $this->workgroupResponses[$workgroup] = $result;
      return $result;
    }
    catch (GuzzleException $e) {
      $this->logger->error('Unable to connect to workgroup api. @message', ['@message' => $e->getMessage()]);
    }
    return FALSE;
  }

}
