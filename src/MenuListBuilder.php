<?php

namespace Drupal\group_menu;

/**
 * {@inheritdoc}
 */
class MenuListBuilder extends \Drupal\menu_ui\MenuListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getEntityIds() {
    // Note: I wanted to use a query tag here but it didn't seem to work...
    // Also, I do not like the way this is currently working
    $query = $this->getStorage()->getQuery()
    ->condition($this->entityType->getKey('id'),
      db_select('group_menu_field_data', 'gm')
        ->fields('gm', array('entity_id'))
        ->execute()
        ->fetchAll(\PDO::FETCH_COLUMN, 0),
      'NOT IN')
    ->sort($this->entityType->getKey('id'));
    
    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }
}