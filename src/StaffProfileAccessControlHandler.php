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

   /**
    * {@inheritdoc}
    */
  protected function checkFieldAccess($operation, $field_definition, $account, $items = NULL) {
    $protected_fields = ['field_program_area_s_', 'field_last_name', 'field_email', 'field_address_1', 'field_address_2', 'field_city', 'field_state', 'field_zip', 'field_phone', 'field_extension_region', 'field_location', 'field_department_id', 'field_base_county', 'field_counties_served', 'field_profile_image', 'field_profile_smugmug', 'field_profile_smugmug_text'];
     switch ($operation) {
       case 'edit':
       $field = $field_definition->getName();
        if (in_array($field, $protected_fields) && !$account->hasPermission('administer staff profile entity')) {
          return AccessResult::forbidden();
          #TODO allow to see text formatted
          #TODO allow profiles not from staff db to be edited to keep info up to date
        }
         break;
       case 'view':
        return AccessResult::allowedIfHasPermission($account, 'access content');
        break;
     }
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }
}
