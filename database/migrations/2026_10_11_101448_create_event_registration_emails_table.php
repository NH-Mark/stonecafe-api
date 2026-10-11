<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_registration_emails', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_registration_id')
                ->constrained('event_registrations')
                ->cascadeOnDelete();

            $table->string('email_type', 32);
            // selected, not_selected, waitlisted

            $table->string('recipient_email');
            $table->string('status', 20)->default('queued');
            // queued, sent, failed

            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index([
                'event_registration_id',
                'email_type',
                'status',
            ], 'registration_email_lookup_index');

            $table->index(['status', 'queued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registration_emails');
    }
};