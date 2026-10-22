<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Zdjęcia z biblioteki wstawione w treść wpisu blokami edytora. Tabela jest uzupełniana przy każdym zapisie wpisu
        // (Post::booted), a służy temu, by panel nie pozwolił usunąć zdjęcia, które jest w którymś artykule.
        Schema::create('photo_post', function (Blueprint $table) {
            $table->foreignId('photo_id')->constrained('photos')->cascadeOnDelete();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->primary(['photo_id', 'post_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_post');
    }
};
