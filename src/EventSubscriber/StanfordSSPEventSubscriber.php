<?php

namespace Drupal\stanford_ssp\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
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
   * StanfordSSPEventSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\Core\Session\AccountProxyInterface $userAccount
   *   Current user object.
   * @param \Drupal\Core\Path\PathMatcherInterface $pathMatcher
   *   Path matcher service.
   * @param \Drupal\Core\Path\CurrentPathStack $currentPath
   *   Current path service.
   * @param \Drupal\path_alias\AliasManagerInterface $aliasManager
   *   Alias manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, protected AccountProxyInterface $userAccount, protected PathMatcherInterface $pathMatcher, protected CurrentPathStack $currentPath, protected AliasManagerInterface $aliasManager) {
    $this->samlConfig = $config_factory->get('simplesamlphp_auth.settings');
    $this->stanfordConfig = $config_factory->get('stanford_ssp.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[KernelEvents::REQUEST][] = ['requestHandler'];
    $events[KernelEvents::RESPONSE][] = ['responseHandler'];
    return $events;
  }

  /**
   * Redirect user create page if local login is disabled.
   */
  public function requestHandler(RequestEvent $event) {
    $request = $event->getRequest();
    try {
      if (
        $request->attributes->get('_route') == 'user.admin_create' &&
        $this->stanfordConfig->get('hide_local_login')
      ) {
        $destination = Url::fromRoute('stanford_ssp.create_user')->toString();
        $event->setResponse(new RedirectResponse($destination));
      }
    }
    catch (\Throwable $e) {
      // Safety catch to avoid errors.
    }
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
      $this->userAccount->isAnonymous() &&
      $this->redirectPath($event->getRequest())
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

  /**
   * Check if the current path is excluded by the settings for redirecting.
   *
   * The logic of this function was taken from the RequestPath condition plugin.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Current request stack.
   *
   * @return bool
   *   If the current page should be redirected.
   */
  protected function redirectPath(Request $request): bool {
    $exclude_paths = implode("\n", $this->stanfordConfig->get('exclude_redirect') ?? []);

    $pages = mb_strtolower($exclude_paths);
    if (!$pages) {
      return TRUE;
    }
    // Compare the lowercase path alias (if any) and internal path.
    $path = $this->currentPath->getPath($request);

    // Do not trim a trailing slash if that is the complete path.
    $path = $path === '/' ? $path : rtrim($path, '/');
    $path_alias = mb_strtolower($this->aliasManager->getAliasByPath($path));

    return !($this->pathMatcher->matchPath($path_alias, $pages) || (($path != $path_alias) && $this->pathMatcher->matchPath($path, $pages)));
  }

}
