<?php

namespace Drupal\group_menu;

use \Drupal\menu_ui\MenuListBuilder;

/**
 * {@inheritdoc}
 */
class GroupMenuListBuilder extends MenuListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getEntityIds() {
    // Note: I wanted to use a query tag here but it didn't seem to work...
    // Also, I do not like the way this is currently working
    $group_menu_ids = db_select('group_menu_field_data', 'gm')
      ->fields('gm', array('entity_id'))
      ->execute()
      ->fetchAll(\PDO::FETCH_COLUMN, 0);

    $query = $this->getStorage()->getQuery()
    ->condition($this->entityType->getKey('id'), $group_menu_ids, 'NOT IN')
    ->sort($this->entityType->getKey('id'));

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

}
