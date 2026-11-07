<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('knowledge_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name',160);
            $table->string('slug',180)->unique();
            $table->timestamps();
        });
        Schema::create('knowledge_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('knowledge_categories')->restrictOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->string('title',190);
            $table->string('slug',190)->unique();
            $table->longText('body_html');
            $table->string('status',12)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('subject',190);
            $table->string('status',12)->default('open')->index();
            $table->timestamps();
        });
        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->timestamps();
        });
        Schema::create('vendor_shipping_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->unique()->constrained('vendors')->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->decimal('flat_rate',12,2)->default(0);
            $table->decimal('free_above',12,2)->nullable();
            $table->unsignedTinyInteger('estimated_days')->default(3);
            $table->string('policy_note',300)->nullable();
            $table->timestamps();
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('subtotal',12,2)->default(0);
            $table->decimal('shipping_fee',12,2)->default(0);
            $table->string('shipping_carrier',100)->nullable();
            $table->string('tracking_number',120)->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders',function (Blueprint $table) {
            $table->dropColumn(['subtotal','shipping_fee','shipping_carrier','tracking_number','shipped_at','delivered_at']);
        });
        Schema::dropIfExists('vendor_shipping_profiles');
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('knowledge_articles');
        Schema::dropIfExists('knowledge_categories');
    }
};
