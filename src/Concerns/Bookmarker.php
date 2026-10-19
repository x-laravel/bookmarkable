<?php

namespace XLaravel\Bookmarkable\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use InvalidArgumentException;
use LogicException;
use XLaravel\Bookmarkable\Contracts\CanBookmark;
use XLaravel\Bookmarkable\Contracts\HasBookmarks;
use XLaravel\Bookmarkable\Models\Bookmark;
use XLaravel\Bookmarkable\Models\BookmarkFolder;
use XLaravel\Bookmarkable\Support\BookmarkableConfig;

/**
 * @mixin Model
 */
trait Bookmarker
{
    public static function bootBookmarker(): void
    {
        static::deleted(function (Model $model) {
            /** @var Model&self $model */
            $model->forgetBookmarkFoldersWhenGone();
        });
    }

    /**
     * @return MorphMany<BookmarkFolder, $this>
     */
    public function bookmarkFolders(): MorphMany
    {
        return $this->morphMany(BookmarkableConfig::folderModel(), 'owner');
    }

    /**
     * @return HasManyThrough<Bookmark, BookmarkFolder, $this>
     */
    public function bookmarks(): HasManyThrough
    {
        return $this->hasManyThrough(
            BookmarkableConfig::bookmarkModel(),
            BookmarkableConfig::folderModel(),
            'owner_id',
            'bookmark_folder_id',
        )->where(BookmarkableConfig::folderTable() . '.owner_type', $this->getMorphClass());
    }

    public function defaultBookmarkFolder(): BookmarkFolder
    {
        return $this->bookmarkFolders()->firstOrCreate(
            ['is_default' => true],
            ['name' => BookmarkableConfig::defaultFolderName()],
        );
    }

    public function createBookmarkFolder(string $name, bool $private = true): BookmarkFolder
    {
        return $this->bookmarkFolders()->create([
            'name' => $name,
            'is_private' => $private,
        ]);
    }

    /**
     * @param  list<string>  $ids
     */
    public function reorderBookmarkFolders(array $ids): void
    {
        $this->getConnection()->transaction(function () use ($ids) {
            foreach ($ids as $position => $id) {
                $this->bookmarkFolders()->whereKey($id)->update(['sort_order' => $position + 1]);
            }
        });
    }

    public function bookmark(Model&HasBookmarks $bookmarkable, ?BookmarkFolder $folder = null): Bookmark
    {
        return $this->ownBookmarkFolder($folder)->add($bookmarkable);
    }

    public function unbookmark(Model&HasBookmarks $bookmarkable, ?BookmarkFolder $folder = null): int
    {
        $bookmarks = $folder === null
            ? $this->bookmarks()->whereMorphedTo('bookmarkable', $bookmarkable)->get()
            : $this->ownBookmarkFolder($folder)->bookmarks()->whereMorphedTo('bookmarkable', $bookmarkable)->get();

        return $bookmarks->filter(fn (Bookmark $bookmark) => (bool) $bookmark->delete())->count();
    }

    public function hasBookmarked(Model&HasBookmarks $bookmarkable, ?BookmarkFolder $folder = null): bool
    {
        if ($folder === null) {
            return $this->bookmarks()->whereMorphedTo('bookmarkable', $bookmarkable)->exists();
        }

        return $this->ownBookmarkFolder($folder)->contains($bookmarkable);
    }

    protected function ownBookmarkFolder(?BookmarkFolder $folder): BookmarkFolder
    {
        if (! $this instanceof CanBookmark) {
            throw new LogicException(sprintf('Implement %s on [%s].', CanBookmark::class, static::class));
        }

        if ($folder === null) {
            return $this->defaultBookmarkFolder();
        }

        if (! $folder->owner()->is($this)) {
            throw new InvalidArgumentException('The bookmark folder does not belong to this owner.');
        }

        return $folder;
    }

    protected function forgetBookmarkFoldersWhenGone(): void
    {
        if ($this->exists) {
            return;
        }

        BookmarkableConfig::bookmarkModel()::query()
            ->whereIn('bookmark_folder_id', $this->bookmarkFolders()->select('id'))
            ->delete();

        $this->bookmarkFolders()->delete();
    }
}
