<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration 1a – users: Globalen UNIQUE(email) durch Composite-UNIQUE(email, ucs_source) ersetzen.
 *
 * Hintergrund: UCS-Eltern können keine E-Mail haben (NULL). Da MySQL/MariaDB keine partiellen
 * Unique-Indizes kennt, wird der alte globale UNIQUE gedroppt und durch einen Composite-UNIQUE
 * ersetzt. Damit:
 *   - lokale Accounts behalten ihre Eindeutigkeit (ucs_source='local' + gleiche E-Mail → Konflikt),
 *   - mehrere NULL-E-Mails sind erlaubt (MySQL-Semantik),
 *   - identische E-Mail darf einmal pro Quelle vorkommen.
 *
 * @see docs/ucs-kelvin-integration-konzept.md §4.1 / §8
 *
 * ⚠  Vor produktivem Einsatz: Index-Namen via `SHOW INDEX FROM users WHERE Column_name='email'`
 *    verifizieren. Standard-Laravel-Name ist `users_email_unique`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Standard-Laravel-Index-Name; ggf. an Datenbankstand anpassen.
            $table->dropUnique('users_email_unique');
            $table->unique(['email', 'ucs_source'], 'users_email_source_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_email_source_unique');
            $table->unique('email', 'users_email_unique');
        });
    }
};

