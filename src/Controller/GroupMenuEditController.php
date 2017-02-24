<?php

namespace Drupal\group_menu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\system\MenuInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class GroupMenuEditController extends ControllerBase {

  /**
   * The entity form builder service.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * Creates a new GroupMenuEditController object.
   *
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder service.
   */
  public function __construct(EntityFormBuilderInterface $entity_form_builder) {
    $this->entityFormBuilder = $entity_form_builder;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.form_builder'));
  }

  public function edit(MenuInterface $menu) {
    return $this->entityFormBuilder()->getForm($menu, 'Drupal\group_menu\Form\GroupMenuForm');
    return array(
      '#type' => 'markup',
      '#markup' => $this->t('This is a test'),
    );
  }
}