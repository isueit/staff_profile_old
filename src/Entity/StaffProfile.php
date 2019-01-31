<?php
namespace Drupal\staff_profile\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Druapl\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\staff_profile\NodeInterface;
use Drupal\user\UserInterface;

/**
 * Defines staff_profile entity class
 *
 *  @ContentEntityType(
 *    id = "staff_profile",
 *    label = @Translation("Staff Profile"),
 *    bundle_label = @Translation("Staff Profile Entity Type"),
 *    handlers = {
 *      "form" = {
 *        "add" = "Drupal\Core\Entity\ContentEntityForm",
 *        "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *        "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *      },
 *      "access" = "Drupal\staff_profile"\StaffProfileAccessControlHandler"
 *     },
 *    base_table = "staff_profile"
 *    admin_permission = "administer staff_profile entity"
 *    links = {
 *      "canonical" = "/people/{field_first_name}-{field_last_name}",
 *      "add-page" = "/people/add",
 *      "edit-form" = "/people/{field_first_name}-{field_last_name}/edit",
 *      "delete-form" = "/people/{field_first_name}-{field_last_name}/delete",
 *      "collection" = "/people",
 *    },
 *  }
 *
*/
class StaffProfile extends ContentEntityBase implements StaffProfileInterface {
  use EntityChangedTrait;

  /**
  * {@inheritdoc}
  *
  * Set computed fields when creating a new staff Profile
  */
  public static function preCreate(EntityStorageInterface $storage_controller, arrat &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser->()->id(), //TODO Check if the user with the netid exists as a user and give them ownership instead
    );
  }

  /**
  * {@inheritdoc}
  */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
  * {@inheritdoc}
  */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
  * {@inheritdoc}
  */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
  * {@inheritdoc}
  */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
  * {@inheritdoc}
  */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }


  /**
  * {@inheritdoc}
  *
  * Creates Fields and properties
  * Defines gui behavior
  */
  public static function BaseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription('ID of the ')
  }


}
