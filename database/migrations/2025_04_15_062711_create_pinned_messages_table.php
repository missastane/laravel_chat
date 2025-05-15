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
        Schema::create('pinned_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade'); 
            $table->foreignId('conversation_id')->constrained('conversations')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('message_id')->constrained('messages')->onUpdate('cascade')->onDelete('cascade');
            $table->tinyInteger('is_public')->default(2)->comment('1 => true, 2 => false'); 
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['user_id', 'conversation_id', 'message_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pinned_messages');
    }
};
