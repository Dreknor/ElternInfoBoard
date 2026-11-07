<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul „Elternabfragen“: versionierte Vorlagen, freigegebene Abfragen, Aufträge je Kind,
 * unveränderliche Antwortversionen, Zustell- und Prozessprotokoll sowie Exporte.
 *
 * @see docs/elternabfragen-konzept.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiry_templates', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inquiry_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('inquiry_templates')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('status', 20)->default('draft')->index();
            $table->string('title');
            $table->text('purpose');
            $table->text('parent_info')->nullable();
            $table->string('responsible_office')->nullable();
            $table->string('target_hint')->nullable();
            $table->unsignedSmallInteger('default_deadline_days')->default(14);
            $table->unsignedInteger('retention_days')->nullable();
            $table->text('retention_note')->nullable();
            $table->boolean('is_sensitive')->default(false);
            $table->json('fields');
            $table->text('review_note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('privacy_checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('privacy_checked_at')->nullable();
            $table->timestamps();

            $table->unique(['template_id', 'version']);
        });

        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_version_id')->constrained('inquiry_template_versions')->restrictOnDelete();
            $table->string('title');
            $table->text('intro')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->dateTime('starts_at');
            $table->dateTime('deadline_at');
            $table->string('target_type', 10)->default('groups');
            $table->json('target_group_ids')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('notify_mail')->default(true);
            $table->boolean('notify_push')->default(true);
            $table->boolean('auto_reminders')->default(true);
            $table->boolean('staff_visible')->default(false);
            $table->unsignedSmallInteger('correction_window_days')->default(7);
            $table->unsignedInteger('retention_days')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedInteger('expected_children')->nullable();
            $table->unsignedInteger('expected_accounts')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('purged_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inquiry_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained('inquiries')->cascadeOnDelete();
            $table->foreignId('child_id')->constrained('children')->cascadeOnDelete();
            $table->unsignedBigInteger('group_id')->nullable()->index();
            $table->string('status', 20)->default('open')->index();
            $table->unsignedBigInteger('current_response_id')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('first_submitted_at')->nullable();
            $table->timestamp('last_submitted_at')->nullable();
            $table->dateTime('extended_until')->nullable();
            $table->dateTime('correction_until')->nullable();
            $table->boolean('has_problem')->default(false);
            $table->text('problem_note')->nullable();
            $table->text('note')->nullable();
            $table->unsignedSmallInteger('reminder_count')->default(0);
            $table->timestamp('last_reminded_at')->nullable();
            $table->timestamps();

            $table->unique(['inquiry_id', 'child_id']);
        });

        Schema::create('inquiry_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('inquiry_assignments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('delivery_failed_at')->nullable();
            $table->string('delivery_error')->nullable();
            $table->timestamps();

            $table->unique(['assignment_id', 'user_id']);
        });

        Schema::create('inquiry_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('inquiry_assignments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->json('answers');
            $table->timestamps();

            $table->unique(['assignment_id', 'user_id']);
        });

        Schema::create('inquiry_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('inquiry_assignments')->cascadeOnDelete();
            $table->foreignId('inquiry_id')->constrained('inquiries')->cascadeOnDelete();
            $table->foreignId('child_id')->constrained('children')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('template_version_id')->constrained('inquiry_template_versions')->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->json('answers');
            $table->string('form_hash', 64);
            $table->string('answer_hash', 64);
            $table->unsignedBigInteger('previous_response_id')->nullable();
            $table->text('correction_reason')->nullable();
            $table->boolean('is_current')->default(true)->index();
            $table->string('channel', 10)->default('web');
            $table->string('ip_anonymized', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('submitted_at');
            $table->string('review_status', 20)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->unique(['assignment_id', 'version']);
        });

        Schema::create('inquiry_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained('inquiries')->cascadeOnDelete();
            $table->foreignId('assignment_id')->nullable()->constrained('inquiry_assignments')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kind', 30);
            $table->string('channel', 10);
            $table->string('trigger', 10)->default('auto');
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('success')->default(true);
            $table->string('error')->nullable();
            $table->timestamp('sent_at');

            $table->index(['assignment_id', 'kind']);
        });

        Schema::create('inquiry_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->nullable()->constrained('inquiries')->cascadeOnDelete();
            $table->foreignId('template_version_id')->nullable()->constrained('inquiry_template_versions')->cascadeOnDelete();
            $table->foreignId('assignment_id')->nullable()->constrained('inquiry_assignments')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);
            $table->text('note')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('created_at');
        });

        Schema::create('inquiry_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained('inquiries')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('group_id')->nullable();
            $table->json('fields');
            $table->string('format', 5);
            $table->string('target');
            $table->unsignedInteger('record_count');
            $table->unsignedInteger('missing_count')->default(0);
            $table->string('path')->nullable();
            $table->timestamp('expires_at');
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamp('last_downloaded_at')->nullable();
            $table->timestamp('file_deleted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_exports');
        Schema::dropIfExists('inquiry_events');
        Schema::dropIfExists('inquiry_deliveries');
        Schema::dropIfExists('inquiry_responses');
        Schema::dropIfExists('inquiry_drafts');
        Schema::dropIfExists('inquiry_recipients');
        Schema::dropIfExists('inquiry_assignments');
        Schema::dropIfExists('inquiries');
        Schema::dropIfExists('inquiry_template_versions');
        Schema::dropIfExists('inquiry_templates');
    }
};
