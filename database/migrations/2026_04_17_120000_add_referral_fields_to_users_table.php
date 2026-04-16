<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 16)->nullable()->unique()->after('password');
            $table->foreignId('referred_by')->nullable()->after('referral_code')->constrained('users')->nullOnDelete();
        });

        $existing = DB::table('users')->whereNull('referral_code')->pluck('id');
        foreach ($existing as $id) {
            $code = $this->makeUniqueReferralCode();
            DB::table('users')->where('id', $id)->update(['referral_code' => $code]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);
            $table->dropColumn(['referral_code', 'referred_by']);
        });
    }

    private function makeUniqueReferralCode(): string
    {
        do {
            $code = strtolower(Str::random(8));
        } while (DB::table('users')->where('referral_code', $code)->exists());

        return $code;
    }
};
