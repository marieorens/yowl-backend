<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts', 'average_rating')) {
                $table->dropColumn('average_rating');
            }
            if (Schema::hasColumn('posts', 'ratings_count')) {
                $table->dropColumn('ratings_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->decimal('average_rating', 3, 2)->nullable();
            $table->integer('ratings_count')->default(0);
        });
    }
};
