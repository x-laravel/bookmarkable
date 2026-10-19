<?php

namespace XLaravel\Bookmarkable\Exceptions;

class DefaultFolderException extends BookmarkableException
{
    public static function cannotRename(): static
    {
        return static::make('cannot_rename_default');
    }

    public static function cannotDelete(): static
    {
        return static::make('cannot_delete_default');
    }

    public static function cannotShare(): static
    {
        return static::make('cannot_share_default');
    }
}
