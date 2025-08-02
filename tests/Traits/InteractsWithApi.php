<?php

namespace Tests\Traits;

use App\Models\User;

trait InteractsWithApi
{
    /**
     * Create a user with app token permissions
     */
    protected function createUserWithAppToken(array $permissions = ['*']): array
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-app-token', $permissions)->plainTextToken;

        return ['user' => $user, 'token' => $token];
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