<?php

namespace Drupal\Tests\stanford_ssp\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;

class LocalLoginFormTest extends KernelTestBase {

  /**
   * {@inheritDoc}
   */
  protected static $modules = [
    'system',
    'user',
    'stanford_ssp',
    'simplesamlphp_auth',
    'externalauth',
  ];

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('user_role');
    $this->installSchema('externalauth', 'authmap');
    $this->installSchema('system', ['key_value_expire', 'sequences']);

    for ($i = 0; $i < 5; $i++) {
      Role::create(['label' => "Role $i", 'id' => "role$i"])->save();
    }
    \Drupal::configFactory()
      ->getEditable('simplesamlphp_auth.settings')
      ->set('allow.default_login_roles', [])
      ->save();
  }

  /**
   * Make sure the form saves the config.
   */
  public function testLoginFormConfig() {
    $status = (bool) \Drupal::config('stanford_ssp.settings')
      ->get('hide_local_login');

    $this->assertFalse($status);
    $form = \Drupal::formBuilder()
      ->getForm('\Drupal\stanford_ssp\Form\LocalLoginForm');

    $this->assertCount(26, $form);
    $form_state = new FormState();
    $form_state->setValue('hide_local_login', TRUE);
    \Drupal::formBuilder()
      ->submitForm('\Drupal\stanford_ssp\Form\LocalLoginForm', $form_state);

    $status = (bool) \Drupal::config('stanford_ssp.settings')
      ->get('hide_local_login');
    $this->assertTrue($status);
  }

}
