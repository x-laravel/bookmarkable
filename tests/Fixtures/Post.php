<?php

namespace XLaravel\Bookmarkable\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use XLaravel\Bookmarkable\Concerns\Bookmarkable;
use XLaravel\Bookmarkable\Contracts\HasBookmarks;

/**
 * @property int $id
 * @property string $title
 * @property bool $is_bookmarked
 */
class Post extends Model implements HasBookmarks
{
    use Bookmarkable;
    use SoftDeletes;

    public $timestamps = false;

    protected $guarded = [];
}
