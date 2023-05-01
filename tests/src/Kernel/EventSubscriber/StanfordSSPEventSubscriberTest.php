<?php

namespace Drupal\Tests\stanford_ssp\Kernel\EventSubscriber;

use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\stanford_ssp\EventSubscriber\StanfordSSPEventSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Route;

/**
 * Class StanfordSSPEventSubscriberTest
 *
 * @package Drupal\Tests\stanford_ssp\Kernel\EventSubscriber
 * @coversDefaultClass \Drupal\stanford_ssp\EventSubscriber\StanfordSSPEventSubscriber
 */
class StanfordSSPEventSubscriberTest extends KernelTestBase {

  /**
   * {@inheritDoc}
   */
  protected static $modules = [
    'system',
    'stanford_ssp',
    'simplesamlphp_auth',
    'externalauth',
    'user',
    'path_alias',
  ];

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setup();
    \Drupal::configFactory()
      ->getEditable('simplesamlphp_auth.settings')
      ->set('activate', TRUE)
      ->save();
    \Drupal::configFactory()
      ->getEditable('stanford_ssp.settings')
      ->set('hide_local_login', TRUE)
      ->set('exclude_redirect', ['/foo-bar/*'])
      ->save();
  }

  /**
   * Check that 403 responses get redirected to log in.
   */
  public function testKernelResponse() {
    $request = new Request();
    $dispatcher = new EventDispatcher();

    $request->headers
      ->set('HOST', 'example.com');

    $listener = \Drupal::service('stanford_ssp.event_subscriber');
    $dispatcher->addListener(KernelEvents::RESPONSE, [
      $listener,
      'responseHandler',
    ]);

    $response = new Response('', Response::HTTP_FORBIDDEN);
    $kernel = $this->createMock('Symfony\\Component\\HttpKernel\\HttpKernelInterface');
    $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);
    $dispatcher->dispatch(KernelEvents::RESPONSE, $event);

    $target_url = $event->getResponse()->getTargetUrl();
    $this->assertStringContainsString('/saml_login?ReturnTo=http', $target_url);
    $this->assertEquals(302, $event->getResponse()->getStatusCode());

    $request = Request::create('/foo-bar/baz');
    $response = new Response('', Response::HTTP_FORBIDDEN);
    $kernel = $this->createMock('Symfony\\Component\\HttpKernel\\HttpKernelInterface');
    $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);
    $dispatcher->dispatch(KernelEvents::RESPONSE, $event);

    /** @var \Symfony\Component\HttpFoundation\Response $response */
    $response = $event->getResponse();
    $this->assertEquals(403, $response->getStatusCode());
  }

}
