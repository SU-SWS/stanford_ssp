<?php

namespace Drupal\stanford_ssp\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\simplesamlphp_auth\Exception\SimplesamlphpAttributeException;
use Drupal\simplesamlphp_auth\Service\SimplesamlphpAuthManager;
use SimpleSAML\Auth\Simple;
use SimpleSAML_Configuration;

/**
 * Class StanfordSSPAuthManager to decorate auth manager service.
 *
 * @package Drupal\stanford_ssp\Service
 */
class StanfordSSPAuthManager extends SimplesamlphpAuthManager {

  /**
   * Logger channel service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * {@inheritDoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, Simple $instance = NULL, SimpleSAML_Configuration $config = NULL) {
    parent::__construct($config_factory, $instance, $config);
    $this->logger = $logger_factory->get('stanford_ssp');
  }

  /**
   * {@inheritDoc}
   *
   * Alter the parent method by preventing a complete break when an attribute is
   * not available.
   */
  public function getAttribute($attribute) {
    try {
      return parent::getAttribute($attribute);
    }
    catch (SimplesamlphpAttributeException $e) {
      $this->logger->error($e);

      // If the `mail` attribute isn't available, build one from the uid.
      // Authname is normally the `uid` attribute which is the SUNetId.
      if ($attribute == 'mail') {
        return $this->getAuthname() . '@stanford.edu';
      }
    }
  }

}
