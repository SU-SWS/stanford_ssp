<?php

namespace Drupal\stanford_ssp\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Kernel event subscriber to redirect anonymous users to saml login.
 *
 * @package Drupal\stanford_ssp\EventSubscriber
 */
class StanfordSSPEventSubscriber implements EventSubscriberInterface {

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
  public static function getSubscribedEvents() {
    $events = [];
    $events[KernelEvents::RESPONSE][] = ['responseHandler'];
    return $events;
  }

  /**
   * Upon getting the kernel response, redirect 403 pages appropriately.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   Response event object..
   */
  public function responseHandler(ResponseEvent $event) {

    if (
      $event->getResponse()->getStatusCode() == Response::HTTP_FORBIDDEN &&
      $event->getRequestType() == HttpKernelInterface::MASTER_REQUEST &&
      $this->userAccount->isAnonymous()
    ) {
      $origin = $event->getRequest()->getPathInfo();
      $query = $event->getRequest()->getQueryString();
      $destination = trim("$origin?$query", '/?');

      // Redirect anonymous users to login portal.
      $url = Url::fromRoute('user.login', [], ['query' => ['destination' => $destination]]);
      if ($this->samlConfig->get('activate') && $this->stanfordConfig->get('hide_local_login')) {
        global $base_url;
        $destination = "$base_url/$destination";
        $url = Url::fromRoute('simplesamlphp_auth.saml_login', [], ['query' => ['ReturnTo' => $destination]]);
      }

      $response = new RedirectResponse($url->toString());
      $event->setResponse($response);
    }
  }

}
