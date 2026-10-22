<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kiedy wysłaliśmy osobie z formularza automatyczne potwierdzenie (tylko jeśli podała adres e-mail).
        Schema::table('contact_requests', function (Blueprint $table) {
            $table->timestamp('confirmation_sent_at')->nullable()->after('notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('contact_requests', function (Blueprint $table) {
            $table->dropColumn('confirmation_sent_at');
        });
    }
};
