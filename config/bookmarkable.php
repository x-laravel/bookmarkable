<?php

use XLaravel\Bookmarkable\Models\Bookmark;
use XLaravel\Bookmarkable\Models\BookmarkFolder;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Folder
    |--------------------------------------------------------------------------
    |
    | Every owner gets one default folder, created on its first bookmark. It
    | cannot be renamed or deleted and is always private.
    |
    | default_folder_name  Name given to the folder when it is created. Null
    |                      uses the "default_folder" translation.
    |
    */

    'default_folder_name' => null,

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | connection  Database connection of both tables. Null uses the default
    |             connection.
    | table       Name of the entity's table.
    | model       Eloquent model of the table. It must extend the package's
    |             own model: XLaravel\Bookmarkable\Models\BookmarkFolder or
    |             XLaravel\Bookmarkable\Models\Bookmark.
    |
    */

    'connection' => null,

    'folder' => [
        'table' => 'bookmark_folders',
        'model' => BookmarkFolder::class,
    ],

    'bookmark' => [
        'table' => 'bookmarks',
        'model' => Bookmark::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pruning
    |--------------------------------------------------------------------------
    |
    | Folders whose owner and bookmarks whose item no longer exist are left
    | behind by deletes that skip model events, such as bulk queries and
    | database cascades. The package schedules model:prune to remove them.
    |
    | enabled   Schedule the pruning.
    | schedule  When the pruning runs, written in one of two ways:
    |           A cron expression:
    |             "30 3 * * *"        every day at 03:30
    |
    |           The name of a Laravel schedule frequency method, with its
    |           arguments after a colon, separated by commas:
    |             "daily"             every day at 00:00
    |             "dailyAt:03:30"     every day at 03:30
    |             "twiceDaily:1,13"   every day at 01:00 and 13:00
    |             "weeklyOn:1,04:00"  every Monday at 04:00
    | chunk     Rows deleted by each query.
    |
    */

    'prune' => [
        'enabled' => true,
        'schedule' => 'daily',
        'chunk' => 1000,
    ],

];
