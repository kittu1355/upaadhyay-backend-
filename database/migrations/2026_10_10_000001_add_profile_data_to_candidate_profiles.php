<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faculty profile: stores the rich profile (qualifications, employment, links, preferences…)
 * as JSON next to the indexed columns. Safe to run on a DB that already has candidate_profiles
 * (adds only the missing column) and on a fresh DB (the base table is not in this repo's migrations).
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('candidate_profiles')) {
            Schema::create('candidate_profiles', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $t->string('profile_photo', 500)->nullable();
                $t->date('date_of_birth')->nullable();
                $t->string('gender', 20)->nullable();
                $t->string('location', 190)->nullable();
                $t->string('city', 100)->nullable();
                $t->string('state', 100)->nullable();
                $t->text('bio')->nullable();
                $t->string('current_job_title', 190)->nullable();
                $t->decimal('total_experience', 4, 1)->nullable();
                $t->json('profile_data')->nullable();
                $t->timestamps();
            });
            return;
        }
        if (! Schema::hasColumn('candidate_profiles', 'profile_data')) {
            Schema::table('candidate_profiles', fn (Blueprint $t) => $t->json('profile_data')->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('candidate_profiles', 'profile_data')) {
            Schema::table('candidate_profiles', fn (Blueprint $t) => $t->dropColumn('profile_data'));
        }
    }
};
