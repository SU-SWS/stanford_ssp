<?php

namespace Drupal\Tests\stanford_ssp\Unit\Service;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\simplesamlphp_auth\Exception\SimplesamlphpAttributeException;
use Drupal\simplesamlphp_auth\Service\SimplesamlphpAuthManager;
use Drupal\stanford_ssp\Service\StanfordSSPAuthManager;
use Drupal\Tests\UnitTestCase;
use SimpleSAML\Auth\Simple;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class StanfordSSPDrupalAuthTest
 *
 * @group stanford_ssp
 * @coversDefaultClass \Drupal\stanford_ssp\Service\StanfordSSPAuthManager
 */
class StanfordSSPAuthManagerTest extends UnitTestCase {

  /**
   * Tests the user without a mail attribute gets an account created.
   */
  public function testNoAttributeError() {
    $saml_config = [
      'simplesamlphp_auth.settings' => [
        'auth_source' => $this->randomMachineName(),
      ],
    ];
    $config_factory = $this->getConfigFactoryStub($saml_config);

    $account = $this->createMock(AccountProxyInterface::class);
    $context = $this->createMock(AdminContext::class);
    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $stack = $this->createMock(RequestStack::class);
    $messenger = $this->createMock(MessengerInterface::class);

    $instance = $this->createMock(Simple::class);
    $instance->method('getAttributes')->willReturn([]);

    $auth_manager = new SimplesamlphpAuthManager($config_factory, $account, $context, $module_handler, $stack, $messenger, $instance);
    $this->expectException(SimplesamlphpAttributeException::class);
    $auth_manager->getAttribute('mail');
  }

  /**
   * Tests the user without a mail attribute gets an account created.
   */
  public function testNoAttributeSuccess() {
    $saml_config = [
      'simplesamlphp_auth.settings' => [
        'auth_source' => $this->randomMachineName(),
      ],
    ];
    $config_factory = $this->getConfigFactoryStub($saml_config);

    $account = $this->createMock(AccountProxyInterface::class);
    $context = $this->createMock(AdminContext::class);
    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $stack = $this->createMock(RequestStack::class);
    $messenger = $this->createMock(MessengerInterface::class);

    $instance = $this->createMock(Simple::class);
    $instance->method('getAttributes')->willReturn([]);

    $auth_manager = new StanfordSSPAuthManager($config_factory, $account, $context, $module_handler, $stack, $messenger, $this->getLoggerFactoryStub(), $instance);
    $this->assertEquals('@stanford.edu', $auth_manager->getAttribute('mail'));
  }

  /**
   * Get a logger factory mock object.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   */
  protected function getLoggerFactoryStub() {
    $logger_channel = $this->createMock(LoggerChannel::class);

    $logger = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger->method('get')->willReturn($logger_channel);
    return $logger;
  }

}
