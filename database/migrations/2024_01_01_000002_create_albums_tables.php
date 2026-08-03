<?php

declare(strict_types=1);

use AndyDefer\LaravelCluster\Enums\BinaryChoice;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for creating the albums table.
 *
 * This table manages album collections that can be associated with various
 * models (e.g., Users, Posts, Products) through polymorphic relationships.
 * Albums can contain images and support public/private visibility settings.
 *
 * @category Database
 *
 * @author   Andy Defer
 * @license  MIT
 *
 * @see      https://laravel.com/docs/migrations
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the 'albums' table with all necessary columns for album management,
     * including metadata, privacy settings, and polymorphic relations.
     */
    public function up(): void
    {
        Schema::create('albums', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $this->addBasicInformationColumns($table);
            $this->addCoverImageRelationship($table);
            $this->addVisibilityAndFeatureColumns($table);
            $this->addMetadataColumn($table);
            $this->addPolymorphicRelationColumns($table);
            $this->addTimestampsAndSoftDeletes($table);

            $this->addCompositeIndexes($table);
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the 'albums' table if it exists.
     */
    public function down(): void
    {
        Schema::dropIfExists('albums');
    }

    /**
     * Add basic information columns to the table.
     *
     * These columns store the album name, slug for URLs, and description.
     *
     * @param  Blueprint  $table  The blueprint instance
     */
    private function addBasicInformationColumns(Blueprint $table): void
    {
        $table->string('name');
        $table->string('slug')->unique();
        $table->text('description')->nullable();
    }

    /**
     * Add cover image relationship column.
     *
     * Establishes a foreign key relationship to the images table for
     * the album's cover image.
     *
     * @param  Blueprint  $table  The blueprint instance
     */
    private function addCoverImageRelationship(Blueprint $table): void
    {
        $table->uuid('cover_image_id')->nullable();
        $table->foreign('cover_image_id')
            ->references('id')
            ->on('images')
            ->nullOnDelete();
    }

    /**
     * Add visibility and feature flag columns.
     *
     * Controls album privacy (public/private) and whether it should be
     * highlighted or featured in listings.
     *
     * @param  Blueprint  $table  The blueprint instance
     */
    private function addVisibilityAndFeatureColumns(Blueprint $table): void
    {
        $table->string('is_public')->default(BinaryChoice::YES);
        $table->string('is_featured')->default(BinaryChoice::YES);
    }

    /**
     * Add metadata JSON column.
     *
     * Stores additional album data such as tags, settings, or custom fields.
     *
     * @param  Blueprint  $table  The blueprint instance
     */
    private function addMetadataColumn(Blueprint $table): void
    {
        $table->json('metadata')->nullable();
    }

    /**
     * Add polymorphic relation columns.
     *
     * Links the album to any model (Albumable) such as User, Post, or Product.
     * Both columns are nullable and indexed for query performance.
     *
     * @param  Blueprint  $table  The blueprint instance
     */
    private function addPolymorphicRelationColumns(Blueprint $table): void
    {
        $table->string('albumable_type')->nullable()->index();
        $table->uuid('albumable_id')->nullable()->index();
    }

    /**
     * Add timestamp and soft delete columns.
     *
     * Adds created_at, updated_at, and deleted_at columns for tracking
     * and record recovery.
     *
     * @param  Blueprint  $table  The blueprint instance
     */
    private function addTimestampsAndSoftDeletes(Blueprint $table): void
    {
        $table->timestamps();
        $table->softDeletes();
    }

    /**
     * Add composite indexes for performance optimization.
     *
     * Creates composite indexes for common query patterns involving
     * polymorphic relationships.
     *
     * @param  Blueprint  $table  The blueprint instance
     */
    private function addCompositeIndexes(Blueprint $table): void
    {
        $table->index(['albumable_type', 'albumable_id']);
    }
};
