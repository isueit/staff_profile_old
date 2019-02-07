<?php

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
    $build['description'] = [
      '#markup' => $this->t('Staff Profile List')
    ];
    $build += parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Creates a column for each field
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['body'] = $this ->t('body');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\staff_profile\Entity\StaffProfile */
    $row['name'] = $entity->'field_first_name'->value;+$entity->'field_last_name'->value;
    $row['body'] = $entity->'body'->value;
    return $row + parent::buildRow($entity);
  }
}
