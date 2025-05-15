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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');   // owner
            $table->foreignId('contact_user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade'); // user that be saved in contacts
            $table->string('contact_name')->nullable();  
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'contact_user_id']); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
