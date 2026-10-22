<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Biblioteka zdjęć: jeden wiersz to jedno zdjęcie w kilku szerokościach WebP (`path`-{szerokość}.webp).
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('alt');
            $table->string('path')->unique();
            $table->json('widths');
            $table->unsignedSmallInteger('width');
            $table->unsignedSmallInteger('height');
            $table->string('color', 32)->nullable();
            $table->string('original_name')->nullable();
            $table->timestamps();
        });

        // Zdjęcie z biblioteki. Usunięcie zdjęcia nie kasuje obszaru, zabiegu ani osoby (panel i tak blokuje usunięcie użytego zdjęcia).
        foreach (['treatment_categories', 'treatments', 'team_members'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('photo_id')->nullable()->constrained('photos')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['treatment_categories', 'treatments', 'team_members'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropConstrainedForeignId('photo_id');
            });
        }

        Schema::dropIfExists('photos');
    }
};
