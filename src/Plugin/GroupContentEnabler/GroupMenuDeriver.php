<?php

namespace Drupal\group_menu\Plugin\GroupContentEnabler;

use Drupal\Component\Plugin\Derivative\DeriverBase;

class GroupMenuDeriver extends DeriverBase {

  /**
   * {@inheritdoc}.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives['menu'] = [
      'entity_bundle' => 'menu',
      'label' => t('Group menu'),
      'description' => t('Adds menus to groups both publicly and privately.'),
    ] + $base_plugin_definition;

    return $this->derivatives;
  }

}
