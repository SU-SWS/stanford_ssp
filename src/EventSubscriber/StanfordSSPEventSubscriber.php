<?php

namespace Drupal\stanford_ssp\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

Class StanfordSSPEventSubscriber implements EventSubscriberInterface {

  /**
   * A config object with saml settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $userAccount;

  /**
   * StanfordSSPEventSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\Core\Session\AccountProxyInterface $user_account
   *   Current user object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountProxyInterface $user_account) {
    $this->config = $config_factory->get('simplesamlphp_auth.settings');
    $this->userAccount = $user_account;
  }

  /**
   * {@inheritdoc}
   */
  static public function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE] = ['responseHandler'];
    return $events;
  }

  /**
   * Upon getting the kernel response, redirect 403 pages appropriately.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   Response event object..
   */
  public function responseHandler(FilterResponseEvent $event) {
    if ($event->getResponse()->getStatusCode() != Response::HTTP_FORBIDDEN) {
      return;
    }

    // Disable cache on 403 pages to allow redirect responses to function
    // correctly.
    $response = new Response($event->getResponse()
      ->getContent(), 403, ['Cache-Control' => 'no-cache']);
    $event->setResponse($response);

    if (
      $event->getRequestType() == HttpKernelInterface::MASTER_REQUEST &&
      $this->userAccount->isAnonymous()
    ) {
      $origin = $event->getRequest()->getPathInfo();


      if ($this->config->get('activate') && $this->config->get('hide_local_login')) {
        $url = Url::fromRoute('simplesamlphp_auth.saml_login', [], ['query' => ['destination' => trim($origin, '/')]]);
      }
      else {
        $url = Url::fromRoute('user.login', [], ['query' => ['destination' => $origin]]);
      }

      // Redirect anonymous users to login portal.
      $response = new RedirectResponse($url->toString());
      $response->send();
    }
  }

}
