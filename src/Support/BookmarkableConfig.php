<?php

namespace XLaravel\Bookmarkable\Support;

use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use XLaravel\Bookmarkable\Models\Bookmark;
use XLaravel\Bookmarkable\Models\BookmarkFolder;

/**
 * @internal
 */
final class BookmarkableConfig
{
    /**
     * @return class-string<BookmarkFolder>
     */
    public static function folderModel(): string
    {
        return self::model('folder', BookmarkFolder::class);
    }

    /**
     * @return class-string<Bookmark>
     */
    public static function bookmarkModel(): string
    {
        return self::model('bookmark', Bookmark::class);
    }

    public static function folderTable(): string
    {
        return self::string('folder.table') ?: 'bookmark_folders';
    }

    public static function bookmarkTable(): string
    {
        return self::string('bookmark.table') ?: 'bookmarks';
    }

    public static function connection(): ?string
    {
        return self::string('connection') ?: null;
    }

    public static function defaultFolderName(): string
    {
        $name = self::string('default_folder_name') ?: trans('bookmarkable::messages.default_folder');

        return is_string($name) ? $name : 'Saved';
    }

    /**
     * @template TModel of BookmarkFolder|Bookmark
     *
     * @param  class-string<TModel>  $base
     * @return class-string<TModel>
     */
    protected static function model(string $key, string $base): string
    {
        $model = Config::get("bookmarkable.{$key}.model") ?? $base;

        if (! is_string($model) || ! is_a($model, $base, true)) {
            throw new InvalidArgumentException("Set bookmarkable.{$key}.model to a model that extends {$base}.");
        }

        return $model;
    }

    protected static function string(string $key): string
    {
        $value = Config::get("bookmarkable.{$key}");

        return is_string($value) ? trim($value) : '';
    }
}
