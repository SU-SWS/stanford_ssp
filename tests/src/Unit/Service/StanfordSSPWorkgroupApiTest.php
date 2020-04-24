<?php

namespace Drupal\Tests\stanford_ssp\Unit\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\stanford_ssp\Service\StanfordSSPWorkgroupApi;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Class StanfordSSPWorkgroupApi.
 *
 * @group stanford_ssp
 * @coversDefaultClass \Drupal\stanford_ssp\Service\StanfordSSPWorkgroupApi
 */
class StanfordSSPWorkgroupApiTest extends UnitTestCase {

  /**
   * Workgroup service object.
   *
   * @var \Drupal\stanford_ssp\Service\StanfordSSPWorkgroupApi
   */
  protected $service;

  /**
   * User authname.
   *
   * @var string
   */
  protected $authname;

  /**
   * If the guzzle callback should throw an error.
   *
   * @var bool
   */
  protected $throwGuzzleException = FALSE;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->authname = $this->randomMachineName();

    $config_factory = $this->getConfigFactoryStub([
      'stanford_ssp.settings' => [
        'workgroup_api_cert' => __FILE__,
        'workgroup_api_key' => __FILE__,
        'workgroup_api_url' => '',
      ],
      'simplesamlphp_auth.settings' => [
        'role' => [
          'population' => 'valid_role:eduPersonEntitlement,=,valid:workgroup',
        ],
      ],
    ]);

    $guzzle = $this->createMock(ClientInterface::class);
    $guzzle->method('request')
      ->withAnyParameters()
      ->will($this->returnCallback([$this, 'guzzleRequestCallback']));

    $logger = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger->method('get')->willReturn($this->createMock(LoggerChannelInterface::class));
    $this->service = new StanfordSSPWorkgroupApi($config_factory, $guzzle, $logger);
  }

  public function guzzleRequestCallback($method, $url) {
    if ($this->throwGuzzleException) {
      $request = $this->createMock(RequestInterface::class);
      throw new ClientException('It broke', $request);
    }

    $guzzle_response = $this->createMock(ResponseInterface::class);
    $guzzle_response->method('getStatusCode')->willReturn(200);

    $body = "<members></members>";
    switch ($url) {
      case 'https://workgroupsvc.stanford.edu/v1/workgroups/valid:workgroup':
        $body = "<members><member id='{$this->authname}'/></members>";
        break;

      case 'https://workgroupsvc.stanford.edu/v1/workgroups/foo:bar':
        $body = "<visibility>STANFORD</visibility>";
        break;

      case 'https://workgroupsvc.stanford.edu/v1/workgroups/bar:foo':
        $body = "<workgroup><visibility>PRIVATE</visibility></workgroup>";
        break;
    }
    $guzzle_response->method('getBody')->willReturn($body);
    return $guzzle_response;
  }

  public function testSetCert() {
    $new_path = $this->randomMachineName();
    $this->service->setCert($new_path);
    $this->assertEquals($new_path, $this->service->getCert());

    $new_path = $this->randomMachineName();
    $this->service->setKey($new_path);
    $this->assertEquals($new_path, $this->service->getKey());
  }

  public function testConnection() {
    $this->assertEquals(200, $this->service->connectionSuccessful());
    $this->assertEquals(200, $this->service->connectionSuccessful());
  }

  public function testRoles() {
    $this->assertArrayEquals(['valid_role'], $this->service->getRolesFromAuthname($this->authname));
  }

  public function testUserGroups() {
    $this->assertFalse($this->service->userInAnyGroup(['invalid:workgroup'], $this->authname));
    $this->assertTrue($this->service->userInAnyGroup(['valid:workgroup'], $this->authname));

    $this->assertFalse($this->service->userInAllGroups([
      'invalid:workgroup',
      'valid:workgroup',
    ], $this->authname));
    $this->assertTrue($this->service->userInAllGroups(['valid:workgroup'], $this->authname));
  }

  /**
   * Valid workgroups are public.
   */
  public function testValidWorkgroup() {
    $this->assertTrue($this->service->isWorkgroupValid('foo:bar'));
    $this->assertFalse($this->service->isWorkgroupValid('bar:foo'));
  }

  /**
   * Guzzle exceptions don't break the service.
   */
  public function testGuzzleException() {
    $this->throwGuzzleException = TRUE;
    $this->assertFalse($this->service->isWorkgroupValid('foo:bar'));
    $this->assertFalse($this->service->userInGroup('foo', 'bar'));
  }

}
