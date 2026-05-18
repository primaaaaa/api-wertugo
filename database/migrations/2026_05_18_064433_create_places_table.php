// database/migrations/xxxx_xx_xx_create_places_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('places', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category'); // alam, cafe, aktivitas, kuliner
            $table->text('description');
            $table->string('location'); // wilayah/area
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('contact')->nullable();
            $table->string('social_media')->nullable(); // bisa simpan JSON atau string
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('places');
    }
};