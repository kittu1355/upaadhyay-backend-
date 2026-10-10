<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\CandidateProfileRequest;
use App\Services\CandidateService;
use App\Services\FileUploadService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class CandidateProfileController extends Controller
{
    public function __construct(private CandidateService $svc, private FileUploadService $uploads) {}

    public function show(Request $r)
    {
        return ApiResponse::success($this->svc->getProfile($r->user()));
    }

    public function update(CandidateProfileRequest $r)
    {
        return ApiResponse::success($this->svc->updateProfile($r->user(), $r->validated()), 'Profile updated');
    }

    /** Remove the caller's own profile photo: deletes the stored object(s) and clears the profile column. */
    public function removePhoto(Request $r)
    {
        $user = $r->user();
        $user->files()->where('category', 'profile_photo')->get()->each(fn ($f) => $this->uploads->delete($f));
        $user->candidateProfile()->updateOrCreate([], ['profile_photo' => null]);
        return ApiResponse::success($this->svc->getProfile($user->fresh()), 'Photo removed');
    }
}
