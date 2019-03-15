<?php

namespace Drupal\stanford_ssp\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\simplesamlphp_auth\Service\SimplesamlphpAuthManager;
use SimpleSAML\Auth\Simple;
use SimpleSAML_Configuration;

/**
 * Class StanfordSSPAuthManager
 *
 * @package Drupal\stanford_ssp\Service
 */
class StanfordSSPAuthManager extends SimplesamlphpAuthManager {

  /**
   * Stanford SSP configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $stanfordConfig;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, Simple $instance = NULL, SimpleSAML_Configuration $config = NULL) {
    parent::__construct($config_factory, $instance, $config);
    $this->stanfordConfig = $config_factory->get('stanford_ssp');
  }

  /**
   * {@inheritdoc}
   *
   * Override the parent method so that we can have a primary mail attribute
   * and a fallback. This will help with sunet aliases. But if a user does not
   * have the primary mail attribute (probably eduPersonPrincipalName) then we
   * use the fallback.
   */
  public function getDefaultEmail() {
    if ($mail = $this->getAttribute($this->config->get('mail_attr'))) {
      return $mail;
    }

    return $this->getAttribute($this->stanfordConfig->get('mail_attr_fallback'));
  }

}
