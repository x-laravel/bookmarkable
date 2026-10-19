# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-10-19

### Added

- `Contracts\CanBookmark` and `Concerns\Bookmarker` for the model that saves,
  `Contracts\HasBookmarks` and `Concerns\Bookmarkable` for the models that can be saved.
- A private default folder per owner, created on the first bookmark, that cannot be renamed, shared
  or deleted.
- Folders of the owner's own with ULID keys, a private or shared flag and `ordered()` listing by
  position and last activity.
- `bookmark()`, `unbookmark()` and `hasBookmarked()` for the default folder, a given folder or every
  folder.
- `withBookmarkedBy()` scope and `isBookmarkedBy()` to show the saved state of a list in one query.
- Cleanup of folders and bookmarks when their owner or item is deleted, and pruning of left-behind
  rows through `model:prune`, scheduled by the package with a configurable frequency and chunk size.
- `Events\Bookmarked` and `Events\Unbookmarked`, dispatched after commit.
- Configurable default folder name, connection, tables and models, with Turkish and English
  messages.

[1.0.0]: https://github.com/x-laravel/bookmarkable/releases/tag/v1.0.0
