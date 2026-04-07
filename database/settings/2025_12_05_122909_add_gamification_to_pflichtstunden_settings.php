<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('pflichtstunden.gamification_show_progress', true);
        $this->migrator->add('pflichtstunden.gamification_show_ranking', true);
        $this->migrator->add('pflichtstunden.gamification_show_comparison', true);
    }

    public function down(): void
    {
        $this->migrator->delete('pflichtstunden.gamification_show_progress');
        $this->migrator->delete('pflichtstunden.gamification_show_ranking');
        $this->migrator->delete('pflichtstunden.gamification_show_comparison');
    }
};
