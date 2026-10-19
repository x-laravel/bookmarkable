<?php

namespace XLaravel\Bookmarkable\Tests;

use Illuminate\Console\Scheduling\Event as ScheduledEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use XLaravel\Bookmarkable\Events\Unbookmarked;
use XLaravel\Bookmarkable\Models\Bookmark;
use XLaravel\Bookmarkable\Models\BookmarkFolder;
use XLaravel\Bookmarkable\Tests\Fixtures\Post;
use XLaravel\Bookmarkable\Tests\Fixtures\User;
use XLaravel\Bookmarkable\Tests\Fixtures\Video;

class PruningTest extends TestCase
{
    public function test_bookmarks_of_items_that_no_longer_exist_are_pruned(): void
    {
        $user = User::query()->create(['name' => 'Ada']);
        $gone = Post::query()->create(['title' => 'Rock']);
        $kept = Post::query()->create(['title' => 'Jazz']);
        $trashed = Post::query()->create(['title' => 'Blues']);
        $video = Video::query()->create(['title' => 'Rock']);
        $user->bookmark($gone);
        $user->bookmark($kept);
        $user->bookmark($trashed);
        $user->bookmark($video);
        DB::table('posts')->where('id', $gone->id)->delete();
        $trashed->delete();

        $this->prune();

        $this->assertFalse($user->hasBookmarked($gone));
        $this->assertTrue($user->hasBookmarked($kept));
        $this->assertTrue($user->hasBookmarked($trashed));
        $this->assertTrue($user->hasBookmarked($video));
    }

    public function test_folders_of_owners_that_no_longer_exist_are_pruned_with_their_bookmarks(): void
    {
        $gone = User::query()->create(['name' => 'Ada']);
        $kept = User::query()->create(['name' => 'Grace']);
        $post = Post::query()->create(['title' => 'Rock']);
        $gone->bookmark($post);
        $gone->bookmark($post, $gone->createBookmarkFolder('Concerts'));
        $kept->bookmark($post);
        DB::table('users')->where('id', $gone->id)->delete();

        $this->prune();

        $this->assertSame(1, BookmarkFolder::query()->count());
        $this->assertSame(1, Bookmark::query()->count());
        $this->assertTrue($kept->hasBookmarked($post));
    }

    public function test_unknown_types_are_left_alone(): void
    {
        $folder = User::query()->create(['name' => 'Ada'])->createBookmarkFolder('Concerts');
        $folder->bookmarks()->create(['bookmarkable_type' => 'missing', 'bookmarkable_id' => 1]);

        $this->prune();

        $this->assertSame(1, Bookmark::query()->count());
    }

    public function test_pruning_dispatches_no_unbookmarked_events(): void
    {
        $user = User::query()->create(['name' => 'Ada']);
        $post = Post::query()->create(['title' => 'Rock']);
        $user->bookmark($post);
        DB::table('posts')->delete();

        Event::fake([Unbookmarked::class]);

        $this->prune();

        Event::assertNotDispatched(Unbookmarked::class);
    }

    public function test_pruning_is_scheduled_daily_in_chunks_of_a_thousand(): void
    {
        $event = $this->scheduledPruning();

        $this->assertNotNull($event);
        $this->assertSame('0 0 * * *', $event->expression);
        $this->assertStringContainsString('--chunk=1000', (string) $event->command);
    }

    public function test_the_chunk_size_can_be_configured(): void
    {
        config(['bookmarkable.prune.chunk' => 250]);

        $this->assertStringContainsString('--chunk=250', (string) $this->scheduledPruning()?->command);
    }

    public function test_the_schedule_accepts_a_cron_expression(): void
    {
        config(['bookmarkable.prune.schedule' => '30 3 * * *']);

        $this->assertSame('30 3 * * *', $this->scheduledPruning()?->expression);
    }

    public function test_the_schedule_accepts_a_frequency_with_arguments(): void
    {
        config(['bookmarkable.prune.schedule' => 'dailyAt:03:30']);

        $this->assertSame('30 3 * * *', $this->scheduledPruning()?->expression);

        config(['bookmarkable.prune.schedule' => 'weeklyOn:1,04:00']);

        $this->assertSame('0 4 * * 1', $this->scheduledPruning()->expression);
    }

    public function test_an_unknown_frequency_is_refused(): void
    {
        config(['bookmarkable.prune.schedule' => 'sometimes']);

        $this->expectException(InvalidArgumentException::class);

        $this->scheduledPruning();
    }

    public function test_the_schedule_can_be_turned_off(): void
    {
        config(['bookmarkable.prune.enabled' => false]);

        $this->assertNull($this->scheduledPruning());
    }

    protected function prune(): void
    {
        $this->assertSame(0, Artisan::call('model:prune', ['--model' => [BookmarkFolder::class, Bookmark::class]]));
    }

    protected function scheduledPruning(): ?ScheduledEvent
    {
        $this->app?->forgetInstance(Schedule::class);

        $events = $this->app?->make(Schedule::class)->events() ?? [];

        foreach ($events as $event) {
            if (str_contains((string) $event->command, 'model:prune') && str_contains((string) $event->command, 'Bookmark')) {
                return $event;
            }
        }

        return null;
    }
}
