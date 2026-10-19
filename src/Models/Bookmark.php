<?php

namespace XLaravel\Bookmarkable\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use XLaravel\Bookmarkable\Events\Bookmarked;
use XLaravel\Bookmarkable\Events\Unbookmarked;
use XLaravel\Bookmarkable\Support\BookmarkableConfig;
use XLaravel\Bookmarkable\Support\MissingMorphTargets;

/**
 * @property int $id
 * @property string $bookmark_folder_id
 * @property string $bookmarkable_type
 * @property int|string $bookmarkable_id
 * @property Carbon|null $created_at
 */
class Bookmark extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;

    protected $guarded = [];

    /**
     * @var list<string>
     */
    protected $touches = ['folder'];

    protected static function booted(): void
    {
        static::created(function (Bookmark $bookmark) {
            Event::dispatch(new Bookmarked($bookmark));
        });

        static::deleted(function (Bookmark $bookmark) {
            Event::dispatch(new Unbookmarked($bookmark));
        });
    }

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return MissingMorphTargets::where(static::query(), 'bookmarkable')->orWhereDoesntHave('folder');
    }

    public function getTable(): string
    {
        return BookmarkableConfig::bookmarkTable();
    }

    public function getConnectionName(): ?string
    {
        return BookmarkableConfig::connection() ?? parent::getConnectionName();
    }

    /**
     * @return BelongsTo<BookmarkFolder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(BookmarkableConfig::folderModel(), 'bookmark_folder_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function bookmarkable(): MorphTo
    {
        return $this->morphTo();
    }
}
