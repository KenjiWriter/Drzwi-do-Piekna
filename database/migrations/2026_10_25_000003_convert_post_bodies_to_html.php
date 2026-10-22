<?php

use App\Support\Markdown;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Treść wpisów była najpierw zapisywana jako Markdown, a teraz edytuje się ją w edytorze wizualnym (HTML z blokami).
     * Istniejące wpisy w Markdown zamieniamy na HTML. Wpisy już zapisane jako HTML (zaczynają się od znacznika) zostają.
     */
    public function up(): void
    {
        DB::table('posts')->orderBy('id')->get(['id', 'body'])->each(function (object $post): void {
            if (! str_starts_with(ltrim($post->body), '<')) {
                DB::table('posts')->where('id', $post->id)->update(['body' => Markdown::toHtml($post->body)]);
            }
        });
    }

    public function down(): void
    {
        // Zmiana formatu jest jednokierunkowa: HTML z edytora nie wraca do Markdown.
    }
};
