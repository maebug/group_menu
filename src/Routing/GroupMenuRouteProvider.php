<?php

namespace Drupal\group_menu\Routing;

use Symfony\Component\Routing\Route;

/**
 * Provides routes for group_menu group content.
 */
class GroupMenuRouteProvider {

  /**
   * Provides the shared collection route for group node plugins.
   */
  public function getRoutes() {
    $routes = $permissions_add = $permissions_create = [];

    $plugin_id = "group_menu:menu";

    $permissions_add[] = "edit group menus";
    $permissions_create[] = "create group menus";

    // @todo Conditionally (see above) alter GroupContent info to use this path.
    $routes['entity.group_menu.group_menu_relate_menu'] = new Route('group/{group}/menu/add');
    $routes['entity.group_menu.group_menu_relate_menu']
      ->setDefaults([
        '_title' => 'Relate menu',
        '_controller' => '\Drupal\group_menu\Controller\GroupMenuController::addPage',
      ])
      ->setRequirement('_group_permission', implode('+', $permissions_add))
      ->setRequirement('_group_installed_content', $plugin_id)
      ->setOption('_group_operation_route', TRUE);

    // @todo Conditionally (see above) alter GroupContent info to use this path.
    $routes['entity.group_menu.group_menu_add_menu'] = new Route('group/{group}/menu/create');
    $routes['entity.group_menu.group_menu_add_menu']
      ->setDefaults([
        '_title' => 'Create menu',
        '_controller' => '\Drupal\group_menu\Controller\GroupMenuWizardController::addPage',
      ])
      ->setRequirement('_group_permission', implode('+', $permissions_create))
      ->setRequirement('_group_installed_content', $plugin_id)
      ->setOption('_group_operation_route', TRUE);

    return $routes;
  }

}
