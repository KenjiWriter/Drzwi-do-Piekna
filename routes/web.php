<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\EffectsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegacyRedirectController;
use App\Http\Controllers\LegalPageController;
use App\Http\Controllers\MailPreviewController;
use App\Models\Page;
use App\Http\Controllers\PriceListController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\TreatmentController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/o-mnie', AboutController::class)->name('about');
Route::get('/efekty', EffectsController::class)->name('effects');
Route::get('/cennik', PriceListController::class)->name('prices');

Route::get('/zabiegi', [TreatmentController::class, 'index'])->name('treatments.index');
Route::get('/zabiegi/{category}', [TreatmentController::class, 'category'])->name('treatments.category');
Route::get('/zabiegi/{category}/{treatment}', [TreatmentController::class, 'show'])->name('treatments.show');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

Route::get('/polityka-prywatnosci', LegalPageController::class)->defaults('slug', Page::PRIVACY)->name('privacy');

Route::get('/kontakt', [ContactController::class, 'show'])->name('contact');
Route::post('/kontakt', [ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');

// Przekierowania 301 ze starej strony WordPress (drzwidopiekna.pl), żeby zachować pozycje w wyszukiwarce.
// Adresy artykułów bloga są takie same jak na starej stronie, więc /blog/{slug}/ trafia na /blog/{slug}
// zwykłym przekierowaniem ukośnika końcowego (RedirectTrailingSlash). Mapy i reszta: config/clinic.php.
Route::get('/zabieg/{legacy}', LegacyRedirectController::class);
Route::get('/en/zabieg/{legacy}', LegacyRedirectController::class);
foreach (config('clinic.legacy_redirects') as $from => $to) {
    Route::permanentRedirect($from, $to);
}

// Podgląd szablonów e-mail do pracy nad wyglądem. Tylko lokalnie (na produkcji trasa nie istnieje).
if (app()->environment('local')) {
    Route::get('/_podglad/email/potwierdzenie', [MailPreviewController::class, 'confirmation']);
}

Route::get('/robots.txt', [SeoController::class, 'robots']);
Route::get('/sitemap.xml', [SeoController::class, 'sitemap']);
