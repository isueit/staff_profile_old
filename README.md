# Staff Profile Module
### staff_profile
This is Drupal 8 module creates the entity, pathauto pattern and view for the staff_profile

## [Staff Profile Entity](src/Entity/StaffProfile.php)
The staff profile entity consists of:
* Username
* First and Last names
* Biography
* Contact Address
* Contact email, phone and fax numbers
* Counties Served
* Program Area
* Title and Department

## Views
[Public list](config/install/views.view.staff_profiles.yml) allows anonymous users to view staff profiles.

[Admin list](src/Entity/Controller/StaffProfileListBuilder.php) allows administrators to manage staff profiles.

Canonical view shows individual profiles, accessible by entity id or pathauto route and is defined in the [entity definition.](src/Entity/StaffProfile.php)

## [Pathauto Pattern](config/install/pathauto.pattern.staff_profiles.yml)
Pathauto route is created with entity using the format "first_name-last_name" under the path example.com/sitename/people/first_name-last_name

## Forms
Add, Edit and Delete forms to manage profiles as admin user

## Prerequisites
Staff Profile requires the following modules:
* drupal:views
* telephone
* pathauto
