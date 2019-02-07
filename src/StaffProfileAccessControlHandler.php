<?php

namespace Drupal\staff_profile;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the StaffProfile entity.
 * @see \Drupal\comment\Entity\Comment.
 */
class StaffProfileAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * Link activities to permissions
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view staff profile entity');

      case 'edit':
        return AccessResult::allowedIfHasPermission($account, 'edit staff profile entity');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete staff profile entity');
    }
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
   protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
     return AccessResult::allowedIfHasPermission($account, 'add staff profile entity');
   }
}
