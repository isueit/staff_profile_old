<?php

namespace Drupal\staff_profile\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\staff_profile\Entity\StaffProfileInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

class StaffProfileDisplayBuilder extends ControllerBase {
  /**
   * Creates public page listing staff
   */
  public function displayPeople() {
    $local_profiles = \Drupal::entityTypeManager()->getStorage('staff_profile_profile')->loadByProperties(['status' => TRUE]);
    foreach ($local_profiles as $profile) {
      $element[$profile->id()] += array('#markup' => $profile->toUrl());
    }
    return $element;
  }
}
