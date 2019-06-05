<?php

namespace Drupal\Tests\stanford_ssp\Kernel\Service;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\simplesamlphp_auth\Exception\SimplesamlphpAttributeException;
use Drupal\simplesamlphp_auth\Service\SimplesamlphpAuthManager;
use Drupal\stanford_ssp\Service\StanfordSSPAuthManager;
use Drupal\Tests\UnitTestCase;

/**
 * Class StanfordSSPDrupalAuthTest
 *
 * @group stanford_ssp
 */
class StanfordSSPDrupalAuthTest extends UnitTestCase {

  /**
   * Tests the user without a mail attribute gets an account created.
   */
  public function testNoAttributeError() {
    $saml_config = [
      'simplesamlphp_auth.settings' => [],
    ];
    $config_factory = $this->getConfigFactoryStub($saml_config);

    $auth_manager = new SimplesamlphpAuthManager($config_factory);
    $this->expectException(SimplesamlphpAttributeException::class);
    $auth_manager->getAttribute('mail');
  }

  /**
   * Tests the user without a mail attribute gets an account created.
   */
  public function testNoAttributeSuccess() {
    $saml_config = [
      'simplesamlphp_auth.settings' => [],
    ];
    $config_factory = $this->getConfigFactoryStub($saml_config);

    $auth_manager = new StanfordSSPAuthManager($config_factory, $this->getLoggerFactoryStub());
    $this->assertEquals('@stanford.edu', $auth_manager->getAttribute('mail'));
  }

  protected function getLoggerFactoryStub() {
    $logger_channel = $this->createMock(LoggerChannel::class);

    $logger = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger->method('get')->willReturn($logger_channel);
    return $logger;
  }

}
