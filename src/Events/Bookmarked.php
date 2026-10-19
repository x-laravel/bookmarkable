<?php

namespace XLaravel\Bookmarkable\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use XLaravel\Bookmarkable\Models\Bookmark;

final readonly class Bookmarked implements ShouldDispatchAfterCommit
{
    public function __construct(
        public Bookmark $bookmark,
    ) {
    }
}
