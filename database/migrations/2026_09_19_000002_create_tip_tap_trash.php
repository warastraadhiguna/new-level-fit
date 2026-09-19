<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tip_tap_trash', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->string('kind', 20);
            $table->unsignedBigInteger('original_id');
            $table->unsignedBigInteger('member_id')->index();
            $table->string('label');
            $table->string('member_code')->nullable()->index();
            $table->string('card_number')->nullable()->index();
            $table->longText('payload');
            $table->timestamp('deleted_at');
            $table->unique(['kind', 'original_id']);
        });
        Schema::create('tip_tap_locks', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->unsignedInteger('id')->primary();
        });
        DB::table('tip_tap_locks')->insert(['id' => 1]);
    }

    public function down(): void
    {
        if (DB::table('tip_tap_trash')->exists()) {
            throw new RuntimeException('Restore atau hapus permanen seluruh tempat sampah sebelum rollback Tip-Tap.');
        }
        Schema::dropIfExists('tip_tap_trash');
        Schema::dropIfExists('tip_tap_locks');
    }
};
