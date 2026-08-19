<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Services\StudentDashboardReadService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        StudentDashboardReadService $dashboard
    ) {
        $profile =
            $request
                ->attributes
                ->get('profile');

        $data =
            $dashboard
                ->forProfile(
                    $profile
                );

        if (!$data) {
            return response()->json([
                'success' => false,

                'error' => [
                    'code' =>
                        'WALLET_NOT_FOUND',

                    'message' =>
                        'Wallet pengguna tidak ditemukan.',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,

            'data' =>
                $data,
        ]);
    }
}
