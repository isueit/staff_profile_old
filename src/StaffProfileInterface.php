<?php
namespace Drupal\staff_profile;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\EntityChangedInterface;

/**
 * Provides an interface defining a Contact entity.
 * @ingroup staff_profile
 *
 */

interface StaffProfileInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
