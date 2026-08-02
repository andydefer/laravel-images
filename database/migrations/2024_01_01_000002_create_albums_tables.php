<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('albums', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('cover_image_id')->nullable()->constrained('images')->nullOnDelete();

            $table->boolean('is_public')->default(true);
            $table->boolean('is_featured')->default(false);

            $table->json('metadata')->nullable();

            $table->string('albumable_type')->nullable()->index();
            $table->unsignedBigInteger('albumable_id')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['albumable_type', 'albumable_id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('albums');
    }
};
