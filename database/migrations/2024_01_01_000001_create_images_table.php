<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the 'images' table with all necessary columns for image management,
     * including file information, metadata, and polymorphic relations.
     */
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $this->addFileInformationColumns($table);
            $this->addImageTypeColumn($table);
            $this->addDimensionsColumns($table);
            $this->addMetadataColumn($table);
            $this->addOrderAndFlagsColumns($table);
            $this->addInverseImageColumn($table);
            $this->addUploaderInformationColumns($table);
            $this->addPolymorphicRelationColumns($table);
            $this->addTimestampsAndSoftDeletes($table);

            $this->addCompositeIndexes($table);
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the 'images' table if it exists.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }

    /**
     * Add file information columns to the table.
     *
     * These columns store file metadata such as path, name, extension,
     * MIME type, and file size.
     *
     * @param  Blueprint  $table  The blueprint instance
     */
    private function addFileInformationColumns(Blueprint $table): void
    {
        $table->string('path');
        $table->string('filename');
        $table->string('original_filename');
        $table->string('extension', 10);
        $table->string('mime_type', 100);
        $table->unsignedBigInteger('size');
    }

    /**
     * Add image type column with index.
     *
     * The type can be used to categorize images (e.g., avatar, cover, thumbnail).
     *
     * @param  Blueprint  $table  The blueprint instance
     */
    private function addImageTypeColumn(Blueprint $table): void
    {
        $table->string('type')->index();
    }

    /**
     * Add image dimensions columns.
     *
     * These columns store width and height for display optimization.
     *
     * @param  Blueprint  $table  The blueprint instance
     */
    private function addDimensionsColumns(Blueprint $table): void
    {
        $table->integer('width')->nullable();
        $table->integer('height')->nullable();
    }

    /**
     * Add metadata JSON column.
     *
     * Stores additional image data such as alt text, caption, etc.
     *
     * @param  Blueprint  $table  The blueprint instance
     */
    private function addMetadataColumn(Blueprint $table): void
    {
        $table->json('metadata')->nullable();
    }

    /**
     * Add order and flag columns.
     *
     * Controls image display order and primary status.
     *
     * @param  Blueprint  $table  The blueprint instance
     */
    private function addOrderAndFlagsColumns(Blueprint $table): void
    {
        $table->integer('order')->default(0);
        $table->boolean('is_primary')->default(false);
        $table->boolean('is_processed')->default(false);
    }

    /**
     * Add inverse image column for dark/light mode variants.
     *
     * @param  Blueprint  $table  The blueprint instance
     */
    private function addInverseImageColumn(Blueprint $table): void
    {
        $table->uuid('inverse_image_id')->nullable();
        $table->foreign('inverse_image_id')
            ->references('id')
            ->on('images')
            ->nullOnDelete();
    }

    /**
     * Add uploader information columns.
     *
     * Stores polymorphic relation to the uploader (Admin, User, etc.).
     *
     * @param  Blueprint  $table  The blueprint instance
     */
    private function addUploaderInformationColumns(Blueprint $table): void
    {
        $table->string('uploaded_by_type')->nullable();
        $table->uuid('uploaded_by_id')->nullable();
    }

    /**
     * Add polymorphic relation columns.
     *
     * Links the image to any model (Imageable).
     *
     * @param  Blueprint  $table  The blueprint instance
     */
    private function addPolymorphicRelationColumns(Blueprint $table): void
    {
        $table->string('imageable_type')->index();
        $table->uuid('imageable_id')->index();
    }

    /**
     * Add timestamp and soft delete columns.
     *
     * Adds created_at, updated_at, and deleted_at columns.
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
     * Creates composite indexes for common query patterns.
     *
     * @param  Blueprint  $table  The blueprint instance
     */
    private function addCompositeIndexes(Blueprint $table): void
    {
        $table->index(['imageable_type', 'imageable_id', 'type']);
        $table->index(['uploaded_by_type', 'uploaded_by_id']);
        $table->index(['imageable_type', 'imageable_id', 'order']);
    }
};
