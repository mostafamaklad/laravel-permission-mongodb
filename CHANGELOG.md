# Changelog

All Notable changes to `laravel-permission-mongodb` will be documented in this file.

## 1.3.3-alpha - 2017-09-19

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
