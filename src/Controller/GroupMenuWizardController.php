<?php

namespace Drupal\group_menu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Render\RendererInterface;
use Drupal\group_menu\Entity\GroupMenu;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Drupal\system\Entity\Menu;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for 'group_menu' GroupContent routes.
 */
class GroupMenuWizardController extends ControllerBase {

  /**
   * The private store for temporary group menus.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $privateTempStore;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The group content plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $pluginManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new GroupMenuWizardController.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager
   *   The group content plugin manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $entity_form_builder, GroupContentEnablerManagerInterface $plugin_manager, RendererInterface $renderer) {
    $this->privateTempStore = $temp_store_factory->get('group_menu_add_temp');
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFormBuilder = $entity_form_builder;
    $this->pluginManager = $plugin_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('plugin.manager.group_content_enabler'),
      $container->get('renderer')
    );
  }

  /**
   * Provides the form for creating a menu in a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to create a menu in.
   *
   * @return array
   *   The form array for either step 1 or 2 of the group menu creation wizard.
   */
  public function addForm(GroupInterface $group) {
    $plugin_id = 'group_menu:menu';
    $storage_id = $plugin_id . ':' . $group->id();

    // If we are on step one, we need to build a menu form.
    if ($this->privateTempStore->get("$storage_id:step") !== 2) {
      $this->privateTempStore->set("$storage_id:step", 1);

      // Only create a new menu if we have nothing stored.
      if (!$entity = $this->privateTempStore->get("$storage_id:menu")) {
        $entity = Menu::create();
      }
    }
    // If we are on step two, we need to build a group content form.
    else {
      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      $plugin = $group->getGroupType()->getContentPlugin($plugin_id);
      $entity = GroupMenu::create([
        'type' => $plugin->getContentTypeConfigId(),
        'gid' => $group->id(),
      ]);
    }

    // Return the form with the group and storage ID added to the form state.
    $extra = ['group' => $group, 'storage_id' => $storage_id];
    return $this->entityFormBuilder()->getForm($entity, 'group_menu-form', $extra);
  }

  /**
   * The _title_callback for the add menu form route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to create a menu in.
   *
   * @return string
   *   The page title.
   */
  public function addFormTitle(GroupInterface $group) {
    return $this->t('Create menu in %label', ['%label' => $group->label()]);
  }

  /**
   * Provides the group menu creation overview page.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the group menu to.
   *
   * @return array
   *   The group menu creation overview page.
   */
  public function addPage(GroupInterface $group) {
    // We do not set the "entity_add_list" template's "#add_bundle_message" key
    // because we deny access to the page if no bundle is available.
    $build = ['#theme' => 'entity_add_list', '#bundles' => []];
    $add_form_route = 'entity.group_menu.group_menu_add_form';

    $plugin_id = 'group_menu:menu';

    $storage = $this->entityTypeManager->getStorage('group_content_type');
    $properties = [
      'group_type' => $group->bundle(),
      'content_plugin' => $plugin_id,
    ];
    /** @var \Drupal\group\Entity\GroupContentTypeInterface[] $bundles */
    $bundles = $storage->loadByProperties($properties);

    // Filter out the bundles the user doesn't have access to.
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('group_menu');
    foreach (array_keys($bundles) as $bundle) {
      // Check for access and add it as a cacheable dependency.
      $access = $access_control_handler->createAccess($bundle, NULL, ['group' => $group], TRUE);
      $this->renderer->addCacheableDependency($build, $access);

      // Remove inaccessible bundles from the list.
      if (!$access->isAllowed()) {
        unset($bundles[$bundle]);
      }
    }

    // Redirect if there's only one bundle available.
    if (count($bundles) == 1) {
      $group_content_type = reset($bundles);
      $plugin = $group_content_type->getContentPlugin();
      $url = Url::fromRoute($add_form_route, ['group' => $group->id()], ['absolute' => TRUE]);
      return new RedirectResponse($url->toString());
    }

    // Get the menu type storage handler.
    $storage_handler = $this->entityTypeManager->getStorage('menu');

    // Set the info for all of the remaining bundles.
    foreach ($bundles as $bundle => $group_content_type) {
      $plugin = $group_content_type->getContentPlugin();
      //$bundle_label = $storage_handler->load($plugin->getEntityBundle())->label();

      $build['#bundles'][$bundle] = [
        'label' => 'menu',
        'description' => $this->t('Create a menu for the group.'),
        'add_link' => Link::createFromRoute('menu', $add_form_route, ['group' => $group->id()]),
      ];
    }

    // Add the list cache tags for the GroupContentType entity type.
    $bundle_entity_type = $this->entityTypeManager->getDefinition('group_content_type');
    $build['#cache']['tags'] = $bundle_entity_type->getListCacheTags();

    return $build;
  }

}
