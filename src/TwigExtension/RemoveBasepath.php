<?php
namespace Drupal\staff_profile\TwigExtension;

class RemoveBasepath extends \Twig_Extension {
  /**
   * Generates List of Twig filters created
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('removebasepath', array($this, 'removeBasepath')),
    ];
  }

  /**
   * Unique identifier for Twig extension
   */
  public function getName() {
    return 'staff_profile.twig_extension';
  }

  /**
   * Remove the basepath from local urls when using json_feed
   */
  public static function removeBasepath($string) {
    return preg_replace('/^[\\/]+[a-zA-Z0-9]+[\\/]+/', '', $string);
  }
}
