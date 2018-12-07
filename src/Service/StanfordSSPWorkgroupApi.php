<?php

namespace Drupal\stanford_ssp\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \GuzzleHttp\ClientInterface $guzzle
   *   Http client guzzle service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Logger channel factory service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, ClientInterface $guzzle, LoggerChannelFactoryInterface $logger) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->guzzle = $guzzle;
    $this->logger = $logger->get('stanford_ssp');

    if ($this->entityTypeManager->hasDefinition('key')) {
      $this->setCertProperties();
    }
  }

  /**
   * Set the cert and key properties based on settings for the config.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function setCertProperties() {
    $config = $this->configFactory->get('stanford_ssp.settings');
    $cert_entity_id = $config->get('workgroup_api_cert');
    $key_entity_id = $config->get('workgroup_api_key');
    if (!$cert_entity_id || !$key_entity_id) {
      return;
    }

    /** @var \Drupal\key\Entity\Key[] $cert_entities */
    $cert_entities = $this->entityTypeManager->getStorage('key')
      ->loadMultiple([$cert_entity_id, $key_entity_id]);

    if (count($cert_entities) != 2) {
      return;
    }
    $this->cert = $cert_entities[$cert_entity_id]->getKeyValue();
    $this->key = $cert_entities[$key_entity_id]->getKeyValue();
  }

  /**
   * {@inheritdoc}
   */
  public function connectionSuccessful($cert, $key) {
    $response = $this->getWorkgroupApiResponse('itservices:webservices', $cert, $key);
    return $response->getStatusCode() == 200;
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
    foreach ($workgroup_mappings as $workgroup_mapping) {
      list($role, $mapping) = explode(':', $workgroup_mapping, 2);
      $workgroup = substr($mapping, strrpos($mapping, ',') + 1);
      if ($this->userInGroup($workgroup, $authname)) {
        $roles[] = $role;
      }
    }
    return $roles;
  }

  /**
   * Check if the given name is part of the workgroup provided.
   *
   * @param string $workgroup
   *   Workgroup name like itservices:webservices.
   * @param string $name
   *   User's sunetid.
   *
   * @return bool
   *   If the user is part of the group.
   */
  protected function userInGroup($workgroup, $name) {
    if ($response = $this->getWorkgroupApiResponse($workgroup, $this->cert, $this->key)) {
      $dom = new \DOMDocument();
      $dom->loadXML((string) $response->getBody());
      $xpath = new \DOMXPath($dom);

      return $xpath->query("//member[@id='$name']")->length > 0;
    }
    return FALSE;
  }

  /**
   * Call the workgroup api and get the response for the workgroup.
   *
   * @param string $workgroup
   *   Workgroup name like itservices:webservices.
   * @param string $cert
   *   Path to cert file.
   * @param string $key
   *   Path to key file.
   *
   * @return bool|\Psr\Http\Message\ResponseInterface
   *   API response or false if fails.
   */
  protected function getWorkgroupApiResponse($workgroup, $cert, $key) {
    $options = [
      'cert' => $cert,
      'ssl_key' => $key,
      'verify' => TRUE,
      'timeout' => 5,
    ];
    $base_url = $this->configFactory->get('stanford_ssp.settings')
      ->get('workgroup_api_url');
    $base_url = trim($base_url, '/') ?: 'https://workgroupsvc.stanford.edu/v1/workgroups';
    try {
      $result = $this->guzzle->request('GET', "$base_url/$workgroup", $options);
      return $result;
    }
    catch (GuzzleException $e) {
      $this->logger->error('Unable to connect to workgroup api. @message', ['@message' => $e->getMessage()]);
    }
    return FALSE;
  }

}
