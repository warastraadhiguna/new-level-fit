<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('RENAME TABLE tip_tap_trash TO trashes, tip_tap_locks TO locks');
            return;
        }

        Schema::rename('tip_tap_trash', 'trashes');
        Schema::rename('tip_tap_locks', 'locks');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('RENAME TABLE trashes TO tip_tap_trash, locks TO tip_tap_locks');
            return;
        }

        Schema::rename('trashes', 'tip_tap_trash');
        Schema::rename('locks', 'tip_tap_locks');
    }
};
