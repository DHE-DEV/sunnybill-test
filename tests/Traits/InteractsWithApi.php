<?php

namespace Tests\Traits;

use App\Models\User;
use App\Models\AppToken;

trait InteractsWithApi
{
    /**
     * Create a user with app token permissions
     */
    protected function createUserWithAppToken(array $permissions = ['*'], int $userId = null): array
    {
        $user = $userId ? User::find($userId) : User::factory()->create();
        
        if (!$user && $userId) {
            // Erstelle User mit spezifischer ID falls nötig (für Tests)
            $user = User::factory()->create(['id' => $userId]);
        }
        
        $token = AppToken::generateToken();
        
        $appToken = AppToken::create([
            'user_id' => $user->id,
            'name' => 'Test Token',
            'token' => hash('sha256', $token),
            'abilities' => $permissions === ['*'] ? array_keys(AppToken::getAvailableAbilities()) : $permissions,
            'expires_at' => now()->addHours(2),
            'is_active' => true,
            'created_by_ip' => '127.0.0.1',
            'app_type' => 'integration',
            'app_version' => '1.0.0',
            'device_info' => 'Test Environment',
            'notes' => 'Token for testing',
            'restrict_customers' => false,
            'restrict_suppliers' => false,
            'restrict_solar_plants' => false,
            'restrict_projects' => false,
        ]);

        return ['user' => $user, 'token' => $token, 'app_token' => $appToken];
    }

    /**
     * Create admin user with all permissions
     */
    protected function createAdminToken(): array
    {
        return $this->createUserWithAppToken(['*']);
    }

    /**
     * Create editor user (User ID 57) with limited permissions
     */
    protected function createEditorToken(array $permissions = ['tasks:read', 'tasks:update', 'tasks:status']): array
    {
        return $this->createUserWithAppToken($permissions, 57);
    }

    /**
     * Get headers for app token authentication
     */
    protected function appTokenHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Make an authenticated API request
     */
    protected function apiGet(string $uri, array $permissions = ['*'])
    {
        $tokenData = $this->createUserWithAppToken($permissions);
        
        return $this->getJson(
            '/api/app' . $uri,
            $this->appTokenHeaders($tokenData['token'])
        );
    }

    /**
     * Make an authenticated POST API request
     */
    protected function apiPost(string $uri, array $data = [], array $permissions = ['*'])
    {
        $tokenData = $this->createUserWithAppToken($permissions);
        
        return $this->postJson(
            '/api/app' . $uri,
            $data,
            $this->appTokenHeaders($tokenData['token'])
        );
    }

    /**
     * Make an authenticated PUT API request
     */
    protected function apiPut(string $uri, array $data = [], array $permissions = ['*'])
    {
        $tokenData = $this->createUserWithAppToken($permissions);
        
        return $this->putJson(
            '/api/app' . $uri,
            $data,
            $this->appTokenHeaders($tokenData['token'])
        );
    }

    /**
     * Make an authenticated PATCH API request
     */
    protected function apiPatch(string $uri, array $data = [], array $permissions = ['*'])
    {
        $tokenData = $this->createUserWithAppToken($permissions);
        
        return $this->patchJson(
            '/api/app' . $uri,
            $data,
            $this->appTokenHeaders($tokenData['token'])
        );
    }

    /**
     * Make an authenticated DELETE API request
     */
    protected function apiDelete(string $uri, array $permissions = ['*'])
    {
        $tokenData = $this->createUserWithAppToken($permissions);
        
        return $this->deleteJson(
            '/api/app' . $uri,
            [],
            $this->appTokenHeaders($tokenData['token'])
        );
    }

    /**
     * Test unauthorized access
     */
    protected function assertUnauthorized(string $method, string $uri, array $data = [])
    {
        $response = match(strtoupper($method)) {
            'GET' => $this->getJson('/api/app' . $uri),
            'POST' => $this->postJson('/api/app' . $uri, $data),
            'PUT' => $this->putJson('/api/app' . $uri, $data),
            'PATCH' => $this->patchJson('/api/app' . $uri, $data),
            'DELETE' => $this->deleteJson('/api/app' . $uri),
        };

        $response->assertStatus(401);
    }

    /**
     * Test forbidden access (wrong permissions)
     */
    protected function assertForbidden(string $method, string $uri, array $requiredPermissions, array $data = [])
    {
        $tokenData = $this->createUserWithAppToken(['wrong:permission']);

        $headers = $this->appTokenHeaders($tokenData['token']);

        $response = match(strtoupper($method)) {
            'GET' => $this->getJson('/api/app' . $uri, $headers),
            'POST' => $this->postJson('/api/app' . $uri, $data, $headers),
            'PUT' => $this->putJson('/api/app' . $uri, $data, $headers),
            'PATCH' => $this->patchJson('/api/app' . $uri, $data, $headers),
            'DELETE' => $this->deleteJson('/api/app' . $uri, [], $headers),
        };

        $response->assertStatus(403);
    }
}
