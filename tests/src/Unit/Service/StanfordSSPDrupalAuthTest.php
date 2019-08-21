<?php

namespace Drupal\Tests\stanford_ssp\Unit\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\stanford_ssp\Service\StanfordSSPAuthManager;
use Drupal\stanford_ssp\Service\StanfordSSPDrupalAuth;
use Drupal\stanford_ssp\Service\StanfordSSPWorkgroupApiInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;

/**
 * Class StanfordSSPDrupalAuthTest
 *
 * @group stanford_ssp
 * @coversDefaultClass \Drupal\stanford_ssp\Service\StanfordSSPDrupalAuth
 */
class StanfordSSPDrupalAuthTest extends UnitTestCase {

  /**
   * @var \Drupal\stanford_ssp\Service\StanfordSSPDrupalAuth
   */
  protected $service;

  /**
   * @var string
   */
  protected $removeRole;

  /**
   * @var string
   */
  protected $addRole;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->removeRole = $this->randomMachineName();
    $this->addRole = $this->randomMachineName();

    $auth = $this->createMock(StanfordSSPAuthManager::class);
    $auth->method('getAttributes')->willReturn(['eduPersonAffiliation' => ['staff']]);

    $config_factory = $this->getConfigFactoryStub([
      'simplesamlphp_auth.settings' => [
        'role' => ['eval_every_time' => 1],
        'debug' => TRUE,
      ],
      'stanford_ssp.settings' => [
        'use_workgroup_api' => TRUE,
      ],
    ]);
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $logger = $this->createMock(LoggerInterface::class);
    $external_auth = $this->createMock(ExternalAuthInterface::class);

    $account = $this->createMock(UserInterface::class);
    $account->method('getRoles')->willReturn([$this->removeRole]);
    $account->method('removeRole')->willReturn(TRUE);

    $external_auth->method('login')->willReturn($account);
    $messenger = $this->createMock(MessengerInterface::class);
    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $workgroup_api = $this->createMock(StanfordSSPWorkgroupApiInterface::class);
    $workgroup_api->method('getRolesFromAuthName')->willReturn([$this->addRole]);

    $this->service = new StanfordSSPDrupalAuth($auth, $config_factory, $entity_type_manager, $logger, $external_auth, $account, $messenger, $module_handler, $workgroup_api);
  }

  /**
   * Test role sync.
   */
  public function testRoleSync() {
    $user = $this->service->externalLoginRegister($this->randomMachineName());
    $this->assertInstanceOf(UserInterface::class, $user);
  }

}
