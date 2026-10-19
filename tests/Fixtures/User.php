<?php

namespace XLaravel\Bookmarkable\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use XLaravel\Bookmarkable\Concerns\Bookmarker;
use XLaravel\Bookmarkable\Contracts\CanBookmark;

/**
 * @property int $id
 * @property string $name
 */
class User extends Model implements CanBookmark
{
    use Bookmarker;
    use SoftDeletes;

    public $timestamps = false;

    protected $guarded = [];
}
