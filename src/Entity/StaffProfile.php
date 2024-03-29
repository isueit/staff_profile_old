<?php
namespace Drupal\staff_profile\Entity;

use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;
use Drupal\staff_profile\StaffProfileInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityPublishedTrait;

/**
 * Defines staff_profile entity class
 *
 *  @ingroup staff_profile
 *  @ContentEntityType(
 *    id = "staff_profile_profile",
 *    label = @Translation("Staff Profile"),
 *    handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\staff_profile\Entity\Controller\StaffProfileListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\staff_profile\Form\StaffProfileForm",
 *       "edit" = "Drupal\staff_profile\Form\StaffProfileForm",
 *       "delete" = "Drupal\staff_profile\Form\StaffProfileDeleteForm",
 *     },
 *     "access" = "Drupal\staff_profile\StaffProfileAccessControlHandler",
 *   },
 *    base_table = "staff_profile_entity",
 *    revision_table = "staff_profile_entity_revision",
 *    revision_data_table = "staff_profile_field_revision",
 *    admin_permission = "administer staff profile entity",
 *    fieldable = TRUE,
 *    links = {
 *      "canonical" = "/people/{staff_profile_profile}",
 *      "add-page" = "/people/add",
 *      "edit-form" = "/people/{staff_profile_profile}/edit",
 *      "delete-form" = "/people/{staff_profile_profile}/delete",
 *      "collection" = "/people/admin",
 *    },
 *    entity_keys = {
 *      "id" = "id",
 *      "uuid" = "uuid",
 *      "label" = "name",
 *      "published" = "status",
 *      "revision" = "revision_id",
 *      "status" = "status",
 *    },
 *    revision_metadata_keys = {
 *      "revision_user" = "revision_user",
 *      "revision_created" = "revision_created",
 *      "revision_log_message",
 *    },
 *    field_ui_base_route = "staff_profile.staff_profile_settings",
 *  )
 *
*/
class StaffProfile extends EditorialContentEntityBase implements StaffProfileInterface, EntityPublishedInterface {
  use EntityChangedTrait;

