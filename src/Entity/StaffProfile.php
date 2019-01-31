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
 *    id = "node.staff_profile",
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
 *    entity_keys = {
 *      TODO
 *
 *
 *
 *
 *    }
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
      ->setReadOnly(TRUE);
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setReadOnly(TRUE);
    $fields['body'] = BaseFieldDefinition::create('text_with_summary')
      ->setLabel(t('Biography/Area(s) of Expertise'))
      ->setSettings('teaser', array(
        'label' => 'hidden'
        'type' => 'text_summary_or_trimmed'
      ))
      ->setSettings('view', array())
      ->setSettings('form', array())
      ->setDisplayConfigurable('teaser', TRUE)
      ->setDisplayConfigurable('view', )
      ->setDisplayConfigurable('form', )
    $fields['address_1'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Address 1'))
      ->setSettings('teaser', array())
      ->setSettings('view', array())
      ->setSettings('form', array())
      ->setDisplayConfigurable('teaser', )
      ->setDisplayConfigurable('view', )
      ->setDisplayConfigurable('form', )
    $fields['address_2'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Address 2'))
      ->setSettings('teaser', array())
      ->setSettings('view', array())
      ->setSettings('form', array())
      ->setDisplayConfigurable('teaser', )
      ->setDisplayConfigurable('view', )
      ->setDisplayConfigurable('form', )
    $fields['base_county'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Base County'))
      ->setSettings('teaser', array())
      ->setSettings('view', array())
      ->setSettings('form', array())
      ->setDisplayConfigurable('teaser', )
      ->setDisplayConfigurable('view', )
      ->setDisplayConfigurable('form', )
    $fields['base_region'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Base Region'))
      ->setSettings('teaser', array())
      ->setSettings('view', array())
      ->setSettings('form', array())
      ->setDisplayConfigurable('teaser', )
      ->setDisplayConfigurable('view', )
      ->setDisplayConfigurable('form', )
    $fields['city'] = BaseFieldDefinition::create('string')
      ->setLabel(t('City'))
      ->setSettings('teaser', array())
      ->setSettings('view', array())
      ->setSettings('form', array())
      ->setDisplayConfigurable('teaser', )
      ->setDisplayConfigurable('view', )
      ->setDisplayConfigurable('form', )
    $fields['counties_served'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Counties Served'))
      ->setSettings('teaser', array())
      ->setSettings('view', array())
      ->setSettings('form', array())
      ->setDisplayConfigurable('teaser', )
      ->setDisplayConfigurable('view', )
      ->setDisplayConfigurable('form', )
    $fields['department_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Department ID'))
      ->setSettings('teaser', array())
      ->setSettings('view', array())
      ->setSettings('form', array())
      ->setDisplayConfigurable('teaser', )
      ->setDisplayConfigurable('view', )
      ->setDisplayConfigurable('form', )
    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('E-Mail'))
      ->setSettings('teaser', array())
      ->setSettings('view', array())
      ->setSettings('form', array())
    $fields['fax'] = BaseFieldDefinition::create('telephone')
      ->setLabel(t('Telephone Number'))
      ->setSettings('teaser', array())
      ->setSettings('view', array())
      ->setSettings('form', array())
      ->setDisplayConfigurable('teaser', )
      ->setDisplayConfigurable('view', )
      ->setDisplayConfigurable('form', )
    $fields['from_staff_directory'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Managed by Staff Profile Sync'))
      ->setSettings('teaser', array())
      ->setSettings('view', array())
      ->setSettings('form', array())
      ->setDisplayConfigurable('teaser', )
      ->setDisplayConfigurable('view', )
      ->setDisplayConfigurable('form', )
    $fields['first_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('First Name'))
      ->setSettings('teaser', array())
      ->setSettings('view', array())
      ->setSettings('form', array())
      ->setDisplayConfigurable('teaser', )
      ->setDisplayConfigurable('view', )
      ->setDisplayConfigurable('form', )
    $fields['last_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Last Name'))
      ->setSettings('teaser', array())
      ->setSettings('view', array())
      ->setSettings('form', array())
      ->setDisplayConfigurable('teaser', )
      ->setDisplayConfigurable('view', )
      ->setDisplayConfigurable('form', )
    $fields['location'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Location'))
      ->setSettings('teaser', array())
      ->setSettings('view', array())
      ->setSettings('form', array())
      ->setDisplayConfigurable('teaser', )
      ->setDisplayConfigurable('view', )
      ->setDisplayConfigurable('form', )
    $fields['phone'] = BaseFieldDefinition::create('telephone')
      ->setLabel(t('Phone Number'))
      ->setSettings('teaser', array())
      ->setSettings('view', array())
      ->setSettings('form', array())
      ->setDisplayConfigurable('teaser', )
      ->setDisplayConfigurable('view', )
      ->setDisplayConfigurable('form', )
    $fields['position_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Position Title'))
      ->setSettings('teaser', array())
      ->setSettings('view', array())
      ->setSettings('form', array())
      ->setDisplayConfigurable('teaser', )
      ->setDisplayConfigurable('view', )
      ->setDisplayConfigurable('form', )
    $fields['profile_image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Profile Image'))
      ->setSettings('teaser', array())
      ->setSettings('view', array())
      ->setSettings('form', array())
      ->setDisplayConfigurable('teaser', )
      ->setDisplayConfigurable('view', )
      ->setDisplayConfigurable('form', )
    $fields['program_area_s_'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Program Areas'))
      ->setSettings('teaser', array())
      ->setSettings('view', array())
      ->setSettings('form', array())
      ->setDisplayConfigurable('teaser', )
      ->setDisplayConfigurable('view', )
      ->setDisplayConfigurable('form', )
    $fields['state'] = BaseFieldDefinition::create('string')
      ->setLabel(t('State'))
      ->setSettings('teaser', array())
      ->setSettings('view', array())
      ->setSettings('form', array())
      ->setDisplayConfigurable('teaser', )
      ->setDisplayConfigurable('view', )
      ->setDisplayConfigurable('form', )
    $fields['zip'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ZIP Code'))
      ->setSettings('teaser', array())
      ->setSettings('view', array())
      ->setSettings('form', array())
      ->setDisplayConfigurable('teaser', )
      ->setDisplayConfigurable('view', )
      ->setDisplayConfigurable('form', )







//TODO Remove this, it is replaced with above, more organized

    $fields['id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('NETID'))
      ->setDescription(t('NETID of the Staff Profile entity'))
      ->setSettings(array(
        'default_value' => '',
        'max_length'=> 255,
        'text_processing' => 0,
      ))
      ->SetDisplayOptions('form', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE)
      ->setReadOnly(TRUE);
    //UUID, unique outside of project scope
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The uuid of the Staff Profile Entity'))
      ->setReadOnly(TRUE);

    //User Defined fields
    //First Name
    $fields['first_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('First Name'))
      ->setDescription(t('First name of the Staff Profile'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -5
      ))
      ->SetDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

      //Last Name
      $fields['last_name'] = BaseFieldDefinition::create('string')
        ->setLabel(t('Last Name'))
        ->setDescription(t('Last name of the Staff Profile'))
        ->setSettings(array(
          'default_value' => '',
          'max_length' => 255,
          'text_processing' => 0,
        ))
        ->setDisplayOptions('view', array(
          'label' => 'above',
          'type' => 'string',
          'weight' => -5
        ))
        ->SetDisplayOptions('form', array(
          'type' => 'string_textfield',
          'weight' => -5,
        ))
        ->setDisplayConfigurable('view', TRUE)
        ->setDisplayConfigurable('form', TRUE);


      $fields['body'] = BaseFieldDefinition::create('text_textarea_with_summary')
//Address TODO possibly set the edit and create forms to have a dropdown to make selecting address easier
      $fields['address_1'] = BaseFieldDefinition::create('string')
        ->setLabel(t('Address Line 1'))
        ->setDescription(t('First Address Line'))
        ->setSettings(array(
          'default_value' => '',
          'max_length' => 255,
          'text_processing' => 0,
        ))
        ->setDisplayOptions('view', array(
          'label' => 'above',
          'type' => 'string',
          'weight' => -4,
        ))
        ->SetDisplayOptions('form', array(
          'type' => 'string_textfield',
          'weight' => -4,
        ))
        ->setDisplayConfigurable('view', TRUE)
        ->setDisplayConfigurable('form', TRUE);

      $fields['address_2'] = BaseFieldDefinition::create('string')
        ->setLabel(t('Address Line 2'))
        ->setDescription(t('Second Address Line'))
        ->setSettings(array(
          'default_value' => '',
          'max_length' => 255,
          'text_processing' => 0,
        ))
        ->setDisplayOptions('view', array(
          'label' => 'above',
          'type' => 'string',
          'weight' => -4,
        ))
        ->SetDisplayOptions('form', array(
          'type' => 'string_textfield',
          'weight' => -4,
        ))
        ->setDisplayConfigurable('view', TRUE)
        ->setDisplayConfigurable('form', TRUE);

      $fields['city'] = BaseFieldDefinition::create('string')
        ->setLabel(t('City'))
        ->setDescription(t('Address City'))
        ->setSettings(array(
          'default_value' => '',
          'max_length' => 255,
          'text_processing' => 0,
        ))
        ->setDisplayOptions('view', array(
          'label' => 'above',
          'type' => 'string',
          'weight' => -4,
        ))
        ->SetDisplayOptions('form', array(
          'type' => 'string_textfield',
          'weight' => -4,
        ))
        ->setDisplayConfigurable('view', TRUE)
        ->setDisplayConfigurable('form', TRUE);

      $fields['state'] = BaseFieldDefinition::create('string')
        ->setLabel(t('State'))
        ->setDescription(t('Address State'))
        ->setSettings(array(
          'default_value' => '',
          'max_length' => 255,
          'text_processing' => 0,
        ))
        ->setDisplayOptions('view', array(
          'label' => 'above',
          'type' => 'string',
          'weight' => -4,
        ))
        ->SetDisplayOptions('form', array(
          'type' => 'string_textfield',
          'weight' => -4,
        ))
        ->setDisplayConfigurable('view', TRUE)
        ->setDisplayConfigurable('form', TRUE);

      //TODO may want to set zip to be an int with a length of 5
      $fields['zip'] = BaseFieldDefinition::create('string')
        ->setLabel(t('ZIP'))
        ->setDescription(t('Address ZIP code'))
        ->setSettings(array(
          'default_value' => '',
          'max_length' => 255,
          'text_processing' => 0,
        ))
        ->setDisplayOptions('view', array(
          'label' => 'above',
          'type' => 'string',
          'weight' => -4,
        ))
        ->SetDisplayOptions('form', array(
          'type' => 'string_textfield',
          'weight' => -4,
        ))
        ->setDisplayConfigurable('view', TRUE)
        ->setDisplayConfigurable('form', TRUE);
  }


}
