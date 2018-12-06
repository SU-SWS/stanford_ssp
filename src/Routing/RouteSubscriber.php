<?php

namespace Drupal\stanford_ssp\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\stanford_ssp\Form\RoleSyncForm;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('simplesamlphp_auth.admin_settings_sync')) {
      $route->setDefault('_form', RoleSyncForm::class);
    }
  }

}
