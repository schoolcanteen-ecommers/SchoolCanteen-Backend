<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentProfileResource;
use App\Models\Profile;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $authenticatedProfile =
            $request->attributes->get('profile');

        $profile = Profile::query()
            ->whereKey($authenticatedProfile->id)
            ->where('role', 'student')
            ->with('studentProfile')
            ->first();

        if (!$profile) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'STUDENT_NOT_FOUND',
                    'message' => 'Student tidak ditemukan.',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new StudentProfileResource(
                $profile
            ),
        ]);
    }
}
