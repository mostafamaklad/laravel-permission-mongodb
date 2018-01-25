# Changelog

All Notable changes to `laravel-permission-mongodb` will be documented in this file.

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
