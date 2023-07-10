<?php

namespace Drupal\Tests\stanford_ssp\Kernel\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Mail\MailManager;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Class AddUserFormTest
 *
 * @package Drupal\Tests\stanford_ssp\Unit\Form
 * @coversDefaultClass \Drupal\stanford_ssp\Form\AddUserForm
 */
class AddUserFormTest extends KernelTestBase {

  /**
   * {@inheritDoc}
   */
  protected static $modules = [
    'system',
    'user',
    'stanford_ssp',
    'simplesamlphp_auth',
    'externalauth',
    'path_alias',
  ];

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $existingUser;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('user_role');
    $this->installSchema('externalauth', 'authmap');
    $this->installSchema('system', ['key_value_expire', 'sequences']);

    for ($i = 0; $i < 5; $i++) {
      Role::create(['label' => "Role $i", 'id' => "role$i"])->save();
    }
    $this->existingUser = User::create([
      'name' => $this->randomMachineName(),
      'pass' => \Drupal::service('password_generator')->generate(),
      'mail' => $this->randomMachineName() . '@' . $this->randomMachineName() . '.com',
    ]);
    $this->existingUser->save();
    \Drupal::service('externalauth.authmap')
      ->save($this->existingUser, 'simplesamlphp_auth', strtolower($this->existingUser->getAccountName()));

    $mail_manager = $this->createMock(MailManager::class);
    \Drupal::getContainer()->set('plugin.manager.mail', $mail_manager);

    \Drupal::configFactory()
      ->getEditable('simplesamlphp_auth.settings')
      ->set('allow.set_drupal_pwd', TRUE)
      ->save();
  }

  public function testAvailableRoles() {
    $form = \Drupal::formBuilder()
      ->getForm('\Drupal\stanford_ssp\Form\AddUserForm');
    $this->assertCount(32, $form);

    $this->assertCount(5, $form['roles']['#options']);
    $this->turnOnRoleDelegation();

    $form = \Drupal::formBuilder()
      ->getForm('\Drupal\stanford_ssp\Form\AddUserForm');
    $this->assertCount(2, $form['roles']['#options']);
  }

  public function testFormSubmit() {
    // Authmap already has the current user registered.
    $form_state = new FormState();
    $form_state->setValue('sunetid', $this->existingUser->getAccountName());
    \Drupal::formBuilder()
      ->submitForm('\Drupal\stanford_ssp\Form\AddUserForm', $form_state);
    $this->assertNotEmpty($form_state->getError(['#parents' => ['sunetid']]));
    $this->assertCount(1, $form_state->getErrors());

    // Name is already assigned to a user.
    $form_state = new FormState();
    $form_state->setValue('sunetid', strtolower($this->randomMachineName()));
    $form_state->setValue('name', $this->existingUser->getAccountName());
    \Drupal::formBuilder()
      ->submitForm('\Drupal\stanford_ssp\Form\AddUserForm', $form_state);
    $this->assertNotEmpty($form_state->getError(['#parents' => ['name']]));
    $this->assertGreaterThanOrEqual(1, count($form_state->getErrors()));

    // Incorrect formatted email.
    $form_state = new FormState();
    $form_state->setValue('sunetid', strtolower($this->randomMachineName()));
    $form_state->setValue('email', $this->randomMachineName() . '  ' . $this->randomMachineName());
    \Drupal::formBuilder()
      ->submitForm('\Drupal\stanford_ssp\Form\AddUserForm', $form_state);
    $this->assertNotEmpty($form_state->getError(['#parents' => ['email']]));
    $this->assertGreaterThanOrEqual(1, count($form_state->getErrors()));

    // Email is already assigned to a user.
    $form_state = new FormState();
    $form_state->setValue('sunetid', strtolower($this->randomMachineName()));
    $form_state->setValue('email', $this->existingUser->getEmail());
    \Drupal::formBuilder()
      ->submitForm('\Drupal\stanford_ssp\Form\AddUserForm', $form_state);
    $this->assertNotEmpty($form_state->getError(['#parents' => ['email']]));
    $this->assertGreaterThanOrEqual(1, count($form_state->getErrors()));

    // Poorly formed sunetid
    $form_state = new FormState();
    $form_state->setValue('sunetid', strtolower($this->randomMachineName()). ' foo');
    \Drupal::formBuilder()
      ->submitForm('\Drupal\stanford_ssp\Form\AddUserForm', $form_state);
    $this->assertNotEmpty($form_state->getError(['#parents' => ['email']]));
    $this->assertGreaterThanOrEqual(1, count($form_state->getErrors()));

    // No errors submit.
    $form_state = new FormState();
    $form_state->setValue('sunetid', strtolower($this->randomMachineName()));
    $form_state->setValue('roles', []);
    $form_state->setValue('notify', TRUE);
    \Drupal::formBuilder()
      ->submitForm('\Drupal\stanford_ssp\Form\AddUserForm', $form_state);
    $this->assertFalse($form_state::hasAnyErrors());
  }

  protected function turnOnRoleDelegation() {
    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $module_handler->method('moduleExists')->willReturn(TRUE);
    \Drupal::getContainer()->set('module_handler', $module_handler);
    \Drupal::getContainer()->set('delegatable_roles', new TestDelegatableRoles());
  }

}

class TestDelegatableRoles {

  public function getAssignableRoles() {
    $all_roles = user_role_names(TRUE);
    return array_slice($all_roles, 0, 2);
  }

}
