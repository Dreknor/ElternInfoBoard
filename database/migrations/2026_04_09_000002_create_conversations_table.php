<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['group', 'direct'])->default('group');
            $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->string('title')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('auto_delete_days')->default(90);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('conversation_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('muted_until')->nullable();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_user');
        Schema::dropIfExists('conversations');
    }
};

