<?php

namespace Drupal\group_menu\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class GroupMenuSettingsForm.
 */
class GroupMenuSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'group_menu_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['group_menu.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('group_menu.settings');

    $form['group_menu_hide_list'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide group menus from menu lists'),
      '#description' => $this->t("Hide group menus from default menu lists. (recommended)"),
      '#default_value' => $config->get('group_menu_hide_list'),
    ];

    $form['config_sync_group_menu_ignore'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Configuration sync: ignore group menus'),
      '#description' => $this->t("Do not export group menus and do not delete group menus on configuration import."),
      '#default_value' => $config->get('config_sync_group_menu_ignore'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('group_menu.settings');
    $form_config_sync = $form_state->getValue('config_sync_group_menu_ignore');
    $form_hide_list = $form_state->getValue('group_menu_hide_list');

    $config->set('config_sync_group_menu_ignore', $form_config_sync)->save();
    $config->set('group_menu_hide_list', $form_hide_list)->save();

    parent::submitForm($form, $form_state);
  }

}
