<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_category_id')->constrained('treatment_categories')->cascadeOnDelete();
            $table->string('slug')->unique();
            // Adres ze starej strony WordPress (/zabieg/{legacy_slug}/), do przekierowań 301.
            $table->string('legacy_slug')->nullable()->unique();
            $table->string('name');
            $table->text('summary');
            $table->text('description')->nullable();
            $table->json('indications')->nullable();
            $table->json('effects')->nullable();
            $table->string('duration')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->timestamps();

            $table->index(['treatment_category_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatments');
    }
};
