<?php

namespace Drupal\group_menu_block\Plugin\Block;

use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Menu\MenuTreeParameters;

/**
 * Provides a block for displaying group menus.
 *
 * @Block(
 *   id = "group_menus",
 *   admin_label = @Translation("Group menus"),
 *   context = {
 *     "group" = @ContextDefinition("entity:group", required = FALSE),
 *     "node" = @ContextDefinition("entity:node", required = FALSE)
 *   }
 * )
 */
class GroupMenuBlock extends BlockBase {

  /**
   * Gets group menu names from group ID
   *
   * @param int $gid
   *   The group ID you want to load menus for
   *
   * @return array
   *   Array of machine names for group
   */
  protected function getGroupMenus($gid) {
    $menus = array();
    $group_menus = \Drupal::entityTypeManager()
      ->getStorage('group_menu')
      ->loadByProperties(['gid' => $gid]);

    // Make sure we don't select a menu twice
    foreach($group_menus as $group_menu) {
      $menu_name = $group_menu->entity_id->getString();
      if (!in_array($menu_name, $menus)) {
        $menus[] = $menu_name;
      }
    }
    return $menus;
  }

  /**
   * {@inheritdoc}
   */
  public function build() { 
    // Get the associated group content for the current node
    $node = $this->getContextValue('node');
    if ($node) {
      $group_contents = GroupContent::loadByEntity($node);

      $menus = array();
      // For each group this node belongs to...
      foreach($group_contents as $group_content) {
        $group = $group_content->getGroup();
        // ...make an array of menus to render...
        $menus = array_merge($menus, $this->getGroupMenus($group->id()));
      }
    } else {
      // Not on a node page, see if we can get the group
      $group = $this->getContextValue('group');
      if ($group) {
        $menus = $this->getGroupMenus($group->id());
      } else {
        return array();
      }
    }

    // ...and render them
    $parameters = new MenuTreeParameters();
    $parameters->onlyEnabledLinks();
    $manipulators = array(
      array('callable' => 'menu.default_tree_manipulators:checkAccess'),
      array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
    );
    foreach ($menus as $menu_name) {
      $tree = \Drupal::menuTree()->load($menu_name, $parameters);
      $tree = \Drupal::menuTree()->transform($tree, $manipulators);
      $build[] = \Drupal::menuTree()->build($tree);
    }

    return $build;
  }

}