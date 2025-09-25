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

        // Create the base items table for polymorphic relationships
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('board_id')->constrained('boards')->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('boards')->cascadeOnDelete();
            $table->string('itemable_type'); // The model type (Todo, Checklist, Folder)
            $table->unsignedBigInteger('itemable_id'); // The ID of the related model
            $table->integer('x')->default(0);
            $table->integer('y')->default(0);
            $table->integer('width')->default(200);
            $table->integer('height')->default(100);
            $table->timestamps();

            $table->index(['itemable_type', 'itemable_id']);
        });

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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop all the new tables (drop items first due to FK to folders)
        Schema::dropIfExists('items');
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
