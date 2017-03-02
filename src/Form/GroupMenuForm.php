<?php

namespace Drupal\group_menu\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\menu_ui\MenuForm;

/**
 * Group menu extention of base menu form.
 */
class GroupMenuForm extends MenuForm {

  /**
   * {@inheritdoc}
   */
  protected function buildOverviewForm(array &$form, FormStateInterface $form_state) {
    $form = parent::buildOverviewForm($form, $form_state);
    $form['links']['#empty'] = $this->t('There are no menu links yet. <a href=":url">Add link</a>.', [
      ':url' => $this->url('entity.group_menu.menu_link_content.edit_form', ['menu' => $this->entity->id()], [
        'query' => ['destination' => $this->entity->url('edit-form')],
      ]),
    ]);
    return $form;
  }

}
