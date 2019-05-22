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
[Public list](config/install/views.view.staff_profiles.yml) allows all users to view staff profiles.

[Admin list](config/install/views.view.staff_profiles_admin.yml) allows administrators to manage staff profiles.

Canonical view shows individual profiles, accessible by entity id or pathauto route and is defined in the [entity definition.](src/Entity/StaffProfile.php)

## [Pathauto Pattern](config/install/pathauto.pattern.staff_profiles.yml)
Pathauto route is created with entity using the format "first_name-last_name" under the path example.com/sitename/people/first_name-last_name

## Forms
Add - Create new profile
Edit - Change existing profile
Delete - Delete existing profile

## Search
Enable the search under config>search and metadata>search pages
Mark staff profiles for indexing (config>search and metadata>search pages and click reindex site)
Run cron to index profiles
Search using search/<search page name>

## Prerequisites
Staff Profile requires the following modules:
* drupal:views
* telephone
* pathauto
* Views Bulk Operations
