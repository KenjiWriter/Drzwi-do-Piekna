<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Druga metoda dwuskładnikowego logowania: kod wysyłany e-mailem (has_email_authentication, zarządza nią Filament)
        // oraz przełącznik konta „wymagaj obu metod naraz” (require_all_factors, patrz App\Support\Auth\StrictMultiFactorChallenge).
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('has_email_authentication')->default(false)->after('app_authentication_recovery_codes');
            $table->boolean('require_all_factors')->default(false)->after('has_email_authentication');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['has_email_authentication', 'require_all_factors']);
        });
    }
};
