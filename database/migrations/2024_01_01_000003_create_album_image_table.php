<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('album_image', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('album_id')
                ->constrained('albums')
                ->onDelete('cascade');

            $table->foreignUuid('image_id')
                ->constrained('images')
                ->onDelete('cascade');

            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->unique(['album_id', 'image_id']);
            $table->index(['album_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('album_image');
    }
};
