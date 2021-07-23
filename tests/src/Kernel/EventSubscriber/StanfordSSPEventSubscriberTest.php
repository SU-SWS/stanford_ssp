<?php

namespace Drupal\Tests\stanford_ssp\Kernel\EventSubscriber;

use Drupal\KernelTests\KernelTestBase;
use Drupal\stanford_ssp\EventSubscriber\StanfordSSPEventSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

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
    $listener = new StanfordSSPEventSubscriber(\Drupal::configFactory(), \Drupal::currentUser());
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
  }

}
