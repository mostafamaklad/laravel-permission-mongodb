# Changelog

All Notable changes to `laravel-permission-mongodb` will be documented in this file.

## 4.0.0 - 2022-02-21

### Added
- Support of Laravel 9.x
- Added some return toward PHP 8 transitioning to require return types
- Use of DatabaseMigration and Seeder in tests
- Fix some tests (api guard is no more in auth.php by default)


## 3.1.0 - 2020-10-04

### Added
 - Support of Laravel 8.x
 
## 3.0.0 - 2020-09-27

### Added
 - Support of Laravel 7.x

## 2.0.1 - 2020-02-23

### Changed
 - Defer registering permissions on the Gate instance until it's resolved

## 2.0.0 - 2020-02-20

### Added
 - Support of Laravel 6.x
 
## 1.10.1 - 2018-09-16
 
### Fixed
 - Fix test coverage

## 1.10.0 - 2018-09-15
 
### Added
 - Add migration files
 
### Changed
 - Update PermissionRegistrar to use Authorizable
 - Improve readme description of how defaults work with multiple guards
 - Replacing static Permission::class and Role::class with dynamic value
 - Improve speed of findByName

## 1.9.0 - 2018-09-14

### Fixed
 - Fix wrong BelongsTo relationship
 - Config cleanup
 - Fixes for Lumen 5.6 compatibility
 - Fix classes resolution to config values
 - Fix permissions via roles
 - Fixed detection of Lumen
 
### Added
 - Add scrutinizer code intelligence
 
### Changed
 - Loose typing definitions for BelongsToMany

## 1.8.2 - 2018-08-14

### Changed
 - Exclude yml files from export

## 1.8.1 - 2018-06-24

### Changed
 - Move permission functionality from HasRoles Trait to HasPermissions Trait

## 1.8.0 - 2018-04-15

### Added
 - Allow assign/sync/remove Roles from Permission model
 
## 1.7.1 - 2018-04-09

### Added
 - Allow missing guard driver param (Spark compatibility)

## 1.7.0 - 2018-03-21

### Added
 - Support getting guard_name from extended model
 - Add required permissions and roles in exception object
 - Add the option to hide and show permissions in exceptions

## 1.6.0 - 2018-02-17

### Added
 - Officially support `laravel 5.6`
 - Improve Lumen support
 
## 1.5.3 - 2018-02-07

### Added
 - add findOrCreate to Permissions
 - add findOrCreate to Roles
 
### Fixed
 - use sync([]) instead of detach()
 - fix soft deleting in laravel 5.2 and 5.3

## 1.5.2 - 2018-01-25

### Added
 - Added multiple Revoke Permissions
 - Added multiple Remove Roles
 - Remove SensioLabsInsight badge


## 1.5.1 - 2018-01-22

### Added
 - Added Lumen support

## 1.5.0 - 2018-01-08

### Added
 - Handle Http Exceptions as Unauthorized Exception
 
## 1.4.0 - 2018-01-01

### Added
 - Officially Support `laravel 5.5`

## 1.3.5 - 2017-10-18

### Added
 - Give Permissions to roles in Command Line

### Fixed
 - Fixed a bug where `Role`s and `Permission`s got detached when soft deleting a model


## 1.3.4 - 2017-09-28

### Added
- Add the support of `laravel 5.2`

## 1.3.3 - 2017-09-27

### Added
- Add the support of `laravel 5.3`


## 1.4.0-alpha - 2017-09-19

### Added
- Add the support of `laravel 5.5`


## 1.3.2 - 2017-09-12

### Removed
- Remove the support of `laravel 5.5` till `jenssegers/laravel-mongodb` supports it


## 1.3.1 - 2017-09-11

### Added
- Add convertToRoleModels and convertToPermissionModels

### Fixed
- Register Blade extensions


## 1.3.0 - 2017-09-09

### Added
- Added permission scope to HasRoles trait
- Update dependencies

### Changed
- Register Blade extensions in boot instead of register


## 1.2.2 - 2017-09-07

### Fixed
- Recreate Exceptions
- Fix most PHP Code Sniffer errors
- Fix some PHP Mess Detector errors


## 1.2.1 - 2017-09-05

### Added
- Let middleware use caching
- Allow logging while exceptions


## 1.2.0 - 2017-09-03

### Added
- Add getRoleNames() method to return a collection of assigned roles
- Add getPermissionNames() method to return a collection of all assigned permissions


## 1.1.0 - 2017-09-01

### Added
- Adding support of `Laravel 5.5`

### Fixed
- Remove the role and permission relation when delete user
- Code quality enhancements


## 1.0.0 - 2017-08-21

### Added
- Everything, initial release
