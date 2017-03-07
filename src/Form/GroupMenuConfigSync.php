<?php

namespace Drupal\group_menu\Form;

use Drupal\group_menu\GroupMenuStorageComparer;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Url;
use Drupal\config\Form\ConfigSync;

/**
 * Modified ConfigSync form to use custom StorageComparer.
 */
class GroupMenuConfigSync extends ConfigSync {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Import all'),
    );
    $source_list = $this->syncStorage->listAll();
    $storage_comparer = new GroupMenuStorageComparer($this->syncStorage, $this->activeStorage, $this->configManager);
    if (empty($source_list) || !$storage_comparer->createChangelist()->hasChanges()) {
      $form['no_changes'] = array(
        '#type' => 'table',
        '#header' => array($this->t('Name'), $this->t('Operations')),
        '#rows' => array(),
        '#empty' => $this->t('There are no configuration changes to import.'),
      );
      $form['actions']['#access'] = FALSE;
      return $form;
    }
    elseif (!$storage_comparer->validateSiteUuid()) {
      drupal_set_message($this->t('The staged configuration cannot be imported, because it originates from a different site than this site. You can only synchronize configuration between cloned instances of this site.'), 'error');
      $form['actions']['#access'] = FALSE;
      return $form;
    }
    // A list of changes will be displayed, so check if the user should be
    // warned of potential losses to configuration.
    if ($this->snapshotStorage->exists('core.extension')) {
      $snapshot_comparer = new GroupMenuStorageComparer($this->activeStorage, $this->snapshotStorage, $this->configManager);
      if (!$form_state->getUserInput() && $snapshot_comparer->createChangelist()->hasChanges()) {
        $change_list = array();
        foreach ($snapshot_comparer->getAllCollectionNames() as $collection) {
          foreach ($snapshot_comparer->getChangelist(NULL, $collection) as $config_names) {
            if (empty($config_names)) {
              continue;
            }
            foreach ($config_names as $config_name) {
              $change_list[] = $config_name;
            }
          }
        }
        sort($change_list);
        $message = [
          [
            '#markup' => $this->t('The following items in your active configuration have changes since the last import that may be lost on the next import.')
          ],
          [
            '#theme' => 'item_list',
            '#items' => $change_list,
          ]
        ];
        drupal_set_message($this->renderer->renderPlain($message), 'warning');
      }
    }

    // Store the comparer for use in the submit.
    $form_state->set('storage_comparer', $storage_comparer);

    // Add the AJAX library to the form for dialog support.
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    foreach ($storage_comparer->getAllCollectionNames() as $collection) {
      if ($collection != StorageInterface::DEFAULT_COLLECTION) {
        $form[$collection]['collection_heading'] = array(
          '#type' => 'html_tag',
          '#tag' => 'h2',
          '#value' => $this->t('@collection configuration collection', array('@collection' => $collection)),
        );
      }
      foreach ($storage_comparer->getChangelist(NULL, $collection) as $config_change_type => $config_names) {
        if (empty($config_names)) {
          continue;
        }

        // @todo A table caption would be more appropriate, but does not have the
        //   visual importance of a heading.
        $form[$collection][$config_change_type]['heading'] = array(
          '#type' => 'html_tag',
          '#tag' => 'h3',
        );
        switch ($config_change_type) {
          case 'create':
            $form[$collection][$config_change_type]['heading']['#value'] = $this->formatPlural(count($config_names), '@count new', '@count new');
            break;

          case 'update':
            $form[$collection][$config_change_type]['heading']['#value'] = $this->formatPlural(count($config_names), '@count changed', '@count changed');
            break;

          case 'delete':
            $form[$collection][$config_change_type]['heading']['#value'] = $this->formatPlural(count($config_names), '@count removed', '@count removed');
            break;

          case 'rename':
            $form[$collection][$config_change_type]['heading']['#value'] = $this->formatPlural(count($config_names), '@count renamed', '@count renamed');
            break;
        }
        $form[$collection][$config_change_type]['list'] = array(
          '#type' => 'table',
          '#header' => array($this->t('Name'), $this->t('Operations')),
        );

        foreach ($config_names as $config_name) {
          if ($config_change_type == 'rename') {
            $names = $storage_comparer->extractRenameNames($config_name);
            $route_options = array('source_name' => $names['old_name'], 'target_name' => $names['new_name']);
            $config_name = $this->t('@source_name to @target_name', array('@source_name' => $names['old_name'], '@target_name' => $names['new_name']));
          }
          else {
            $route_options = array('source_name' => $config_name);
          }
          if ($collection != StorageInterface::DEFAULT_COLLECTION) {
            $route_name = 'config.diff_collection';
            $route_options['collection'] = $collection;
          }
          else {
            $route_name = 'config.diff';
          }
          $links['view_diff'] = array(
            'title' => $this->t('View differences'),
            'url' => Url::fromRoute($route_name, $route_options),
            'attributes' => array(
              'class' => array('use-ajax'),
              'data-dialog-type' => 'modal',
              'data-dialog-options' => json_encode(array(
                'width' => 700
              )),
            ),
          );
          $form[$collection][$config_change_type]['list']['#rows'][] = array(
            'name' => $config_name,
            'operations' => array(
              'data' => array(
                '#type' => 'operations',
                '#links' => $links,
              ),
            ),
          );
        }
      }
    }
    return $form;
  }

}
