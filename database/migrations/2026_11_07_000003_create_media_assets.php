<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('path', 255)->unique();
            $table->string('original_name', 190);
            $table->string('alt_text', 160)->nullable();
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();
            $table->index(['user_id','created_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('featured_media_id')->nullable()
                ->constrained('media_assets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropConstrainedForeignId('featured_media_id'));
        Schema::dropIfExists('media_assets');
    }
};
