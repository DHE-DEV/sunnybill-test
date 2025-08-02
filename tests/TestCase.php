<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed basic data needed for tests
        $this->seed();
    }

    /**
     * Create an authenticated user and return the token for API testing
     */
    protected function authenticatedUser(): User
    {
        return User::factory()->create();
    }

    /**
     * Create an API token for testing protected routes
     */
    protected function createApiToken(User $user = null, array $permissions = ['*']): string
    {
        $user = $user ?? $this->authenticatedUser();
        
        return $user->createToken('test-token', $permissions)->plainTextToken;
    }

    /**
     * Get authenticated headers for API requests
     */
    protected function authenticatedHeaders(User $user = null, array $permissions = ['*']): array
    {
        $token = $this->createApiToken($user, $permissions);
        
        return [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Assert that a JSON response has the expected structure
     */
    protected function assertJsonStructure(array $structure, $response): void
    {
        $response->assertJsonStructure($structure);
    }

    /**
     * Assert that a response is a valid API error response
     */
    protected function assertApiError($response, int $statusCode = 422): void
    {
        $response->assertStatus($statusCode)
            ->assertJsonStructure([
                'message',
                'errors' => []
            ]);
    }

    /**
     * Assert that a response is a successful API response
     */
    protected function assertApiSuccess($response, int $statusCode = 200): void
    {
        $response->assertStatus($statusCode)
            ->assertJson([
                'success' => true
            ]);
    }
}
