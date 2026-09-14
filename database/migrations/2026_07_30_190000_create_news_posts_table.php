<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_posts', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 255)->unique();
            $table->string('title', 255);
            $table->text('excerpt')->nullable();
            $table->string('cover_image', 512)->nullable();          // storage/public relative path
            $table->json('content_blocks')->nullable();               // shared ContentBlocks Builder output
            $table->string('author', 255)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('status')->default(true);                 // visible when true + published_at <= now()
            $table->timestamps();

            $table->index(['status', 'published_at'], 'news_status_published_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_posts');
    }
};
