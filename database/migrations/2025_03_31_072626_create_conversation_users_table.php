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
        Schema::create('conversation_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('last_seen_message_id')
            ->nullable()
            ->constrained('messages')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->tinyInteger('is_admin')->default(2)->comment('0 => conversation is private, 1 => true, 2 => false');
            $table->tinyInteger('status')->default(2)->comment('1 => active, 2 => inactive');
            $table->tinyInteger('is_muted')->default(2)->comment('1 => true, 2 => false');
            $table->tinyInteger('is_favorite')->default(2)->comment('1 => true, 2 => false');
            $table->tinyInteger('is_pinned')->default(2)->comment('1 => true, 2 => false');
            $table->tinyInteger('is_archived')->default(2)->comment('1 => true, 2 => false');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->string('membership_status')->virtualAs("IFNULL(left_at, 'active')")->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['conversation_id', 'user_id', 'membership_status'], 'uniq_active_conversation_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_user');
    }
};
