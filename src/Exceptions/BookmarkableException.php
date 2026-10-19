<?php

namespace XLaravel\Bookmarkable\Exceptions;

use RuntimeException;

abstract class BookmarkableException extends RuntimeException
{
    final public function __construct(string $message)
    {
        parent::__construct($message);
    }

    protected static function make(string $key): static
    {
        $message = trans("bookmarkable::messages.{$key}");

        return new static(is_string($message) ? $message : $key);
    }
}
