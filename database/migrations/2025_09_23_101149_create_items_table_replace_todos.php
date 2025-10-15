<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the existing todos table
        Schema::dropIfExists('todos');

        // Create todos table for todo-specific data
        Schema::create('todos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('completed')->default(false);
            $table->timestamps();
        });

        // Create checklists table for checklist-specific data
        Schema::create('checklists', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('items')->nullable(); // Array of checklist items with their completion status
            $table->timestamps();
        });

        // Create folders table for folder-specific data
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable(false)->unique();
            $table->string('name');
            $table->string('color')->default('#3b82f6'); // Default blue color
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Create notes table
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('color')->default('#FEF3C7');
            $table->boolean('pinned')->default(false);
            $table->timestamps();
        });

        // Create bookmarks table
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('url');
            $table->string('favicon_url')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
        });

        // Create events table
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();
            $table->string('location')->nullable();
            $table->boolean('all_day')->default(false);
            $table->integer('remind_minutes_before')->nullable();
            $table->timestamps();
        });

        // Create the base items table for polymorphic relationships
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('board_id')->constrained('boards')->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('folders')->cascadeOnDelete();
            $table->string('itemable_type'); // The model type (Todo, Checklist, Folder, Note, Bookmark, Event)
            $table->unsignedBigInteger('itemable_id'); // The ID of the related model
            $table->integer('x')->default(0);
            $table->integer('y')->default(0);
            $table->integer('width')->default(200);
            $table->integer('height')->default(100);
            $table->timestamps();

            $table->index(['itemable_type', 'itemable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop all the new tables (drop items first due to FK to folders)
        Schema::dropIfExists('items');
        Schema::dropIfExists('events');
        Schema::dropIfExists('bookmarks');
        Schema::dropIfExists('notes');
        Schema::dropIfExists('folders');
        Schema::dropIfExists('checklists');
        Schema::dropIfExists('todos');

        // Recreate the old todos table structure
        Schema::create('todos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('completed')->default(false);
            $table->integer('x')->default(0);
            $table->integer('y')->default(0);
            $table->integer('width')->default(200);
            $table->integer('height')->default(100);
            $table->timestamps();
        });
    }
};
