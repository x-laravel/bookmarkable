<?php

namespace XLaravel\Bookmarkable\Tests;

use XLaravel\Bookmarkable\Models\Bookmark;
use XLaravel\Bookmarkable\Tests\Fixtures\Post;
use XLaravel\Bookmarkable\Tests\Fixtures\User;

class BookmarkableTest extends TestCase
{
    public function test_it_knows_who_bookmarked_it(): void
    {
        $user = User::query()->create(['name' => 'Ada']);
        $other = User::query()->create(['name' => 'Grace']);
        $post = Post::query()->create(['title' => 'Rock']);

        $user->bookmark($post, $user->createBookmarkFolder('Concerts'));

        $this->assertTrue($post->isBookmarkedBy($user));
        $this->assertFalse($post->isBookmarkedBy($other));
        $this->assertFalse($post->isBookmarkedBy(null));
    }

    public function test_lists_load_the_bookmark_state_in_one_query(): void
    {
        $user = User::query()->create(['name' => 'Ada']);
        $saved = Post::query()->create(['title' => 'Rock']);
        Post::query()->create(['title' => 'Jazz']);
        $user->bookmark($saved);

        $posts = Post::query()->withBookmarkedBy($user)->orderBy('id')->get();
        $guest = Post::query()->withBookmarkedBy(null)->orderBy('id')->get();

        $this->assertSame([true, false], $posts->pluck('is_bookmarked')->all());
        $this->assertSame([false, false], $guest->pluck('is_bookmarked')->all());
    }

    public function test_bookmarks_of_another_owner_do_not_count(): void
    {
        $user = User::query()->create(['name' => 'Ada']);
        $other = User::query()->create(['name' => 'Grace']);
        $post = Post::query()->create(['title' => 'Rock']);
        $other->bookmark($post);

        $this->assertFalse(Post::query()->withBookmarkedBy($user)->firstOrFail()->is_bookmarked);
    }

    public function test_soft_deleting_keeps_bookmarks_and_force_deleting_removes_them(): void
    {
        $user = User::query()->create(['name' => 'Ada']);
        $post = Post::query()->create(['title' => 'Rock']);
        $user->bookmark($post);

        $post->delete();

        $this->assertSame(1, Bookmark::query()->count());

        $post->forceDelete();

        $this->assertSame(0, Bookmark::query()->count());
    }
}
