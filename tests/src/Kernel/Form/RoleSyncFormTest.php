<?php

namespace Drupal\Tests\stanford_ssp\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\stanford_ssp\Service\StanfordSSPWorkgroupApiInterface;
use Drupal\user\Entity\Role;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class RoleSyncFormTest.
 *
 * @package Drupal\Tests\stanford_ssp\Unit\Form
 * @coversDefaultClass \Drupal\stanford_ssp\Form\RoleSyncForm
 */
class RoleSyncFormTest extends KernelTestBase {

  /**
   * The form namespace being tested.
   *
   * @var string
   */
  protected $formId = '\Drupal\stanford_ssp\Form\RoleSyncForm';

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
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('user_role');
    $this->installSchema('externalauth', 'authmap');
    $this->installSchema('system', ['key_value_expire', 'sequences']);

    $guzzle = $this->createMock(ClientInterface::class);
    $guzzle->method('request')->will($this->returnCallback([
      $this,
      'guzzleCallback',
    ]));
    \Drupal::getContainer()->set('http_client', $guzzle);

    for ($i = 0; $i < 5; $i++) {
      Role::create(['label' => "Role $i", 'id' => "role$i"])->save();
    }
    \Drupal::configFactory()
      ->getEditable('simplesamlphp_auth.settings')
      ->set('role.population', $this->randomMachineName() . 'role1:attribute_name,=,value|role2:attribute_name2,=,another_value')
      ->save();
  }

  public function guzzleCallback($method, $url, $options) {
    $response = $this->createMock(ResponseInterface::class);
    $response->method('getBody')->willReturn(json_encode(['results' => []]));

    $response->method('getStatusCode')->willReturn(200);
    return $response;
  }

  /**
   * Run tests against the form structure, callbacks, and submit.
   *
   * @throws \Drupal\Core\Form\EnforcedResponseException
   * @throws \Drupal\Core\Form\FormAjaxException
   */
  public function testFormBuild() {
    $form = \Drupal::formBuilder()
      ->getForm($this->formId);
    $this->assertCount(26, $form);

    $form_state = new FormState();
    $form = \Drupal::formBuilder()
      ->buildForm($this->formId, $form_state);
    $this->assertNotEmpty($form_state->getFormObject()
      ->addMapping($form, $form_state));

    $new_workgroup = strtolower($this->randomMachineName());
    $form_state->setUserInput([
      'role_population' => [
        'add' => [
          'attribute' => '',
          'role_id' => 'role2',
          'workgroup' => $new_workgroup,
        ],
      ],
    ]);
    $form_state->getFormObject()->addMappingCallback($form, $form_state);
    $form_state->setMethod('GET');
    $form = \Drupal::formBuilder()
      ->rebuildForm($this->formId, $form_state);

    $this->assertArrayHasKey("role2:eduPersonEntitlement,=,$new_workgroup", $form['user_info']['role_population']);

    $form_state->setTriggeringElement(['#mapping' => "role2:eduPersonEntitlement,=,$new_workgroup"]);
    $form_state->getFormObject()->removeMappingCallback($form, $form_state);
    $form = \Drupal::formBuilder()
      ->rebuildForm($this->formId, $form_state);

    $this->assertArrayNotHasKey("role2:eduPersonEntitlement,=,$new_workgroup", $form['user_info']['role_population']);

    $this->runFormSubmit();
  }

  /**
   * Run tests on the form validation and submit.
   */
  protected function runFormSubmit() {
    $form_state = new FormState();
    $form_state->setValues([
      'unique_id' => $this->randomMachineName(),
      'user_name' => $this->randomMachineName(),
      'use_workgroup_api' => FALSE,
      'workgroup_api_cert' => __DIR__ . '/test.crt',
    ]);

    // Not configured to use workgroup api.
    \Drupal::formBuilder()->submitForm($this->formId, $form_state);
    $this->assertFalse($form_state::hasAnyErrors());
    $this->assertEmpty(\Drupal::config('stanford_ssp.settings')
      ->get('workgroup_api_cert'));

    // Cert set, but key not set.
    $form_state->setValue('use_workgroup_api', TRUE);
    \Drupal::formBuilder()->submitForm($this->formId, $form_state);
    $this->assertTrue($form_state::hasAnyErrors());

    // Cert and keys are the same error.
    $form_state->clearErrors();
    $form_state->setValue('workgroup_api_key', $form_state->getValue('workgroup_api_cert'));
    \Drupal::formBuilder()->submitForm($this->formId, $form_state);
    $this->assertTrue($form_state::hasAnyErrors());

    // Key path is not a file error.
    $form_state->clearErrors();
    $form_state->setValue('workgroup_api_key', $this->randomMachineName());
    \Drupal::formBuilder()->submitForm($this->formId, $form_state);
    $this->assertTrue($form_state::hasAnyErrors());

    // Cert path is not a file error.
    $form_state->clearErrors();
    $form_state->setValue('workgroup_api_key', __DIR__ . '/test.key');
    $form_state->setValue('workgroup_api_cert', $this->randomMachineName());
    \Drupal::formBuilder()->submitForm($this->formId, $form_state);
    $this->assertTrue($form_state::hasAnyErrors());

    // Workgroup api connection unsuccessful error.
    $this->setWorkgroupApiMock();
    $form_state->clearErrors();
    $form_state->setValue('workgroup_api_key', __DIR__ . '/test.key');
    $form_state->setValue('workgroup_api_cert', __DIR__ . '/test.crt');
    \Drupal::formBuilder()->submitForm($this->formId, $form_state);
    $this->assertTrue($form_state::hasAnyErrors());

    // Workgroup api connection successful config check.
    $this->setWorkgroupApiMock(TRUE);
    $form_state->clearErrors();
    \Drupal::formBuilder()->submitForm($this->formId, $form_state);
    $this->assertFalse($form_state::hasAnyErrors());

    $this->assertEquals(__DIR__ . '/test.crt', \Drupal::config('stanford_ssp.settings')
      ->get('workgroup_api_cert'));
  }

  /**
   * Submitting the form with values in the fields should add them to config.
   */
  public function testAddingNewWorkgroup() {
    \Drupal::configFactory()
      ->getEditable('simplesamlphp_auth.settings')
      ->set('role.population', '')
      ->save();

    $form_state = new FormState();
    $form_state->setValues([
      'unique_id' => 'uid',
      'user_name' => 'uid',
      'role_population' => [
        'add' => [
          'role_id' => 'role1',
          'attribute' => '',
          'workgroup' => 'foo:bar',
        ],
      ],
    ]);
    \Drupal::formBuilder()->submitForm($this->formId, $form_state);
    $this->assertEquals('role1:eduPersonEnttitlement,=,foo:bar', \Drupal::config('simplesamlphp_auth.settings')
      ->get('role.population'));
  }

  /**
   * Set the workgroup mock object to the drupal container.
   *
   * @param bool $successful_connection
   *   If the connection should be successful.
   */
  protected function setWorkgroupApiMock(bool $successful_connection = FALSE) {
    $workgroup_api = $this->createMock(StanfordSSPWorkgroupApiInterface::class);
    $workgroup_api->method('connectionSuccessful')
      ->willReturn($successful_connection);
    \Drupal::getContainer()->set('stanford_ssp.workgroup_api', $workgroup_api);
  }

}
