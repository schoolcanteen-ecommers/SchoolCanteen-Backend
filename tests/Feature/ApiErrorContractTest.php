<?php

namespace Tests\Feature;

use App\Http\Requests\Api\V1\Student\StoreTopUpRequest;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ApiErrorContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::post(
            '/api/v1/_test/error-contract/validation',
            function (StoreTopUpRequest $request) {
                return response()->json([
                    'success' => true,
                    'data' => $request->validated(),
                ]);
            }
        );

        Route::get(
            '/api/v1/_test/error-contract/server-error',
            function () {
                throw new RuntimeException(
                    'Sensitive internal test message.'
                );
            }
        );
    }

    public function test_validation_error_uses_api_contract(): void
    {
        $response = $this->postJson(
            '/api/v1/_test/error-contract/validation',
            [
                'amount' => 0,
            ]
        );

        $response
            ->assertStatus(422)
            ->assertExactJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Data yang diberikan tidak valid.',
                    'errors' => [
                        'amount' => [
                            'Nominal top up harus lebih dari 0.',
                        ],
                    ],
                ],
            ]);
    }

    public function test_unknown_api_route_uses_api_contract(): void
    {
        $response = $this->getJson(
            '/api/v1/_test/error-contract/not-found'
        );

        $response
            ->assertStatus(404)
            ->assertExactJson([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Resource yang diminta tidak ditemukan.',
                ],
            ]);
    }

    public function test_method_not_allowed_uses_api_contract(): void
    {
        $response = $this->getJson(
            '/api/v1/_test/error-contract/validation'
        );

        $response
            ->assertStatus(405)
            ->assertHeader('Allow', 'POST')
            ->assertExactJson([
                'success' => false,
                'error' => [
                    'code' => 'METHOD_NOT_ALLOWED',
                    'message' => 'Method HTTP tidak diizinkan untuk endpoint ini.',
                ],
            ]);
    }

    public function test_unhandled_server_error_is_sanitized(): void
    {
        $response = $this->getJson(
            '/api/v1/_test/error-contract/server-error'
        );

        $response
            ->assertStatus(500)
            ->assertExactJson([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_SERVER_ERROR',
                    'message' => 'Terjadi kesalahan pada server.',
                ],
            ]);

        $this->assertStringNotContainsString(
            'Sensitive internal test message.',
            $response->getContent()
        );
    }

    public function test_existing_unauthenticated_response_is_preserved(): void
    {
        $response = $this->getJson(
            '/api/v1/me'
        );

        $response
            ->assertStatus(401)
            ->assertExactJson([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Access token tidak ditemukan.',
                ],
            ]);
    }
}
