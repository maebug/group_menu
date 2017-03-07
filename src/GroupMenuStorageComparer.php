<?php

namespace Drupal\group_menu;

use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageInterface;

/**
 * Modified StorageComparer to exclude group menus.
 */
class GroupMenuStorageComparer extends StorageComparer {

  /**
   * {@inheritdoc}
   */
  public function createChangelist() {
    parent::createChangelist();
    // Caching prevents placing this check in GroupMenuRouteSubscriber.
    $config = \Drupal::config('group_menu.settings');
    if ($config->get('config_sync_group_menu_ignore')) {
      $this->removeChangelistGroupMenus();
    }
    return $this;
  }

  /**
   * Modifies changelist to remove group menus.
   */
  protected function removeChangelistGroupMenus() {
    // Get Drupal database connection.
    $connection = \Drupal::database();

    // Build query to select group menu configuration names.
    $query = $connection->select('config', 'c');
    $query->join('group_menu_field_data', 'gm', "concat('system.menu.', gm.entity_id) = c.name");
    $query->fields('c', array('name'));
    $gm_names = $query->execute()->fetchCol();

    $changelist = $this->getChangelist();

    // Iterate through each change operation.
    foreach ($changelist as $op => $names) {
      // Iterate through each item in operation and remove if group menu.
      foreach($names as $name) {
        if (in_array($name, $gm_names)) {
          $this->removeFromChangelist(StorageInterface::DEFAULT_COLLECTION, $op, $name);
        }
      }
    }
  }

}
