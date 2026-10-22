<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_groups', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('note')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Ceny w pełnych złotych. price_max wypełnione = zakres, is_from = "od", pack_* = cena pakietu.
        Schema::create('price_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_group_id')->constrained('price_groups')->cascadeOnDelete();
            $table->string('subgroup')->nullable();
            $table->string('name');
            $table->unsignedInteger('price_min')->nullable();
            $table->unsignedInteger('price_max')->nullable();
            $table->boolean('is_from')->default(false);
            $table->unsignedInteger('pack_price')->nullable();
            $table->string('pack_label')->nullable();
            $table->string('note')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['price_group_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_items');
        Schema::dropIfExists('price_groups');
    }
};
