<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('service_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('gender', ['M', 'F']);
            $table->string('mobile_no');
            $table->string('whatsapp_no')->nullable();
            $table->string('address')->nullable();
            $table->foreignId('district_id')->constrained()->onDelete('cascade');
            $table->json('industries')->nullable(); // Stores services like Plumber, Electrician
            $table->string('photo_url')->nullable();
            $table->string('email');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_providers');
    }
};
