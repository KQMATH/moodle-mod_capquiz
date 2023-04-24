# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org).

## [Unreleased]

## [0.6.2]

This release is made primarily because of 0.6.1 did not get new
version numbers and is therefore not detected by Moodle's upgrade
system.

- Compliance with continuous integration (CI) tests
    - Improved Code Style 
    - Added PHP Doc
- Some refactoring

## [0.6.1]

### Fixed

- use global $PAGE instead of undefined $this->page in classlist renderer.

## [0.6.0]

Tested for Moodle 3.10 and 3.9.

### Fixed

- Performance improvements.  Fixes #167
- #193 fix foreign key user id for capquiz attempt

## [0.5.0] - 2020-09-16

### Removed

- Commenting system for student feedback is removed in favour of the [QTracker plugin](https://github.com/KQMATH/moodle-local_qtracker).

## [0.4.2] - 2020-09-15

## [0.4.1] - 2019-10-24
### Fixed
- Error in question rating update. #161

## [0.4.0] - 2019-07-30
### Added
- Framework for CAPQuiz **report** sub-plugins
- New **Attempts** report sub-plugin where one can view/generate reports on CAPQuiz attempts made within the activity.
- New **Questions** report sub-plugin where one can view/generate reports on the development of CAPQuiz question ratings over time.
- Ability to manage the question bank in the context of the CAPQuiz activity, via new link in the navigation menu.
- Check out the commented question in the comments pane (teacher view) by providing link to question preview.

### Changed
- Minor styling changes

### Fixed
- Add some missing language strings
- Wrong contexts were exported when exporting user data through the Privacy API (GDPR)

## [0.3.2] - 2019-07-01

## [0.3.1] - 2019-07-01
### Fixed
- Add some missing language strings

## [0.3.0] - 2019-06-27
### Added
- Added support for Moodle grading system
- Added button to add all selected (checked) questions to question list
- Number of stars is now configurable
- Class list is improved (sorting by columns)

### Changed
- Merged some tabs to simplify user interface
- Rounded user ratings in class list

### Fixed
- Add some missing language strings

## [0.2.0] - 2019-06-24
### Added
* Questions can now be sorted by name and rating
* Students can now comment on questions to give feedback to the instructor
* Added tooltips to stars to inform what they mean
* GDPR compliance
* Ability to import from question list templates
* Ability to delete question list templates
* Added "Edit" and "Preview" button to questions in Edit tab

### Changed
* Better star visualization (dimmed star to show "lost" stars)
* Some style improvements to Edit tab

### Fixed
* Various bugfixes

## [0.1.5] - 2019-03-08
### Fixed
* Fix language file to support AMOS translation system.

## [0.1.4] - 2019-01-21
### Fixed
* Fix CSS coding style errors.

## [0.1.3] - 2019-01-21
### Added
* Implementation of the backup API.
### Fixed
* Fixed bugs in question bank.

## [0.1.2] - 2018-11-10

## [0.1.1] - 2018-10-30

## 0.1.0 - 2018-09-28

[Unreleased]: https://github.com/KQMATH/moodle-mod_capquiz/compare/v0.6.0...HEAD

[0.6.2]: https://github.com/KQMATH/moodle-mod_capquiz/compare/v0.6.1...v0.6.2
[0.6.1]: https://github.com/KQMATH/moodle-mod_capquiz/compare/v0.6.0...v0.6.1
[0.6.0]: https://github.com/KQMATH/moodle-mod_capquiz/compare/v0.5.0...v0.6.0
[0.5.0]: https://github.com/KQMATH/moodle-mod_capquiz/compare/v0.4.2...v0.5.0
[0.4.2]: https://github.com/KQMATH/moodle-mod_capquiz/compare/v0.4.1...v0.4.2
[0.4.1]: https://github.com/KQMATH/moodle-mod_capquiz/compare/v0.4.0...v0.4.1
[0.4.0]: https://github.com/KQMATH/moodle-mod_capquiz/compare/v0.3.2...v0.4.0
[0.3.2]: https://github.com/KQMATH/moodle-mod_capquiz/compare/v0.3.1...v0.3.2
[0.3.1]: https://github.com/KQMATH/moodle-mod_capquiz/compare/v0.3.0...v0.3.1
[0.3.0]: https://github.com/KQMATH/moodle-mod_capquiz/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/KQMATH/moodle-mod_capquiz/compare/v0.1.5...v0.2.0
[0.1.5]: https://github.com/KQMATH/moodle-mod_capquiz/compare/v0.1.4...v0.1.5
[0.1.4]: https://github.com/KQMATH/moodle-mod_capquiz/compare/v0.1.3...v0.1.4
[0.1.3]: https://github.com/KQMATH/moodle-mod_capquiz/compare/v0.1.2...v0.1.3
[0.1.2]: https://github.com/KQMATH/moodle-mod_capquiz/compare/v0.1.1...v0.1.2
[0.1.1]: https://github.com/KQMATH/moodle-mod_capquiz/compare/v0.1.0...v0.1.1
