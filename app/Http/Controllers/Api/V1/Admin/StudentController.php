<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminStudentResource;
use App\Models\Profile;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Profile::query()
            ->where('role', 'student')
            ->with([
                'studentProfile',
                'wallet',
            ])
            ->withCount('orders');

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = trim(
                (string) $request->query('search')
            );

            $query->where(function ($query) use ($search) {
                $query
                    ->where(
                        'name',
                        'ilike',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'phone',
                        'ilike',
                        '%' . $search . '%'
                    )
                    ->orWhereHas(
                        'studentProfile',
                        function ($studentQuery) use ($search) {
                            $studentQuery
                                ->where(
                                    'nis',
                                    'ilike',
                                    '%' . $search . '%'
                                )
                                ->orWhere(
                                    'class',
                                    'ilike',
                                    '%' . $search . '%'
                                )
                                ->orWhere(
                                    'major',
                                    'ilike',
                                    '%' . $search . '%'
                                );
                        }
                    );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Class Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('class')) {
            $class = trim(
                (string) $request->query('class')
            );

            $query->whereHas(
                'studentProfile',
                function ($studentQuery) use ($class) {
                    $studentQuery->where(
                        'class',
                        $class
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Major Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('major')) {
            $major = trim(
                (string) $request->query('major')
            );

            $query->whereHas(
                'studentProfile',
                function ($studentQuery) use ($major) {
                    $studentQuery->where(
                        'major',
                        $major
                    );
                }
            );
        }

        $students = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return AdminStudentResource::collection(
            $students
        )->additional([
            'success' => true,
        ]);
    }

    public function show(string $student)
    {
        $profile = Profile::query()
            ->whereKey($student)
            ->where('role', 'student')
            ->with([
                'studentProfile',
                'wallet',
            ])
            ->withCount('orders')
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
            'data' =>
                new AdminStudentResource($profile),
        ]);
    }
}