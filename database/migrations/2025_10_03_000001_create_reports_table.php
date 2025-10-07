<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('reporter_user_id')->constrained('users')->onDelete('cascade');
            $table->enum('reason', ['spam', 'inappropriate', 'harassment', 'fake', 'other']);
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'reviewed', 'resolved', 'rejected'])->default('pending');
            $table->text('admin_note')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->timestamps();
            
            // Un utilisateur ne peut signaler qu'une fois le même post
            $table->unique(['post_id', 'reporter_user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('reports');
    }
};