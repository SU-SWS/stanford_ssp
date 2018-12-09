<?php

namespace Drupal\stanford_ssp\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

Class StanfordSSPEventSubscriber implements EventSubscriberInterface {

  /**
   * A config object with saml settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $samlConfig;

  /**
   * A config object with stanford_ssp settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $stanfordConfig;

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
    $this->samlConfig = $config_factory->get('simplesamlphp_auth.settings');
    $this->stanfordConfig = $config_factory->get('stanford_ssp.settings');
    $this->userAccount = $user_account;
  }

  /**
   * {@inheritdoc}
   */
  static public function getSubscribedEvents() {
    $events[KernelEvents::REQUEST] = ['requestHandler'];
    $events[KernelEvents::RESPONSE] = ['responseHandler'];
    return $events;
  }

  /**
   * Redirect users to SAML endpoint if no local login is available.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   */
  public function requestHandler(GetResponseEvent $event) {
    $url = Url::fromUserInput($event->getRequest()->getPathInfo());

    if (
      $this->samlConfig->get('activate') &&
      $this->stanfordConfig->get('hide_local_login') &&
      $url->isRouted() &&
      $url->getRouteName() == 'user.login'
    ) {
      $url = Url::fromRoute('simplesamlphp_auth.saml_login');
      $response = new RedirectResponse($url->toString(), 301);
      $response->send();
    }
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

      // Redirect anonymous users to login portal.
      $url = Url::fromRoute('user.login', [], ['query' => ['destination' => $origin]]);
      $response = new RedirectResponse($url->toString());
      $response->send();
    }
  }

}
