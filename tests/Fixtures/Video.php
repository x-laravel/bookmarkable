<?php

namespace XLaravel\Bookmarkable\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use XLaravel\Bookmarkable\Concerns\Bookmarkable;
use XLaravel\Bookmarkable\Contracts\HasBookmarks;

/**
 * @property int $id
 * @property string $title
 */
class Video extends Model implements HasBookmarks
{
    use Bookmarkable;

    public $timestamps = false;

    protected $guarded = [];
}
