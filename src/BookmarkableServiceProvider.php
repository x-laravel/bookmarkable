<?php

namespace XLaravel\Bookmarkable;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use XLaravel\Bookmarkable\Support\BookmarkableConfig;

class BookmarkableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/bookmarkable.php', 'bookmarkable');
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'bookmarkable');

        $this->callAfterResolving(Schedule::class, $this->schedulePruning(...));

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../config/bookmarkable.php' => $this->app->configPath('bookmarkable.php'),
        ], 'bookmarkable-config');

        $this->publishesMigrations([
            __DIR__ . '/../database/migrations' => $this->app->databasePath('migrations'),
        ], 'bookmarkable-migrations');

        $this->publishes([
            __DIR__ . '/../lang' => $this->app->langPath('vendor/bookmarkable'),
        ], 'bookmarkable-translations');
    }

    private function schedulePruning(Schedule $schedule): void
    {
        if (! Config::boolean('bookmarkable.prune.enabled', true)) {
            return;
        }

        $frequency = Config::string('bookmarkable.prune.schedule', 'daily');

        $event = $schedule->command('model:prune', [
            '--model' => [BookmarkableConfig::folderModel(), BookmarkableConfig::bookmarkModel()],
            '--chunk' => Config::integer('bookmarkable.prune.chunk', 1000),
        ]);

        if (str_contains($frequency, ' ')) {
            $event->cron($frequency);

            return;
        }

        [$method, $arguments] = array_pad(explode(':', $frequency, 2), 2, '');

        if ($method === '' || ! method_exists($event, $method)) {
            throw new InvalidArgumentException("[{$frequency}] is not a schedule frequency.");
        }

        $event->{$method}(...($arguments === '' ? [] : explode(',', $arguments)));
    }
}
