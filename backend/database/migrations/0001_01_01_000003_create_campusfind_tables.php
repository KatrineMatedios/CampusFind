<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['lost', 'found']);
            $table->string('name');
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->date('date_reported');
            $table->string('location');
            $table->text('description');
            $table->string('photo')->nullable();
            $table->enum('status', ['open', 'claim_pending', 'resolved'])->default('open');
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('date_reported');
        });

        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('identifiers');
            $table->string('proof_photo')->nullable();
            $table->string('pickup_location');
            $table->dateTime('pickup_time');
            $table->string('qr_code')->unique();
            $table->enum('status', ['pending', 'confirmed', 'rejected', 'collected'])->default('pending');
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('description');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('claims');
        Schema::dropIfExists('items');
        Schema::dropIfExists('categories');
    }
};
