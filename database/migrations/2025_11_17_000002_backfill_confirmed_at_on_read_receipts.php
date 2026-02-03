<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('read_receipts')
            ->whereNull('confirmed_at')
            ->whereNull('reminded_at')
            ->update(['confirmed_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        // no-op revert: keep confirmed_at as-is
    }
};
