<?php

namespace Drupal\group_menu\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\user\UserInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Group menu entity.
 *
 * @ingroup group
 *
 * @ContentEntityType(
 *   id = "group_menu",
 *   label = @Translation("Group menu"),
 *   label_singular = @Translation("group menu item"),
 *   label_plural = @Translation("group menu items"),
 *   label_count = @PluralTranslation(
 *     singular = "@count group menu item",
 *     plural = "@count group menu items"
 *   ),
 *   bundle_label = @Translation("Group content type"),
 *   handlers = {
 *     "storage" = "Drupal\group\Entity\Storage\GroupContentStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\group\Entity\Views\GroupContentViewsData",
 *     "list_builder" = "Drupal\group\Entity\Controller\GroupContentListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\group\Entity\Routing\GroupContentRouteProvider",
 *     },
 *     "form" = {
 *       "add" = "Drupal\group\Entity\Form\GroupContentForm",
 *       "edit" = "Drupal\group\Entity\Form\GroupContentForm",
 *       "delete" = "Drupal\group\Entity\Form\GroupContentDeleteForm",
 *       "group-join" = "Drupal\group\Form\GroupJoinForm",
 *       "group-leave" = "Drupal\group\Form\GroupLeaveForm",
 *     },
 *     "access" = "Drupal\group\Entity\Access\GroupContentAccessControlHandler",
 *   },
 *   base_table = "group_menu",
 *   data_table = "group_menu_field_data",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "bundle" = "type",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/group/{group}/menu/add/{plugin_id}",
 *     "add-page" = "/group/{group}/menu/add",
 *     "canonical" = "/group/{group}/menu/{group_menu}",
 *     "collection" = "/group/{group}/menu",
 *     "delete-form" = "/group/{group}/menu/{group_menu}/delete",
 *     "edit-form" = "/group/{group}/menu/{group_menu}/edit"
 *   },
 *   bundle_entity_type = "group_content_type",
 *   field_ui_base_route = "entity.group_content_type.edit_form",
 *   permission_granularity = "bundle",
 *   constraints = {
 *     "GroupContentCardinality" = {}
 *   }
 * )
 */
class GroupMenu extends GroupContent implements GroupContentInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Specify menu entity type
    // Keep an eye out for resolution of https://www.drupal.org/node/2346347
    $fields['entity_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Content'))
      ->setDescription(t('The entity to add to the group.'))
      ->setSetting('target_type', 'menu')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setRequired(TRUE);

    return $fields;
  }

}
