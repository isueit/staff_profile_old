<?php
namespace Drupal\staff_profile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\staff_profile\Plugin\Search\StaffProfileSearch;

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
    //StaffProfileSearch::submitConfigurationForm($form, $form_state);
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    //StaffProfileSearch::buildConfigurationForm($form, $form_state);
  }
}
