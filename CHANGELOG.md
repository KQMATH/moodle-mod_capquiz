# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org).

## [Unreleased]
## [0.9.0] 2025-10-01

**Important:** Read changelog before upgrading.
This is a major codebase simplification and refactoring.

- Question lists are no longer separate from CAPQuiz instances.
  This means there is no longer such a thing as question list templates.
  Any existing question list template will be migrated to a CAPQuiz without an opening time (unpublished)
  - The "Publish" button has been removed from unpublished CAPQuizzes.
  To make your CAPQuiz available to students, you must now configure the "Open for students" setting with a date.
  Disabling the setting is the same as the unpublished state of earlier CAPQuiz versions.
  - Published CAPQuizzes can now be unpublished by disabling the "Open for students" setting.
  - You can now attempt CAPQuizzes as an instructor, instead of changing your role to student.
  This changes your user rating as normal, but question ratings will now not be affected by your attempts.
  - It is now possible to configure which question behaviour to use for a CAPQuiz instance.
  Some question behaviours don't work or make sense with CAPQuiz, so they have been disabled.
  These are: *Adaptive mode*, *Deferred feedback*, and *Deferred feedback with CBM*.
  The *Adaptive mode (no penalty)* question behaviour is available,
  but is discouraged in favor of *Interactive with multiple tries*.
  - Some question display options can now be configured per CAPQuiz instance.
  The options are used when the user reviews their question attempt.
  Available to show/hide: Specific feedback, general feedback, right answer, and correctness.
  - Questions now always use the latest version by default. If your CAPQuiz uses old question versions whether
  intentionally, unknowingly, or frustratingly, they will all be migrated to use the latest question version.
  If you want to use an older version of a question, you can change this manually per question in the *Questions* tab.
  - You can now easily see if a question has already been added while browsing the question bank.
  The **+** button will be replaced by a checkmark to indicate this.
  This also removes the possibility of adding duplicate questions.
  - Optimized database queries which should result in huge speedups when you have many users and attempts.
  - CAPQuiz instances are now properly deleted. This was an issue for a while.

## [0.8.0] 2024-10-07

- Now compatible with new question bank API
- Fixed bug with uploaded images in questions not being displayed

## [0.7.0] - 2023-04-28

+ Upgraded to work with Moodle 4.0

Tested for Moodle 4.0, 4.1, 4.0.  Moodle 3.x is no longer supported,
due to the changes in the core quiz/question API.

Note that it is no longer testet with Moodle 3.x.

## [0.6.3] - 2023-04-28

Tested for Moodle 3.11 and 3.9.

### Fixed

- Fixed some code style problems and CI integration

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
