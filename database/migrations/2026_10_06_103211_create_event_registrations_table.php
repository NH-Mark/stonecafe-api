<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            $table->string('full_name');
            $table->string('email');
            $table->string('phone', 30);
            $table->string('gender', 20);
            $table->string('company')->nullable();

            // Registration status
            $table->enum('status', [
                'pending',
                'confirmed',
                'cancelled',
            ])->default('pending');

            // Payment status
            $table->enum('payment_status', [
                'not_required',
                'pending',
                'paid',
                'failed',
                'refunded',
            ])->default('not_required');

            $table->decimal('payment_amount', 10, 2)->nullable();
            $table->string('payment_currency', 3)->nullable();

            // Payment gateway information
            $table->string('payment_reference')->nullable();
            $table->string('payment_transaction_id')->nullable();

            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->index('email');
            $table->index('status');
            $table->index('payment_status');

            // Prevent the same person registering twice for the same event
            $table->unique(['event_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
    }
};