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
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'access content');

      case 'edit':
        //Check if user has edit staff profile permissions or owns the entity
        $return = AccessResult::allowedIfHasPermission($account, 'edit staff profile entity');
        if (!$return->isForbidden()) {
          $entity_owner = $entity->get('user_id')->getValue()[0]['target_id'];
          $return = $return->orIf(AccessResult::allowedIf($account->id() == $entity_owner));
        }
        return $return;

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