  /**
  * {@inheritdoc}
  * Set computed fields when creating a new Staff Profile
  */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    parent::preCreate($storage, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
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
  */
  public function isPublished() {
    return $this->getEntityKey('status');
  }

  /**
  * {@inheritdoc}
  */
  public function setPublished($published = NULL) {
    $this
      ->set('status', TRUE);
    return $this;
  }

  /**
  * {@inheritdoc}
  */
  public function setUnpublished() {
    $this
      ->set('status', FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    $this->name->value = $this->field_first_name->value . ' ' . $this->field_last_name->value;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    if ($update) {
      if (\Drupal::moduleHandler()->moduleExists('search')) {
        search_mark_for_reindex('staff_profile_profile_search', $this->id());
      }
    }
  }

  /**
  * {@inheritdoc}
  *
  * Creates Fields and properties
  * Defines gui behavior
  */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setReadOnly(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 225,
      ))
      ->setDisplayConfigurable('form', FALSE);

    $fields['body'] = BaseFieldDefinition::create('text_with_summary')
      ->setLabel(t('Biography/Area(s) of Expertise'))
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setTranslatable(TRUE)
      ->setSettings(array(
        'default_value' => '',
      ))
      ->setDisplayOptions('teaser', array(
        'type' => 'text_summary_or_trimmed',
        'weight' => 101,
        'settings' => array(
          'display_label' => TRUE,
          'trim_length' => 600,
        ),
      ))
      ->setDisplayOptions('view', array(
        'type' => 'text_default',
        'weight' => 2,
        'region' => 'content',
        'label' => 'hidden',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'text_textarea_with_summary',
        'weight' => 12,
        'region' => 'content',
        'settings' => array(
          'rows' => 9,
          'placeholder' => '',
          'summary_rows' => 3,
        ),
      ))
      ->setDisplayConfigurable('teaser', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['field_address'] = BaseFieldDefinition::create('address')
      ->setLabel(t('Address'))
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setSettings(array(
        'fields' => array(
          'addressLine1' => 'addressLine1',
          'addressLine2' => 'addressLine2',
          'administrativeArea' => 'administrativeArea',
          'locality' => 'locality',
          'dependentLocality' => 'dependentLocality',
          'postalCode' => 'postalCode',
          'sortingCode' => 'sortingCode',
          'organization' => 0,
          'givenName' => 0,
          'additionalName' => 0,
          'familyName' => 0,
        ),
      ))
      ->setDefaultValue(array(
        'country_code' => 'US',
        'administrative_area' => 'IA',
      ))
      ->setDisplayOptions('view', array(
        'type' => 'address',
        'weight' => 3,
        'region' => 'content',
        'label' => 'hidden',
        'settings' => array(
          'link_to_entity' => FALSE,
        ),
      ))
      ->setDisplayOptions('form', array(
        'type' => 'address_default',
        'weight' => 4,
        'region' => 'content',
        'settings' => array(
          'size' => 60,
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['field_base_county'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Base County'))
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setSettings(array(
        'default_value' => '',
        'target_type' => 'taxonomy_term',
        'handler' => 'default:taxonomy_term',
        'handler_settings' => array(
          'target_bundles' => array(
            'counties-in-iowa' => 'counties-in-iowa',
          ),
          'sort' => array(
            'field' => 'name',
            'direction' => 'desc',
          ),
          'auto_create' => FALSE,
          'auto_create_bundle' => '',
        ),

      ))
      ->setDisplayOptions('view', array(
        'type' => 'entity_reference_entity_view',
        'weight' => 14,
        'region' => 'content',
        'display_label' => 'inline',
        'settings' => array(
          'link' => TRUE,
          'view_mode' => 'default',
        ),
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 16,
        'region' => 'content',
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['field_extension_region'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Extension Region'))
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
      ))
      ->setDisplayOptions('view', array(
        'type' => 'string',
        'weight' => 12,
        'region' => 'content',
        'display_label' => 'inline',
        'settings' => array(
          'link_to_entity' => FALSE,
        ),
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 14,
        'region' => 'content',
        'settings' => array(
          'size' => 60,
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);


    $fields['field_counties_served'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Counties Served'))
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setCardinality(-1)
      ->setSettings(array(
        'default_value' => '',
        'target_type' => 'taxonomy_term',
        'handler' => 'default:taxonomy_term',
        'handler_settings' => array(
          'target_bundles' => array(
            'counties-in-iowa' => 'counties-in-iowa',
          ),
          'sort' => array(
            'field' => 'name',
            'direction' => 'asc',
          ),
          'auto_create' => FALSE,
          'auto_create_bundle' => '',
          ),
      ))
      ->setDisplayOptions('view', array(
        'type' => 'entity_reference_entity_view',
        'weight' => 14,
        'region' => 'content',
        'display_label' => 'inline',
        'settings' => array(
          'link' => TRUE,
          'view_mode' => 'default',
        ),
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_buttons',
        'weight' => 16,
        'region' => 'content',
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['field_department_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Department ID'))
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 18,
        'region' => 'content',
        'settings' => array(
          'size' => 60,
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['field_email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('E-Mail'))
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
      ))
      ->setDisplayOptions('view', array(
        'type' => 'basic_string',
        'weight' => 10,
        'region' => 'content',
        'display_label' => 'inline',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'email_default',
        'weight' => 11,
        'region' => 'content',
        'settings' => array(
          'size' => 60,
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['field_phone_2'] = BaseFieldDefinition::create('telephone')
      ->setLabel(t('Secondary Phone Number'))
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
      ))
      ->setDisplayOptions('view', array(
        'type' => 'string',
        'weight' => 9,
        'region' => 'content',
        'display_label' => 'inline',
        'settings' => array(
          'link_to_entity' => FALSE,
        ),
      ))
      ->setDisplayOptions('form', array(
        'type' => 'telephone_default',
        'weight' => 10,
        'region' => 'content',
        'settings' => array(
          'size' => 60,
          'placeholder' => '',
        ),

      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['field_from_staff_directory'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Managed by Staff Profile Sync'))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => 22,
        'region' => 'content',
        'settings' => array(
          'placeholder' => 'false',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['field_first_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('First Name'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 1,
        'region' => 'content',
        'settings' => array(
          'size' => 60,
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['field_last_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Last Name'))
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 2,
        'region' => 'content',
        'settings' => array(
          'size' => 60,
          'placeholder' => '',
        ),
      ))
      // ->setDisplayOptions('view', array(
      //   'type' => 'invisible',
      //   'label' => 'hidden',
      // ))
      // ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['field_location'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Location'))
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
      ))
      ->setDisplayOptions('view', array(
        'type' => 'string',
        'weight' => 15,
        'region' => 'content',
        'display_label' => 'above',
        'settings' => array(
          'link_to_entity' => FALSE,
        ),
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 19,
        'region' => 'content',
        'settings' => array(
          'size' => 60,
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['field_phone'] = BaseFieldDefinition::create('telephone')
      ->setLabel(t('Phone Number'))
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', array(
        'type' => 'string',
        'weight' => 8,
        'region' => 'content',
        'display_label' => 'inline',
        'settings' => array(
          'link_to_entity' => FALSE,
        ),
      ))
      ->setDisplayOptions('form', array(
        'type' => 'telephone_default',
        'weight' => 3,
        'region' => 'content',
        'settings' => array(
          'size' => 60,
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['field_position_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Position Title'))
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 3,
        'region' => 'content',
        'settings' => array(
          'size' => 60,
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['field_profile_image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Profile Image'))
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setSettings(array(
        'settings' => array(
          'file_directory' => 'profile/images',
          'file_extensions' => 'png gif jpg jpeg',
          'max_filesize' => '',
          'max_resolution' => '',
          'min_resolution' => '',
          'alt_field' => TRUE,
          'alt_field_required' => TRUE,
          'title_field' => TRUE,
          'title_field_required' => FALSE,
          'default_image' => array(
            'uuid' => '',
            'alt' => '',
            'title' => '',
            'width' => null,
            'height' => null,
          ),
          'handler' => 'default;file',
        ),
      ))
      ->setDisplayOptions('view', array(
        'type' => 'image',
        'weight' => 1,
        'region' => 'content',
        'label' => 'hidden',
        'settings' => array(
          'link_to_entity' => FALSE,
          'image_style' => 'medium',
          'image_link' => ''
        ),
      ))
      ->setDisplayOptions('form', array(
        'type' => 'image_image',
        'weight' => 17,
        'region' => 'content',
        'settings' => array(
          'progress_indicator' => 'throbber',
          'preview_image_style' => 'thumbnail'
        ),
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['field_profile_smugmug_image'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Smugmug Embed Image'))
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setSettings(array(
        'default_value' => "",
        'max_length' => 255
      ))
      ->setDisplayOptions('view', array(
        'type' => 'remote_smugmug_image',
        'weight' => 1,
        'region' => 'content',
        'label' => 'hidden',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 17,
        'region' => 'content',
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['field_program_area_s_'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Program Areas'))
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'allowed_values' => array(
          "4-H Youth" => "4-H Youth",
          "Administration" => "Administration",
          "Agriculture" => "Agriculture",
          "Business & Industry" => "Business & Industry",
          "Communications & External Relations" => "Communications & External Relations",
          "Communities" => "Communities",
          "Continuing Education & Professional Development" => "Continuing Education & Professional Development",
          "Human Sciences" => "Human Sciences",
        ),
      ))
      ->setDisplayOptions('view', array(
        'type' => 'string',
        'weight' => 11,
        'region' => 'content',
        'display_label' => 'inline',
        'settings' => array(
          'link_to_entity' => FALSE,
        ),
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 13,
        'region' => 'content',
        'settings' => array(
          'size' => 60,
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);


    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User Name'))
      ->setSettings(array(
        'target_type' => 'user',
        'handler' => 'default',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'entity_reference_label',
        'weight' => 23,
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Published status'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'label' => 'hidden',
        'weight' => 21,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'));

    return $fields;
  }
}
