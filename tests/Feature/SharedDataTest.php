<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SharedDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_public_clinic_data_is_shared_with_the_browser(): void
    {
        $this->seed();

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('clinic', fn ($clinic) => collect($clinic)->keys()->diff(HandleInertiaRequests::PUBLIC_CLINIC_KEYS)->isEmpty())
            ->has('clinic.phone_e164')
            ->has('clinic.legal.privacy')
            ->missing('clinic.admin')
            ->missing('clinic.legacy_redirects')
            ->etc());
    }

    public function test_the_development_admin_account_never_reaches_the_page_html(): void
    {
        config(['clinic.admin' => ['name' => 'Dev', 'email' => 'dev-konto@example.test', 'password' => 'haslo-ktorego-nie-wolno-wyslac']]);

        foreach (['/', '/kontakt', '/blog'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertDontSee('haslo-ktorego-nie-wolno-wyslac', false)
                ->assertDontSee('dev-konto@example.test', false);
        }
    }
}
