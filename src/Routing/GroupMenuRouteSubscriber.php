<?php

namespace Drupal\group_menu\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Modify form for config.sync route.
 */
class GroupMenuRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('config.sync')) {
      $route->setDefault('_form', '\Drupal\group_menu\Form\GroupMenuConfigSync');
    }
  }

}
