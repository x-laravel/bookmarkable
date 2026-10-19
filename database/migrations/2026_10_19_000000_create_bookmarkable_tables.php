<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use XLaravel\Bookmarkable\Support\BookmarkableConfig;

return new class extends Migration
{
    public function up(): void
    {
        $folders = BookmarkableConfig::folderTable();

        Schema::connection($this->getConnection())->create($folders, function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->morphs('owner');
            $table->string('name');
            $table->boolean('is_default')->nullable();
            $table->boolean('is_private')->default(true);
            $table->unsignedInteger('sort_order')->nullable();
            $table->timestamps();

            $table->unique(['owner_type', 'owner_id', 'is_default']);
        });

        $bookmarks = BookmarkableConfig::bookmarkTable();

        Schema::connection($this->getConnection())->create($bookmarks, function (Blueprint $table) use ($folders, $bookmarks) {
            $table->id();
            $table->foreignUlid('bookmark_folder_id')->constrained($folders)->cascadeOnDelete();
            $table->morphs('bookmarkable');
            $table->timestamp('created_at')->nullable();

            $table->unique(['bookmark_folder_id', 'bookmarkable_type', 'bookmarkable_id'], "{$bookmarks}_folder_bookmarkable_unique");
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists(BookmarkableConfig::bookmarkTable());
        Schema::connection($this->getConnection())->dropIfExists(BookmarkableConfig::folderTable());
    }

    public function getConnection(): ?string
    {
        return BookmarkableConfig::connection();
    }
};
