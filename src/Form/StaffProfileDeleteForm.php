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
    //The default asks "Are you sure that you want to delete entity <entity name>?"
  }

  /**
   * @return \Drupal\Core\Url
   *  A url object
   */
  public function getCancelUrl() {
    return new Url('entity.staff_profile_profile.collection');
  }
}
