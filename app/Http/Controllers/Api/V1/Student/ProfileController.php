<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Student\UpdateProfileRequest;
use App\Http\Resources\StudentProfileResource;
use App\Models\Profile;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $authProfile =
            $request->attributes->get(
                'profile'
            );

        $profile =
            Profile::query()
                ->whereKey(
                    $authProfile->id
                )
                ->with(
                    'studentProfile'
                )
                ->first();

        return response()->json([
            'success' => true,

            'data' =>
                new StudentProfileResource(
                    $profile
                ),
        ]);
    }

    public function update(
        UpdateProfileRequest $request
    ) {
        $authProfile =
            $request->attributes->get(
                'profile'
            );

        $profile =
            Profile::query()
                ->whereKey(
                    $authProfile->id
                )
                ->firstOrFail();

        $data =
            $request->validated();

        $profile->name =
            trim(
                $data['name']
            );

        $profile->phone =
            isset(
                $data['phone']
            )
                ? trim(
                    $data['phone']
                )
                : null;

        if (
            $profile->phone === ''
        ) {
            $profile->phone = null;
        }

        $profile->save();

        $profile->load(
            'studentProfile'
        );

        return response()->json([
            'success' => true,

            'message' =>
                'Profil berhasil diperbarui.',

            'data' =>
                new StudentProfileResource(
                    $profile
                ),
        ]);
    }
}
