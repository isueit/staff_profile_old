<?php

namespace Drupal\staff_profile\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form for deleting staff profile
 * @ingroup staff_profile
 */
class StaffProfileDeleteForm extends ContentEntityConfirmFormBase {
  /**
   * @return string
   *  The form question
   */
  public function getQuestion() {
    return $this->t("Are you sure that you want to delete the Staff Profile for %profile?", array( '%profile' => $this->entity->label()));
  }

  /**
   * @return \Drupal\Core\Url
   *  A url object
   */
  public function getCancelUrl() {
    return new Url('entity.staff_profile_profile.collection');
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
