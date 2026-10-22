<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dokumenty prawne edytowane w panelu (na razie polityka prywatności i cookies). Adres strony jest stały
        // (slug), bo linkują go formularz i stopka, więc panel nie pozwala ich zakładać ani usuwać.
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->longText('body');
            $table->date('effective_from')->nullable();
            $table->boolean('needs_review')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
