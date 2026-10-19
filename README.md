# Bookmarkable for Laravel

<p align="center">
<a href="https://github.com/X-Laravel/bookmarkable/actions/workflows/tests.yml"><img src="https://github.com/X-Laravel/bookmarkable/actions/workflows/tests.yml/badge.svg" alt="Tests"></a>
<a href="https://packagist.org/packages/x-laravel/bookmarkable" rel="nofollow"><img src="https://img.shields.io/packagist/v/x-laravel/bookmarkable" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/x-laravel/bookmarkable" rel="nofollow"><img src="https://img.shields.io/packagist/php-v/x-laravel/bookmarkable" alt="PHP Version"></a>
<a href="https://packagist.org/packages/x-laravel/bookmarkable" rel="nofollow"><img src="https://img.shields.io/packagist/dt/x-laravel/bookmarkable" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/x-laravel/bookmarkable" rel="nofollow"><img src="https://img.shields.io/packagist/l/x-laravel/bookmarkable" alt="License"></a>
</p>

Instagram-style saving for Eloquent: one tap saves an item to the owner's private default folder,
and the owner can also file it into folders of their own. Any model can own folders, any model can
be saved, and one item can sit in several folders at once.

## Requirements

| Package version | PHP | Laravel |
|-----------------|-----|---------|
| 1.x | 8.4+ | 13 |

## Installation

```bash
composer require x-laravel/bookmarkable
php artisan vendor:publish --tag=bookmarkable-migrations
php artisan migrate
```

Publish the config to change the defaults:

```bash
php artisan vendor:publish --tag=bookmarkable-config
```

## Preparing the Models

Implement `CanBookmark` and use `Bookmarker` on the model that saves. Implement `HasBookmarks` and
use `Bookmarkable` on every model that can be saved; only those models are accepted:

```php
use XLaravel\Bookmarkable\Concerns\Bookmarkable;
use XLaravel\Bookmarkable\Concerns\Bookmarker;
use XLaravel\Bookmarkable\Contracts\CanBookmark;
use XLaravel\Bookmarkable\Contracts\HasBookmarks;

class User extends Authenticatable implements CanBookmark
{
    use Bookmarker;
}

class Event extends Model implements HasBookmarks
{
    use Bookmarkable;
}
```

The `owner_id` and `bookmarkable_id` columns follow Laravel's morph key type, so set
`Schema::defaultMorphKeyType('uuid')` or `'ulid'` before migrating if your models use them.

## Configuration

| Key | Default | Meaning |
|-----|---------|---------|
| `default_folder_name` | `null` | Name of the default folder; `null` uses the `default_folder` translation |
| `connection` | `null` | Connection of both tables |
| `folder.table` | `'bookmark_folders'` | Name of the folder table |
| `folder.model` | `BookmarkFolder::class` | Folder model; must extend the package's |
| `bookmark.table` | `'bookmarks'` | Name of the bookmark table |
| `bookmark.model` | `Bookmark::class` | Bookmark model; must extend the package's |
| `prune.enabled` | `true` | Schedule the pruning of left-behind rows |
| `prune.schedule` | `'daily'` | A cron expression, or a frequency method with its arguments: `'dailyAt:03:30'`, `'twiceDaily:1,13'`, `'weeklyOn:1,04:00'` |
| `prune.chunk` | `1000` | Rows deleted by each pruning query |

## Saving

```php
$user->bookmark($event);                  // into the default folder
$user->bookmark($event, $folder);         // into one of the owner's folders
$user->unbookmark($event);                // out of every folder
$user->unbookmark($event, $folder);       // out of one folder
$user->hasBookmarked($event);             // in any folder
$user->hasBookmarked($event, $folder);    // in that folder
```

Saving an item twice into the same folder keeps a single bookmark; the returned bookmark's
`wasRecentlyCreated` tells which call created it. A folder of another owner is refused with
`InvalidArgumentException`.

## Folders

```php
$user->defaultBookmarkFolder();
$folder = $user->createBookmarkFolder('Concerts');
$shared = $user->createBookmarkFolder('Weekend', private: false);

$user->bookmarkFolders()->ordered()->withCount('bookmarks')->get();
$user->reorderBookmarkFolders([$shared->id, $folder->id]);

$folder->add($event);
$folder->remove($event);
$folder->contains($event);
$folder->bookmarks()->with('bookmarkable')->latest()->paginate();
```

Folder keys are ULIDs, so they can appear in URLs without revealing how many folders exist.
Folders are private unless created otherwise; the package only stores the flag, and who may see a
shared folder is up to your application.

The default folder is created on the owner's first bookmark, or by `defaultBookmarkFolder()`. Each
owner has exactly one, enforced by a unique index. It is always private, and renaming, sharing or
deleting it throws `DefaultFolderException`.

`ordered()` lists the default folder first, then folders that were never given a position by their
last activity, then the rest by the position `reorderBookmarkFolders()` gave them. Adding an item
counts as activity.

## Showing the Saved State

Load whether each item of a list is saved by the current user in one query:

```php
$events = Event::query()->withBookmarkedBy($request->user())->paginate();

$events->first()->is_bookmarked;
```

A `null` owner, such as a guest, gets `false` for every item. For a single model, use
`$event->isBookmarkedBy($user)`.

## Deleting and Pruning

When an owner is deleted, its folders and bookmarks are deleted with it; when a saved item is
deleted, it is removed from every folder. Soft-deleted models keep their bookmarks until they are
force deleted.

Deletes that skip model events, such as bulk queries, database cascades or raw SQL, leave folders
and bookmarks behind. The package schedules `model:prune` for its two models, daily by default, to
remove folders whose owner and bookmarks whose item or folder no longer exist. Run it by hand with:

```bash
php artisan model:prune --model="XLaravel\Bookmarkable\Models\BookmarkFolder" --model="XLaravel\Bookmarkable\Models\Bookmark"
```

Pruning skips types that no longer resolve to a model and models on another connection, and
dispatches no `Unbookmarked` events.

## Reacting to Bookmarks

`XLaravel\Bookmarkable\Events\Bookmarked` and `Unbookmarked` carry the `Bookmark` and are
dispatched after the surrounding transaction commits. Saving an item that is already in the folder
dispatches nothing.

## Customising the Messages

The exceptions and the default folder name come with Turkish and English messages:

```bash
php artisan vendor:publish --tag=bookmarkable-translations
```

## Testing

```bash
composer install
composer test
composer analyse
```

## License

Open source software licensed under the [MIT license](LICENSE).
