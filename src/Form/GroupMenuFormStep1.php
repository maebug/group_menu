<?php

namespace Drupal\group_menu\Form;

use Drupal\menu_ui\MenuForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a creating a menu without it being saved yet.
 */
class GroupMenuFormStep1 extends MenuForm {

  /**
   * The private store for temporary group nodes.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $privateTempStore;

  /**
   * Constructs a GroupNodeFormStep1 object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   */
  public function __construct(QueryFactory $entity_query_factory, MenuLinkManagerInterface $menu_link_manager,
MenuLinkTreeInterface $menu_tree, LinkGeneratorInterface $link_generator, PrivateTempStoreFactory $temp_store_factory) {
    parent::__construct($entity_query_factory, $menu_link_manager, $menu_tree, $link_generator);
    $this->privateTempStore = $temp_store_factory->get('group_menu_add_temp');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('entity.query'),
      $container->get('plugin.manager.menu.link'),
      $container->get('menu.link_tree'),
      $container->get('link_generator'),
      $container->get('user.private_tempstore')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Continue to final step'),
      '#submit' => ['::submitForm', '::saveTemporary'],
    ];

    $actions['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#submit' => ['::cancel'],
      '#limit_validation_errors' => [],
    ];
    return $actions;
  }

  /**
   * Saves a temporary node and continues to step 2 of group node creation.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\group_menu\Controller\GroupNodeWizardController::add()
   * @see \Drupal\group_menu\Form\GroupNodeFormStep2
   */
  public function saveTemporary(array &$form, FormStateInterface $form_state) {
    $storage_id = $form_state->get('storage_id');

    $this->privateTempStore->set("$storage_id:menu", $this->entity);
    $this->privateTempStore->set("$storage_id:step", 2);

    // Disable any URL-based redirect until the final step.
    $request = $this->getRequest();
    $form_state->setRedirectUrl(Url::fromRoute('<current>', [], ['query' => $request->query->all()]));
    $request->query->remove('destination');
  }

  /**
   * Cancels the node creation by emptying the temp store.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\group_menu\Controller\GroupNodeWizardController::add()
   */
  public function cancel(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = $form_state->get('group');

    $storage_id = $form_state->get('storage_id');
    $this->privateTempStore->delete("$storage_id:menu");

    // Redirect to the group page if no destination was set in the URL.
    $form_state->setRedirect('entity.group.canonical', ['group' => $group->id()]);
  }

}
