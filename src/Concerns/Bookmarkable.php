<?php

namespace XLaravel\Bookmarkable\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use XLaravel\Bookmarkable\Contracts\CanBookmark;
use XLaravel\Bookmarkable\Models\Bookmark;
use XLaravel\Bookmarkable\Support\BookmarkableConfig;

/**
 * @mixin Model
 */
trait Bookmarkable
{
    public static function bootBookmarkable(): void
    {
        static::deleted(function (Model $model) {
            /** @var Model&self $model */
            $model->forgetBookmarksWhenGone();
        });
    }

    /**
     * @return MorphMany<Bookmark, $this>
     */
    public function bookmarkEntries(): MorphMany
    {
        return $this->morphMany(BookmarkableConfig::bookmarkModel(), 'bookmarkable');
    }

    public function isBookmarkedBy((Model&CanBookmark)|null $owner): bool
    {
        if ($owner === null) {
            return false;
        }

        return $this->bookmarkEntries()
            ->whereHas('folder', fn (Builder $folder) => $folder->whereMorphedTo('owner', $owner))
            ->exists();
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeWithBookmarkedBy(Builder $query, (Model&CanBookmark)|null $owner): void
    {
        $query->withExists(['bookmarkEntries as is_bookmarked' => function (Builder $bookmarks) use ($owner) {
            if ($owner === null) {
                $bookmarks->whereRaw('1 = 0');

                return;
            }

            $bookmarks->whereHas('folder', fn (Builder $folder) => $folder->whereMorphedTo('owner', $owner));
        }]);
    }

    protected function forgetBookmarksWhenGone(): void
    {
        if ($this->exists) {
            return;
        }

        $this->bookmarkEntries()->delete();
    }
}
