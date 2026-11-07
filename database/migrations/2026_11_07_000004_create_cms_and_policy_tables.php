<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('content_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->string('type', 12)->index();
            $table->string('title', 190);
            $table->string('slug', 190);
            $table->string('excerpt', 400)->nullable();
            $table->longText('body_html');
            $table->string('status', 12)->default('draft')->index();
            $table->string('seo_title', 190)->nullable();
            $table->string('seo_description', 300)->nullable();
            $table->string('featured_type', 12)->default('none');
            $table->foreignId('featured_media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('featured_youtube_id', 20)->nullable();
            $table->string('featured_color', 7)->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['type', 'slug']);
        });

        Schema::create('content_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('editor_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->json('snapshot');
            $table->timestamps();
            $table->unique(['content_entry_id', 'revision']);
        });

        Schema::create('policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('editor_id')->constrained('users')->restrictOnDelete();
            $table->string('title', 190);
            $table->string('slug', 190)->unique();
            $table->longText('body_html');
            $table->string('status', 12)->default('draft')->index();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
        Schema::create('policy_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('editor_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->longText('body_html');
            $table->timestamps();
            $table->unique(['policy_id','revision']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_revisions');
        Schema::dropIfExists('policies');
        Schema::dropIfExists('content_revisions');
        Schema::dropIfExists('content_entries');
    }
};
