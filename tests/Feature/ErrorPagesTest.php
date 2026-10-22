<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public function test_unknown_pages_show_the_branded_404_and_stay_out_of_the_index(): void
    {
        $this->get('/nie-ma-takiej-strony')
            ->assertNotFound()
            ->assertSee('Nie ma takiej strony')
            ->assertSee('<meta name="robots" content="noindex">', false)
            ->assertSee('href="/"', false)
            ->assertSee('tel:+48797745224', false);
    }

    public function test_server_error_and_maintenance_screens_render_without_the_app(): void
    {
        // Ekrany nie mogą zależeć od Vite ani od JS, bo mają działać także przy awarii reszty aplikacji.
        foreach (['errors.500' => 'Coś poszło nie tak', 'errors.503' => 'Krótka przerwa techniczna'] as $view => $text) {
            $html = view($view)->render();

            $this->assertStringContainsString($text, $html);
            $this->assertStringNotContainsString('/build/', $html);
            $this->assertStringNotContainsString('<script', $html);
        }

        $this->assertStringContainsString('http-equiv="refresh"', view('errors.503')->render());
    }
}
