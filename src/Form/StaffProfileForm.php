<?php

namespace Drupal\staff_profile\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the staff profile entity edit form
 * @ingroup staff_profile
 */
class StaffProfileForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\staff_profile\Entity\StaffProfile */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    debug($form_state);
    // if ($form_state['field_email'] != '') {
    //   $form_state['netid'] = $form_state['field_first_name'] + $form_state['field_last_name'];
    // } else {
    //   $form_state['netid'] = $form_state['field_email'];
    // }
    $status = parent::save($form, $form_state);

    $entity = $this->entity;
    //$entity->setNewRevision();

    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('The staff profile of %staff has been updated.', ['%staff' => $entity->toLink()->toString()]));
    } else {
      drupal_set_message($this->t('The staff profile of %staff has been created.', ['%staff' => $entity->toLink()->toString()]));
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $status;
  }
}
