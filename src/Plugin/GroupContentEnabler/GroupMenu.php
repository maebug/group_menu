<?php

namespace Drupal\group_menu\Plugin\GroupContentEnabler;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\GroupContentEnablerBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a content enabler for menus.
 *
 * @GroupContentEnabler(
 *   id = "group_menu",
 *   label = @Translation("Group menu"),
 *   description = @Translation("Adds menus to groups."),
 *   entity_type_id = "menu",
 *   pretty_path_key = "menu",
 *   deriver = "Drupal\group_menu\Plugin\GroupContentEnabler\GroupMenuDeriver"
 * )
 */
class GroupMenu extends GroupContentEnablerBase {

  /**
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    $account = \Drupal::currentUser();
    $operations = [];
    $route_params = ['group' => $group->id()];

    if ($group->hasPermission("create group menus", $account)) {
      $operations["group-menu-create-menu"] = [
        'title' => $this->t('Create menu'),
        'url' => new Url('entity.group_menu.group_menu_add_form', $route_params),
        'weight' => 31,
      ];
    }
    if ($group->hasPermission("edit group menus", $account)) {
      $operations["group-menu-update-menu"] = [
        'title' => $this->t('Edit menus'),
        'url' => new Url('entity.group_menu.group_menu_add_form', $route_params),
        'weight' => 32,
      ];
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    // Add our own permissions for managing the menus.
    $defaults = [
      'description' => 'Only applies to menus that belong to this group.',
    ];

    $permissions["view group menus"] = [
      'title' => 'View menus',
    ] + $defaults;

    $permissions["create group menus"] = [
      'title' => 'Create menus',
      'description' => 'Allows you to create menus that immediately belong to this group.',
    ] + $defaults;

    $permissions["edit group menus"] = [
      'title' => 'Edit menus',
    ] + $defaults;

    $permissions["delete group menus"] = [
      'title' => 'Delete menus',
    ] + $defaults;

    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['entity_cardinality'] = 1;

    // This string will be saved as part of the group type config entity. We do
    // not use a t() function here as it needs to be stored untranslated.
    $config['info_text']['value'] = '<p>By submitting this form you will add this menu to the group.<br />It will then be subject to the access control settings that were configured for the group.<br/>Please fill out any available fields to describe the relation between the content and the group.</p>';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Disable the entity cardinality field as the functionality of this module
    // relies on a cardinality of 1. We don't just hide it, though, to keep a UI
    // that's consistent with other content enabler plugins.
    $info = $this->t("This field has been disabled by the plugin to guarantee the functionality that's expected of it.");
    $form['entity_cardinality']['#disabled'] = TRUE;
    $form['entity_cardinality']['#description'] .= '<br /><em>' . $info . '</em>';

    return $form;
  }

}
