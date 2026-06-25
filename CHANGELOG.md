# Changelog
All notable changes to this plugin will be documented in this file.

## 5.2 - 2026-06-25
### Added
- You can now attempt CAPQuizzes as an instructor, instead of changing your role to student.
  This changes your user rating as normal, but question ratings will now not be affected by your attempts.
- Some question display options can now be configured per CAPQuiz instance.
  The options are used when the user reviews their question attempt.
  Available to show/hide: Specific feedback, general feedback, right answer, and correctness.
- It is now possible to configure which question behavior to use for a CAPQuiz instance.
  Some question behaviors don't work or make sense with CAPQuiz, so they have been disabled.
  These are: *Adaptive mode*, *Deferred feedback*, and *Deferred feedback with CBM*.
  The *Adaptive mode (no penalty)* question behavior is available,
  but is discouraged in favor of *Interactive with multiple tries*.
- You can now easily see if a question has already been added while browsing the question bank.
  The **+** button will be replaced by a checkmark to indicate this.
  This also removes the possibility of adding duplicate questions.

### Changed
- Question lists are no longer separate from CAPQuiz instances.
  This means there is no longer such a thing as question list templates.
  Any existing question list template will be migrated to a CAPQuiz without an opening time (unpublished)
- The "Publish" button has been removed from unpublished CAPQuizzes.
  To make your CAPQuiz available to students, you must now configure the "Open for students" setting with a date.
  Disabling the setting is the same as the unpublished state of earlier CAPQuiz versions.
- Published CAPQuizzes can now be unpublished by disabling the "Open for students" setting.
- Questions now always use the latest version by default. If your CAPQuiz uses old question versions, whether
  intentionally, unknowingly, or frustratingly, they will all be migrated to use the latest question version.
  If you want to use an older version of a question, you can change this manually per question in the *Questions* tab.

### Fixed
- Optimized database queries that should result in huge speedups when you have many users and attempts.
- CAPQuiz instances are now properly deleted. This was an issue for a while.

## 0.8 - 2024-10-07

### Fixed
- Now compatible with new question bank API
- Fixed bug with uploaded images in questions not being displayed
