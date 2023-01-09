<?php

namespace Drupal\Tests\stanford_ssp\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
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
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
  protected function setUp(): void {
    parent::setUp();
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Get the created service for testing.
   *
   * @param bool $return_account
   *   If the external auth service should return an account.
   * @param int $role_setting
   *   What the simplesamlphp_auth.settings config value is.
   * @param bool $return_attributes
   *   If the auth manager service should return attributes.
   *
   * @return \Drupal\stanford_ssp\Service\StanfordSSPDrupalAuth
   *   Constructed service.
   */
  protected function getService(bool $return_account = TRUE, int $role_setting = 1, bool $return_attributes = TRUE): StanfordSSPDrupalAuth {
    $this->removeRole = $this->randomMachineName();
    $this->addRole = $this->randomMachineName();

    $auth = $this->createMock(StanfordSSPAuthManager::class);
    $auth->method('getAttributes')
      ->willReturn($return_attributes ? ['eduPersonAffiliation' => ['staff']] : NULL);

    $config_factory = $this->getConfigFactoryStub([
      'simplesamlphp_auth.settings' => [
        'role' => ['eval_every_time' => $role_setting],
        'debug' => TRUE,
        'register_users' => TRUE,
      ],
      'stanford_ssp.settings' => [
        'use_workgroup_api' => TRUE,
      ],
    ]);
    $account = $this->createMock(UserInterface::class);
    $account->method('getRoles')->willReturn([$this->removeRole]);
    $account->method('removeRole')->willReturn(TRUE);

    $entity_storage = $this->createMock(EntityStorageInterface::class);
    $entity_storage->method('loadByProperties')->willReturn([]);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturn($entity_storage);

    $logger = $this->createMock(LoggerInterface::class);
    $external_auth = $this->createMock(ExternalAuthInterface::class);

    $external_auth->method('login')->willReturn($return_account ? $account : NULL);
    $messenger = $this->createMock(MessengerInterface::class);
    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $workgroup_api = $this->createMock(StanfordSSPWorkgroupApiInterface::class);
    $workgroup_api->method('getRolesFromAuthName')->willReturn([$this->addRole]);

    return new StanfordSSPDrupalAuth($auth, $config_factory, $entity_type_manager, $logger, $external_auth, $account, $messenger, $module_handler, $workgroup_api);
  }

  /**
   * Test role sync.
   */
  public function testRoleSync() {
    $service = $this->getService();
    $user = $service->externalLoginRegister($this->randomMachineName());
    $this->assertInstanceOf(UserInterface::class, $user);

    $service = $this->getService(FALSE);
    $user = $service->externalLoginRegister($this->randomMachineName());
    $this->assertNull($user);

    $service = $this->getService(TRUE, 2, FALSE);
    $user = $service->externalLoginRegister($this->randomMachineName());
    $this->assertInstanceOf(UserInterface::class, $user);
  }

}
