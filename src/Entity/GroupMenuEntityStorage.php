<?php

namespace Drupal\group_menu\Entity;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Extends the ConfigEntityStorage class to not load all menus.
 */
class GroupMenuEntityStorage extends ConfigEntityStorage {

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultiple(array $ids = NULL) {
    // We only need to change the way Drupal loads the list of ALL menus.
    if ($ids !== NULL) {
      return parent::doLoadMultiple($ids);
    }

    $prefix = $this->getPrefix();

    // Get Drupal database connection.
    $connection = \Drupal::database();

    // Build query to select non-group menus.
    $query = $connection->select('config', 'c');
    $query->leftJoin('group_menu_field_data', 'gm', "concat(:prefix, gm.entity_id) = c.name", array(':prefix' => $prefix));
    $query->fields('c', array('name'))
      ->isNull('gm.entity_id')
      ->condition('c.name', $connection->escapeLike($prefix) . '%', 'LIKE');
    $ids = $query->execute()->fetchCol();

    // Strip out the prefixes from the entity names so we can send to parent.
    $prefixlen = strlen($prefix);
    foreach($ids as &$id) {
      $id = substr($id, $prefixlen);
    }

    return parent::doLoadMultiple($ids);
  }
  
}
