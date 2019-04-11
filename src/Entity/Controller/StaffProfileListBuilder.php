<?php
//Empty to statify content entity requirements
namespace Drupal\staff_profile\Entity\Controller;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;
/**
 * Provides a list controller for the staff_profile entity.
 *
 * @ingroup staff_profile
 */
class StaffProfileListBuilder extends EntityListBuilder {
  /**
   * {@inheritdoc}
   *
   * Overide existing render() to create view
   */
  public function render() {
  }
  /**
   * {@inheritdoc}
   *
   * Creates a column for each field
   */
  public function buildHeader() {
  }
  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
  }
}
