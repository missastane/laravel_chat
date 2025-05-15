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
            Schema::create('blocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('blocker_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
                $table->unsignedBigInteger('blockable_id');
                $table->string('blockable_type');
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['blocker_id', 'blockable_id', 'blockable_type']);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocks');
    }
};
