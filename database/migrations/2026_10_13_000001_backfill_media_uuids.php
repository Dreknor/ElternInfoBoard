<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Mediendateien aus der Zeit vor der Spalte `uuid` (Migration 2021_03_16) haben keine UUID.
 * `route('api.files.download', …)` brach dafür mit einer UrlGenerationException ab (/api/files → 500).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('media', 'uuid')) {
            return;
        }

        DB::table('media')
            ->where(fn ($q) => $q->whereNull('uuid')->orWhere('uuid', ''))
            ->orderBy('id')
            ->select('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('media')->where('id', $row->id)->update(['uuid' => (string) Str::uuid()]);
                }
            });
    }

    public function down(): void
    {
        // Nachgetragene UUIDs bleiben erhalten (werden bereits in Links verwendet).
    }
};
