<?php

namespace Drupal\group_menu\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_menu\Entity\GroupMenu;
use Drupal\system\MenuInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to for group node add forms.
 */
class GroupMenuDeleteAccessCheck implements AccessInterface {

  /**
   * Checks access to the group node creation wizard.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to create the node in.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, MenuInterface $menu) {
    // If access is not required for this route.
    if (!$route->getRequirement('_group_menu_delete_access') === 'TRUE') {
      return AccessResult::allowed();
    }
    
    // Check to make sure that the groups in question have the group_menu plugin installed.
    $group_menus = GroupMenu::loadByMenu($menu);
    foreach($group_menus as $group_menu) {
      $group = $group_menu->getGroup();
      if (!$group->getGroupType()->hasContentPlugin('group_menu:menu')) {
        return AccessResult::neutral();
      }

      // This user has access to at least one of the groups that this menu belongs to.
      if ($access = $group->hasPermission('delete group menus', $account)) {
        return AccessResult::allowed();      
      }
    }

    // If route is protected and user does not have permission for at least one group.
    return AccessResult::forbidden();
  }

}
