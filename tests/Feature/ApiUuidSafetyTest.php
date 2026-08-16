<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiUuidSafetyTest extends TestCase
{
    public function test_invalid_public_product_uuid_is_rejected_by_router(): void
    {
        $response = $this->getJson(
            '/api/v1/products/not-a-uuid'
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

    public function test_invalid_public_merchant_uuid_is_rejected_by_router(): void
    {
        $response = $this->getJson(
            '/api/v1/merchants/not-a-uuid'
        );

        $response
            ->assertStatus(404)
            ->assertJsonPath(
                'error.code',
                'NOT_FOUND'
            );
    }

    public function test_invalid_pickup_slot_merchant_uuid_is_rejected_by_router(): void
    {
        $response = $this->getJson(
            '/api/v1/merchants/not-a-uuid/pickup-slots'
        );

        $response
            ->assertStatus(404)
            ->assertJsonPath(
                'error.code',
                'NOT_FOUND'
            );
    }

    public function test_invalid_student_order_uuid_is_rejected_before_authentication(): void
    {
        $response = $this->getJson(
            '/api/v1/student/orders/not-a-uuid'
        );

        $response
            ->assertStatus(404)
            ->assertJsonPath(
                'error.code',
                'NOT_FOUND'
            );
    }

    public function test_invalid_merchant_order_uuid_is_rejected_before_authentication(): void
    {
        $response = $this->getJson(
            '/api/v1/merchant/orders/not-a-uuid'
        );

        $response
            ->assertStatus(404)
            ->assertJsonPath(
                'error.code',
                'NOT_FOUND'
            );
    }

    public function test_invalid_admin_student_uuid_is_rejected_before_authentication(): void
    {
        $response = $this->getJson(
            '/api/v1/admin/students/not-a-uuid'
        );

        $response
            ->assertStatus(404)
            ->assertJsonPath(
                'error.code',
                'NOT_FOUND'
            );
    }

    public function test_valid_uuid_still_matches_protected_route(): void
    {
        $response = $this->getJson(
            '/api/v1/student/orders/'
            .'00000000-0000-0000-0000-000000000000'
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
