<?php

namespace Drupal\Tests\stanford_ssp\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;

/**
 * Class AuthorizationsFormTest
 *
 * @package Drupal\Tests\stanford_ssp\Kernel\Form
 * @coversDefaultClass \Drupal\stanford_ssp\Form\AuthorizationsForm
 */
class AuthorizationsFormTest extends KernelTestBase {

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
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setup();
    \Drupal::configFactory()->getEditable('stanford_ssp.settings')
      ->set('allowed.groups', [])
      ->set('allowed.users', [])
      ->set('allowed.affiliations', [])
      ->save();
  }

  public function testForm() {
    $form = \Drupal::formBuilder()
      ->getForm('\Drupal\stanford_ssp\Form\AuthorizationsForm');
    $this->assertCount(29, $form);
    $form_state = new FormState();
    $form_state->setValues([
      'restriction' => 'restrict',
    ]);
    \Drupal::formBuilder()
      ->submitForm('\Drupal\stanford_ssp\Form\AuthorizationsForm', $form_state);
    $this->assertTrue($form_state::hasAnyErrors());
    $this->assertNotEmpty($form_state->getError(['#parents' => ['restriction']]));

    $form_state->clearErrors();
    $form_state->setValues([
      'allowed_affiliations' => ['student', 'staff'],
      'restriction' => 'restrict',
      'allowed_groups' => 'group1,group2',
      'allowed_users' => 'user1,user2',
    ]);
    \Drupal::formBuilder()
      ->submitForm('\Drupal\stanford_ssp\Form\AuthorizationsForm', $form_state);

    $this->assertTrue(in_array('group1', \Drupal::config('stanford_ssp.settings')->get('allowed.groups')));
    $this->assertTrue(in_array('group2', \Drupal::config('stanford_ssp.settings')->get('allowed.groups')));
    $this->assertTrue(in_array('user1', \Drupal::config('stanford_ssp.settings')->get('allowed.users')));
    $this->assertTrue(in_array('user2', \Drupal::config('stanford_ssp.settings')->get('allowed.users')));
    $this->assertTrue(in_array('student', \Drupal::config('stanford_ssp.settings')->get('allowed.affiliations')));
  }

}
