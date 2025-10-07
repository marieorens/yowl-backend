<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ratings') && !Schema::hasTable('reactions')) {
            Schema::rename('ratings', 'reactions');
        }
        
        Schema::table('reactions', function (Blueprint $table) {
            // Drop columns if they exist
            if (Schema::hasColumn('reactions', 'rating')) {
                $table->dropColumn('rating');
            }
            if (Schema::hasColumn('reactions', 'comment')) {
                $table->dropColumn('comment');
            }
            // Add type column if it doesn't exist
            if (!Schema::hasColumn('reactions', 'type')) {
                $table->enum('type', ['like', 'dislike'])->after('post_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reactions', function (Blueprint $table) {
            if (Schema::hasColumn('reactions', 'type')) {
                $table->dropColumn('type');
            }
            if (!Schema::hasColumn('reactions', 'comment')) {
                $table->text('comment')->nullable();
            }
            if (!Schema::hasColumn('reactions', 'rating')) {
                $table->unsignedTinyInteger('rating');
            }
        });
        
        if (Schema::hasTable('reactions') && !Schema::hasTable('ratings')) {
            Schema::rename('reactions', 'ratings');
        }
    }
};
