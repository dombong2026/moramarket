<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 20)->index();
            $table->string('subject_type', 160)->index();
            $table->unsignedBigInteger('subject_id')->index();
            $table->json('changed_fields')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['created_at','action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
