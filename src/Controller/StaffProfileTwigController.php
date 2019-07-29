<?php
/**
 * @file
 * Contains \Drupal\staff_profile\Controller\StaffProfileTwigController.
 */

namespace Drupal\staff_profile\Controller;

use Drupal\Core\Controller\ControllerBase;

class StaffProfileTwigController extends ControllerBase {
  public function content() {
    debug($this);
    return [
      '#theme' => 'search_result__staff_profile_profile_search',
      // '#url' => "",
      // '#title' => "",
      // '#snippet' => "",
      // '#info' => "",
      '#plugin_id' => 'staff_profile_profile_search',
      // '#title_prefix' => "",
      // '#title_suffix' => "",
      // '#info_split' => [],
    ];
  }
}
