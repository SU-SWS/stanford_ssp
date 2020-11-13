<?php

namespace Drupal\Tests\stanford_ssp\Kernel\Commands;

use Drupal\KernelTests\KernelTestBase;
use Drupal\stanford_ssp\Commands\StanfordSspCommands;
use Drupal\user\Entity\Role;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class StanfordSspCommandsTest
 *
 * @package Drupal\Tests\stanford_ssp\Kernel\Commands
 * @coversDefaultClass \Drupal\stanford_ssp\Commands\StanfordSspCommands
 */
class StanfordSspCommandsTest extends KernelTestBase {

  /**
   * {@inheritDoc}
   */
  protected static $modules = [
    'system',
    'stanford_ssp',
    'simplesamlphp_auth',
    'externalauth',
    'user',
    'stanford_ssp_test',
  ];

  /**
   * Drush command service.
   *
   * @var \Drupal\stanford_ssp\Commands\StanfordSspCommands
   */
  protected $commandObject;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setup();

    $this->installEntitySchema('user');
    $this->installEntitySchema('user_role');
    $this->installSchema('externalauth', 'authmap');
    $this->installSchema('system', ['key_value_expire', 'sequences']);

    for ($i = 0; $i < 5; $i++) {
      Role::create(['label' => "Role $i", 'id' => "role$i"])->save();
    }

    $authmap = \Drupal::service('externalauth.authmap');
    $form_builder = \Drupal::formBuilder();
    $config_factory = \Drupal::configFactory();
    $this->commandObject = new StanfordSspCommands($authmap, $form_builder, $config_factory);
    $this->commandObject->setLogger(\Drupal::logger('stanford_ssp'));
    $this->commandObject->setOutput($this->createMock(OutputInterface::class));
  }

  /**
   * Test adding a new role mapping.
   */
  public function testAddRoleMapping() {

    // Role doesn't exist.
    $this->commandObject->entitlementRole($this->randomMachineName(), $this->randomMachineName());
    $this->assertEmpty(\Drupal::config('simplesamlphp_auth.settings')
      ->get('role.population'));

    // Role doesn't exist.
    $this->commandObject->entitlementRole($this->randomMachineName(), $this->randomMachineName());
    $this->assertEmpty(\Drupal::config('simplesamlphp_auth.settings')
      ->get('role.population'));

    // Role exists.
    $workgroup = $this->randomMachineName();
    $this->commandObject->entitlementRole($workgroup, 'role1');

    $this->assertEquals("role1:eduPersonEntitlement,=,$workgroup", \Drupal::config('simplesamlphp_auth.settings')
      ->get('role.population'));
  }

  /**
   * Create a user through drush.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testAddingUser() {
    $sunet = $this->randomMachineName();
    $options = [
      'email' => $this->randomMachineName() . '@' . $this->randomMachineName() . '.com',
      'roles' => '',
    ];

    $user = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadByProperties(['name' => $sunet]);
    $this->assertEmpty($user);
    /** @var \Drupal\externalauth\Authmap $authmap */
    $authmap = \Drupal::service('externalauth.authmap');
    $this->assertFalse($authmap->getUid(strtolower($sunet), 'simplesamlphp_auth'));

    $this->commandObject->addUser($sunet, $options);

    // Make sure user entity was created.
    $user = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadByProperties(['name' => $sunet]);
    $this->assertNotEmpty($user);
    $this->assertNotFalse($authmap->getUid(strtolower($sunet), 'simplesamlphp_auth'));
  }

}
