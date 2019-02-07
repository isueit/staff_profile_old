<?php
namespace Drupal\staff_profile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class StaffProfileSettingsForm.
 * @package Drupal\staff_profile\Form
 * @ingroup staff_profile
 */
class StaffProfileSettingsForm extends FormBase {
  public function getFormId() {
    return 'staff_profile_settings';
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    //Empty to satisfy requirements of class
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['staff_profile_settings']['#markup'] = 'Settings form for Staff Profile. Manage field settings here.';
    return $form;
  }
}
