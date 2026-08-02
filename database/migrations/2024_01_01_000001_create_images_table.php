<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();

            // File information
            $table->string('path');
            $table->string('filename');
            $table->string('original_filename');
            $table->string('extension', 10);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');

            // Image type
            $table->string('type')->index();

            // Dimensions
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();

            // Metadata (alt_text, caption, etc.)
            $table->json('metadata')->nullable();

            // Order and primary flags
            $table->integer('order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_processed')->default(false);

            // Uploader information
            $table->string('uploaded_by_type')->nullable();
            $table->unsignedBigInteger('uploaded_by_id')->nullable();

            // Polymorphic relations
            $table->string('imageable_type')->index();
            $table->unsignedBigInteger('imageable_id')->index();

            $table->timestamps();
            $table->softDeletes();

            // Composite indexes
            $table->index(['imageable_type', 'imageable_id', 'type']);
            $table->index(['uploaded_by_type', 'uploaded_by_id']);
            $table->index(['imageable_type', 'imageable_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
