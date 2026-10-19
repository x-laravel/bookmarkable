<?php

namespace XLaravel\Bookmarkable\Tests;

use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use XLaravel\Bookmarkable\Events\Bookmarked;
use XLaravel\Bookmarkable\Events\Unbookmarked;
use XLaravel\Bookmarkable\Models\Bookmark;
use XLaravel\Bookmarkable\Models\BookmarkFolder;
use XLaravel\Bookmarkable\Tests\Fixtures\Post;
use XLaravel\Bookmarkable\Tests\Fixtures\User;
use XLaravel\Bookmarkable\Tests\Fixtures\Video;

class BookmarkerTest extends TestCase
{
    public function test_first_bookmark_creates_a_private_default_folder(): void
    {
        $user = User::query()->create(['name' => 'Ada']);
        $post = Post::query()->create(['title' => 'Rock']);

        $bookmark = $user->bookmark($post);

        $folder = $user->defaultBookmarkFolder();

        $this->assertSame($folder->id, $bookmark->bookmark_folder_id);
        $this->assertTrue($folder->isDefault());
        $this->assertTrue($folder->is_private);
        $this->assertSame('Saved', $folder->name);
        $this->assertSame(1, $user->bookmarkFolders()->count());
    }

    public function test_default_folder_is_named_in_the_current_locale(): void
    {
        app()->setLocale('tr');

        $user = User::query()->create(['name' => 'Ada']);

        $this->assertSame('Kaydedilenler', $user->defaultBookmarkFolder()->name);
    }

    public function test_default_folder_name_can_be_configured(): void
    {
        config(['bookmarkable.default_folder_name' => 'All posts']);

        $user = User::query()->create(['name' => 'Ada']);

        $this->assertSame('All posts', $user->defaultBookmarkFolder()->name);
    }

    public function test_bookmarking_twice_keeps_one_bookmark(): void
    {
        $user = User::query()->create(['name' => 'Ada']);
        $post = Post::query()->create(['title' => 'Rock']);

        $first = $user->bookmark($post);
        $second = $user->bookmark($post);

        $this->assertTrue($first->wasRecentlyCreated);
        $this->assertFalse($second->wasRecentlyCreated);
        $this->assertSame(1, Bookmark::query()->count());
    }

    public function test_an_item_can_be_in_several_folders(): void
    {
        $user = User::query()->create(['name' => 'Ada']);
        $post = Post::query()->create(['title' => 'Rock']);
        $folder = $user->createBookmarkFolder('Concerts');

        $user->bookmark($post);
        $user->bookmark($post, $folder);

        $this->assertTrue($user->hasBookmarked($post));
        $this->assertTrue($user->hasBookmarked($post, $folder));
        $this->assertSame(2, $user->bookmarks()->count());
    }

    public function test_bookmarks_of_different_types_are_kept_apart(): void
    {
        $user = User::query()->create(['name' => 'Ada']);
        $post = Post::query()->create(['title' => 'Rock']);
        $video = Video::query()->create(['title' => 'Rock']);

        $user->bookmark($post);

        $this->assertTrue($user->hasBookmarked($post));
        $this->assertFalse($user->hasBookmarked($video));
    }

    public function test_another_owners_folder_is_refused(): void
    {
        $user = User::query()->create(['name' => 'Ada']);
        $other = User::query()->create(['name' => 'Grace']);
        $post = Post::query()->create(['title' => 'Rock']);
        $folder = $other->createBookmarkFolder('Concerts');

        $this->expectException(InvalidArgumentException::class);

        $user->bookmark($post, $folder);
    }

    public function test_unbookmark_removes_the_item_from_every_folder(): void
    {
        $user = User::query()->create(['name' => 'Ada']);
        $post = Post::query()->create(['title' => 'Rock']);
        $folder = $user->createBookmarkFolder('Concerts');
        $user->bookmark($post);
        $user->bookmark($post, $folder);

        $this->assertSame(2, $user->unbookmark($post));
        $this->assertFalse($user->hasBookmarked($post));
        $this->assertSame(2, $user->bookmarkFolders()->count());
    }

    public function test_unbookmark_from_one_folder_keeps_the_others(): void
    {
        $user = User::query()->create(['name' => 'Ada']);
        $post = Post::query()->create(['title' => 'Rock']);
        $folder = $user->createBookmarkFolder('Concerts');
        $user->bookmark($post);
        $user->bookmark($post, $folder);

        $this->assertSame(1, $user->unbookmark($post, $folder));
        $this->assertFalse($user->hasBookmarked($post, $folder));
        $this->assertTrue($user->hasBookmarked($post));
    }

    public function test_bookmark_events_are_dispatched(): void
    {
        Event::fake([Bookmarked::class, Unbookmarked::class]);

        $user = User::query()->create(['name' => 'Ada']);
        $post = Post::query()->create(['title' => 'Rock']);

        $user->bookmark($post);
        $user->bookmark($post);
        $user->unbookmark($post);

        Event::assertDispatchedTimes(Bookmarked::class, 1);
        Event::assertDispatchedTimes(Unbookmarked::class, 1);
    }

    public function test_folders_are_ordered_default_first_then_recent_then_by_position(): void
    {
        $user = User::query()->create(['name' => 'Ada']);
        $post = Post::query()->create(['title' => 'Rock']);

        $this->travelTo(now()->subMinutes(10));
        $default = $user->defaultBookmarkFolder();
        $concerts = $user->createBookmarkFolder('Concerts');
        $theatre = $user->createBookmarkFolder('Theatre');
        $user->reorderBookmarkFolders([$theatre->id, $concerts->id]);

        $this->travelBack();
        $first = $user->createBookmarkFolder('First');
        $this->travel(1)->minutes();
        $second = $user->createBookmarkFolder('Second');
        $this->travel(1)->minutes();
        $user->bookmark($post, $first);

        $this->assertSame(
            [$default->id, $first->id, $second->id, $theatre->id, $concerts->id],
            $user->bookmarkFolders()->ordered()->pluck('id')->all(),
        );
    }

    public function test_deleting_the_owner_deletes_its_folders_and_bookmarks(): void
    {
        $user = User::query()->create(['name' => 'Ada']);
        $post = Post::query()->create(['title' => 'Rock']);
        $user->bookmark($post, $user->createBookmarkFolder('Concerts'));
        $user->bookmark($post);

        $user->delete();

        $this->assertSame(2, BookmarkFolder::query()->count());

        $user->forceDelete();

        $this->assertSame(0, BookmarkFolder::query()->count());
        $this->assertSame(0, Bookmark::query()->count());
    }
}
