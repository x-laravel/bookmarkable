<?php

namespace XLaravel\Bookmarkable\Tests;

use InvalidArgumentException;
use XLaravel\Bookmarkable\Exceptions\DefaultFolderException;
use XLaravel\Bookmarkable\Models\Bookmark;
use XLaravel\Bookmarkable\Models\BookmarkFolder;
use XLaravel\Bookmarkable\Tests\Fixtures\CustomFolder;
use XLaravel\Bookmarkable\Tests\Fixtures\Post;
use XLaravel\Bookmarkable\Tests\Fixtures\User;

class BookmarkFolderTest extends TestCase
{
    public function test_folder_ids_are_ulids(): void
    {
        $folder = User::query()->create(['name' => 'Ada'])->createBookmarkFolder('Concerts');

        $this->assertSame(26, strlen($folder->id));
    }

    public function test_folders_are_private_unless_told_otherwise(): void
    {
        $user = User::query()->create(['name' => 'Ada']);

        $this->assertTrue($user->createBookmarkFolder('Concerts')->is_private);
        $this->assertFalse($user->createBookmarkFolder('Shared', private: false)->is_private);
    }

    public function test_a_folder_can_be_renamed_shared_and_deleted(): void
    {
        $folder = User::query()->create(['name' => 'Ada'])->createBookmarkFolder('Concerts');
        $folder->bookmarks()->create(['bookmarkable_type' => 'post', 'bookmarkable_id' => 1]);

        $folder->update(['name' => 'Gigs', 'is_private' => false]);
        $folder->delete();

        $this->assertSame(0, BookmarkFolder::query()->count());
        $this->assertSame(0, Bookmark::query()->count());
    }

    public function test_the_default_folder_cannot_be_renamed(): void
    {
        $folder = User::query()->create(['name' => 'Ada'])->defaultBookmarkFolder();

        $this->expectException(DefaultFolderException::class);
        $this->expectExceptionMessage('The default folder cannot be renamed.');

        $folder->update(['name' => 'Other']);
    }

    public function test_the_default_folder_cannot_be_shared(): void
    {
        $folder = User::query()->create(['name' => 'Ada'])->defaultBookmarkFolder();

        $this->expectException(DefaultFolderException::class);

        $folder->update(['is_private' => false]);
    }

    public function test_the_default_folder_cannot_be_deleted(): void
    {
        $folder = User::query()->create(['name' => 'Ada'])->defaultBookmarkFolder();

        $this->expectException(DefaultFolderException::class);

        $folder->delete();
    }

    public function test_the_default_folder_is_always_private(): void
    {
        $user = User::query()->create(['name' => 'Ada']);

        $folder = $user->bookmarkFolders()->create(['name' => 'Saved', 'is_default' => true, 'is_private' => false]);

        $this->assertTrue($folder->is_private);
    }

    public function test_an_owner_has_one_default_folder(): void
    {
        $user = User::query()->create(['name' => 'Ada']);

        $this->assertSame($user->defaultBookmarkFolder()->id, $user->defaultBookmarkFolder()->id);
        $this->assertSame(1, $user->bookmarkFolders()->count());
    }

    public function test_adding_and_removing_an_item(): void
    {
        $folder = User::query()->create(['name' => 'Ada'])->createBookmarkFolder('Concerts');
        $post = Post::query()->create(['title' => 'Rock']);

        $folder->add($post);

        $this->assertTrue($folder->contains($post));
        $this->assertTrue($folder->remove($post));
        $this->assertFalse($folder->contains($post));
        $this->assertFalse($folder->remove($post));
    }

    public function test_adding_an_item_touches_the_folder(): void
    {
        $this->travelTo(now()->subHour());
        $folder = User::query()->create(['name' => 'Ada'])->createBookmarkFolder('Concerts');
        $this->travelBack();

        $folder->add(Post::query()->create(['title' => 'Rock']));

        $this->assertTrue($folder->refresh()->updated_at?->isAfter(now()->subMinute()));
    }

    public function test_the_folder_model_can_be_replaced(): void
    {
        config(['bookmarkable.folder.model' => CustomFolder::class]);

        $folder = User::query()->create(['name' => 'Ada'])->createBookmarkFolder('Concerts');

        $this->assertInstanceOf(CustomFolder::class, $folder);
    }

    public function test_a_replacement_model_must_extend_the_package_model(): void
    {
        config(['bookmarkable.folder.model' => User::class]);

        $this->expectException(InvalidArgumentException::class);

        User::query()->create(['name' => 'Ada'])->createBookmarkFolder('Concerts');
    }
}
