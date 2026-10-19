<?php

namespace XLaravel\Bookmarkable\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use XLaravel\Bookmarkable\Contracts\HasBookmarks;
use XLaravel\Bookmarkable\Exceptions\DefaultFolderException;
use XLaravel\Bookmarkable\Support\BookmarkableConfig;
use XLaravel\Bookmarkable\Support\MissingMorphTargets;

/**
 * @property string $id
 * @property string $owner_type
 * @property int|string $owner_id
 * @property string $name
 * @property bool $is_default
 * @property bool $is_private
 * @property int|null $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class BookmarkFolder extends Model
{
    use HasUlids;
    use MassPrunable;

    protected $guarded = [];

    protected $attributes = [
        'is_private' => true,
    ];

    protected static function booted(): void
    {
        static::creating(function (BookmarkFolder $folder) {
            if ($folder->isDefault()) {
                $folder->is_private = true;
            }
        });

        static::updating(function (BookmarkFolder $folder) {
            if (! $folder->isDefault()) {
                return;
            }

            if ($folder->isDirty('name')) {
                throw DefaultFolderException::cannotRename();
            }

            if (! $folder->is_private) {
                throw DefaultFolderException::cannotShare();
            }
        });

        static::deleting(function (BookmarkFolder $folder) {
            if ($folder->isDefault()) {
                throw DefaultFolderException::cannotDelete();
            }

            $folder->bookmarks()->delete();
        });
    }

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return MissingMorphTargets::where(static::query(), 'owner');
    }

    public function getTable(): string
    {
        return BookmarkableConfig::folderTable();
    }

    public function getConnectionName(): ?string
    {
        return BookmarkableConfig::connection() ?? parent::getConnectionName();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getIsDefaultAttribute(mixed $value): bool
    {
        return (bool) $value;
    }

    public function setIsDefaultAttribute(mixed $value): void
    {
        $this->attributes['is_default'] = $value ? true : null;
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<Bookmark, $this>
     */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(BookmarkableConfig::bookmarkModel(), 'bookmark_folder_id');
    }

    public function isDefault(): bool
    {
        return $this->is_default;
    }

    public function add(Model&HasBookmarks $bookmarkable): Bookmark
    {
        return $this->bookmarks()->createOrFirst([
            'bookmarkable_type' => $bookmarkable->getMorphClass(),
            'bookmarkable_id' => $bookmarkable->getKey(),
        ]);
    }

    public function remove(Model&HasBookmarks $bookmarkable): bool
    {
        $bookmark = $this->bookmarks()->whereMorphedTo('bookmarkable', $bookmarkable)->first();

        return (bool) $bookmark?->delete();
    }

    public function contains(Model&HasBookmarks $bookmarkable): bool
    {
        return $this->bookmarks()->whereMorphedTo('bookmarkable', $bookmarkable)->exists();
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $grammar = $query->getQuery()->getGrammar();

        /** @var literal-string $isDefault */
        $isDefault = $grammar->wrap($this->qualifyColumn('is_default'));

        /** @var literal-string $sortOrder */
        $sortOrder = $grammar->wrap($this->qualifyColumn('sort_order'));

        $query
            ->orderByRaw("{$isDefault} is null")
            ->orderByRaw("{$sortOrder} is not null")
            ->orderBy($this->qualifyColumn('sort_order'))
            ->orderByDesc($this->qualifyColumn('updated_at'));
    }
}
