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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('private_reply_message_id')->nullable()->constrained('messages')->onUpdate('cascade')->onDelete('cascade');
            $table->text('content')->nullable()->comment('it may message will only be media not text');
            $table->tinyInteger('message_type')->default(0)->comment('0 => text, 1 => image, 2 => video, 3 => file');
            $table->tinyInteger('status')->default('0')->comment('0 => sent, 1 => delivered, 2 => read'); 
            $table->timestamp('sent_at')->useCurrent(); 
            $table->foreignId('parent_id')->nullable()->constrained('messages')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('forwarded_message_id')->nullable()->constrained('messages')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
