<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE scans DROP CONSTRAINT IF EXISTS scans_type_check');
            DB::statement('ALTER TABLE scans ALTER COLUMN type TYPE VARCHAR(50)');
            DB::statement('ALTER TABLE scans ALTER COLUMN type SET NOT NULL');
            return;
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE scans MODIFY type VARCHAR(50) NOT NULL");
            return;
        }

        if ($driver === 'sqlite') {
            // SQLite local testing ke liye skip.
            return;
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE scans ALTER COLUMN type TYPE VARCHAR(50)");
            DB::statement("ALTER TABLE scans ADD CONSTRAINT scans_type_check CHECK (type IN ('sms', 'url', 'apk'))");
            return;
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE scans MODIFY type ENUM('sms','url','apk') NOT NULL");
            return;
        }

        if ($driver === 'sqlite') {
            return;
        }
    }
};