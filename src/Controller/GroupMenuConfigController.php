<?php

namespace Drupal\group_menu\Controller;

use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Core\Serialization\Yaml;
use Symfony\Component\HttpFoundation\Request;
use Drupal\config\Controller\ConfigController;

/**
 * Extend ConfigController to not include group menus on download.
 */
class GroupMenuConfigController extends ConfigController {

  /**
   * {inheritdoc}
   */
  public function downloadExport() {
    file_unmanaged_delete(file_directory_temp() . '/config.tar.gz');

    $archiver = new ArchiveTar(file_directory_temp() . '/config.tar.gz', 'gz');

    $connection = \Drupal::database();
    $query = $connection->select('config', 'c');
    $query->leftJoin('group_menu_field_data', 'gm', "concat(:prefix, gm.entity_id) = c.name", array(':prefix' => 'system.menu.'));
    $query->fields('c', array('name'))
      ->isNull('gm.entity_id');
    $names = $query->execute()->fetchCol();

    // Get raw configuration data.
    foreach ($names as $name) {
      $archiver->addString("$name.yml", Yaml::encode($this->configManager->getConfigFactory()->get($name)->getRawData()));
    }
    // Get all override data from the remaining collections.
    foreach ($this->targetStorage->getAllCollectionNames() as $collection) {
      $collection_storage = $this->targetStorage->createCollection($collection);
      foreach ($collection_storage->listAll() as $name) {
        $archiver->addString(str_replace('.', '/', $collection) . "/$name.yml", Yaml::encode($collection_storage->read($name)));
      }
    }

    $request = new Request(array('file' => 'config.tar.gz'));
    return $this->fileDownloadController->download($request, 'temporary');
  }

}
