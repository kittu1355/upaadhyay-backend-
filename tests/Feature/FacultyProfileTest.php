<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Concerns\MakesAccounts;
use Tests\TestCase;

class FacultyProfileTest extends TestCase
{
    use RefreshDatabase, MakesAccounts;

    public function test_profile_round_trips_including_profile_data(): void
    {
        $u = $this->candidate();
        Sanctum::actingAs($u);

        $this->putJson('/api/candidate/profile', [
            'name' => 'Dr. Asha Rao', 'phone' => '+91 98765 43210', 'gender' => 'female',
            'city' => 'Nagpur', 'current_job_title' => 'Assistant Professor', 'total_experience' => 4.5,
            'profile_data' => ['fp' => ['headline' => 'Asst Prof - CS', 'subjects' => ['Computer Science']]],
        ])->assertOk();

        $res = $this->getJson('/api/candidate/profile')->assertOk();
        $res->assertJsonPath('data.name', 'Dr. Asha Rao');
        $res->assertJsonPath('data.candidate_profile.city', 'Nagpur');
        $res->assertJsonPath('data.candidate_profile.profile_data.fp.headline', 'Asst Prof - CS');
    }

    public function test_invalid_phone_and_future_dob_rejected(): void
    {
        Sanctum::actingAs($this->candidate());
        $this->putJson('/api/candidate/profile', ['phone' => 'abc'])->assertStatus(422)->assertJsonValidationErrors('phone');
        $this->putJson('/api/candidate/profile', ['date_of_birth' => now()->addDay()->toDateString()])->assertStatus(422);
    }

    public function test_oversized_profile_data_rejected(): void
    {
        Sanctum::actingAs($this->candidate());
        $this->putJson('/api/candidate/profile', ['profile_data' => ['blob' => str_repeat('x', 250000)]])
            ->assertStatus(422)->assertJsonValidationErrors('profile_data');
    }

    public function test_company_cannot_use_candidate_profile_and_guest_gets_401(): void
    {
        $this->getJson('/api/candidate/profile')->assertStatus(401);
        Sanctum::actingAs($this->company());
        $this->putJson('/api/candidate/profile', ['name' => 'X'])->assertStatus(403);
    }

    public function test_users_only_edit_their_own_profile(): void
    {
        $a = $this->candidate(); $b = $this->candidate();
        Sanctum::actingAs($a);
        $this->putJson('/api/candidate/profile', ['city' => 'Pune'])->assertOk();
        $this->assertNull($b->candidateProfile()->first()->city);
    }

    public function test_remove_photo_clears_column_and_only_touches_own_files(): void
    {
        \Illuminate\Support\Facades\Storage::fake('r2');
        config(['uploads.disk' => 'r2']);
        $a = $this->candidate(); $b = $this->candidate();
        Sanctum::actingAs($a);
        $this->post('/api/files/upload', ['category' => 'profile_photo', 'file' => \Illuminate\Http\UploadedFile::fake()->image('me.png', 100, 100)], ['Accept' => 'application/json'])->assertCreated();
        $this->assertNotNull($a->candidateProfile()->first()->profile_photo);

        $this->deleteJson('/api/candidate/profile/photo')->assertOk()->assertJsonPath('data.candidate_profile.profile_photo', null);
        $this->assertSame(0, $a->files()->where('category', 'profile_photo')->count());
        $this->assertNull($b->candidateProfile()->first()->profile_photo);
    }
}
