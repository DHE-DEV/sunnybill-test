<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Tests\Traits\InteractsWithApi;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class AuthenticationTest extends TestCase
{
    use InteractsWithApi;

    public function test_can_get_authenticated_user_profile()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
    }

    public function test_cannot_access_protected_route_without_token()
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    public function test_can_search_users_for_mentions()
    {
        User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
        User::factory()->create(['name' => 'Bob Johnson', 'email' => 'bob@example.com']);

        $response = $this->getJson('/api/users/search?q=John');

        $response->assertStatus(200)
            ->assertJsonCount(2) // John Doe and Bob Johnson
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'email'
                ]
            ]);
    }

    public function test_users_search_returns_empty_without_query()
    {
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/users/search');

        $response->assertStatus(200)
            ->assertJson([]);
    }

    public function test_can_get_all_users_for_mentions()
    {
        User::factory()->count(5)->create();

        $response = $this->getJson('/api/users/all');

        $response->assertStatus(200)
            ->assertJsonCount(5)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'email'
                ]
            ]);
    }

    public function test_app_token_authentication_with_valid_token()
    {
        $tokenData = $this->createUserWithAppToken(['tasks:read']);

        $response = $this->getJson('/api/app/profile', $this->appTokenHeaders($tokenData['token']));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email'
                ]
            ]);
    }

    public function test_app_token_authentication_fails_without_token()
    {
        $response = $this->getJson('/api/app/profile');

        $response->assertStatus(401);
    }

    public function test_app_token_authentication_fails_with_invalid_token()
    {
        $response = $this->getJson('/api/app/profile', [
            'Authorization' => 'Bearer invalid-token',
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401);
    }

    public function test_app_token_permission_check_succeeds_with_correct_permission()
    {
        $tokenData = $this->createUserWithAppToken(['tasks:read']);

        $response = $this->getJson('/api/app/tasks', $this->appTokenHeaders($tokenData['token']));

        $response->assertStatus(200);
    }

    public function test_app_token_permission_check_fails_with_wrong_permission()
    {
        $tokenData = $this->createUserWithAppToken(['wrong:permission']);

        $response = $this->getJson('/api/app/tasks', $this->appTokenHeaders($tokenData['token']));

        $response->assertStatus(403);
    }

    public function test_app_token_with_wildcard_permission_allows_all()
    {
        $tokenData = $this->createUserWithAppToken(['*']);

        $response = $this->getJson('/api/app/tasks', $this->appTokenHeaders($tokenData['token']));

        $response->assertStatus(200);
    }

    public function test_can_logout_with_app_token()
    {
        $tokenData = $this->createUserWithAppToken();
        $user = $tokenData['user'];
        $token = $tokenData['token'];

        // Verify token works
        $response = $this->getJson('/api/app/profile', $this->appTokenHeaders($token));
        $response->assertStatus(200);

        // Logout
        $response = $this->postJson('/api/app/logout', [], $this->appTokenHeaders($token));
        $response->assertStatus(200);

        // Verify token no longer works
        $response = $this->getJson('/api/app/profile', $this->appTokenHeaders($token));
        $response->assertStatus(401);
    }

    public function test_multiple_tokens_can_be_created_for_same_user()
    {
        $user = User::factory()->create();
        
        $token1 = $user->createToken('token1', ['tasks:read'])->plainTextToken;
        $token2 = $user->createToken('token2', ['customers:read'])->plainTextToken;

        // Both tokens should work for their respective permissions
        $response1 = $this->getJson('/api/app/tasks', $this->appTokenHeaders($token1));
        $response1->assertStatus(200);

        $response2 = $this->getJson('/api/app/customers', $this->appTokenHeaders($token2));
        $response2->assertStatus(200);

        // But not for each other's permissions
        $response3 = $this->getJson('/api/app/customers', $this->appTokenHeaders($token1));
        $response3->assertStatus(403);

        $response4 = $this->getJson('/api/app/tasks', $this->appTokenHeaders($token2));
        $response4->assertStatus(403);
    }

    public function test_token_abilities_are_enforced()
    {
        $tokenData = $this->createUserWithAppToken(['tasks:read', 'customers:read']);
        $token = $tokenData['token'];

        // Should work for allowed permissions
        $response1 = $this->getJson('/api/app/tasks', $this->appTokenHeaders($token));
        $response1->assertStatus(200);

        $response2 = $this->getJson('/api/app/customers', $this->appTokenHeaders($token));
        $response2->assertStatus(200);

        // Should fail for disallowed permissions
        $response3 = $this->getJson('/api/app/suppliers', $this->appTokenHeaders($token));
        $response3->assertStatus(403);
    }

    public function test_can_get_dropdown_data_with_appropriate_permissions()
    {
        User::factory()->count(3)->create();
        
        $tokenData = $this->createUserWithAppToken(['tasks:read']);

        $response = $this->getJson('/api/app/users', $this->appTokenHeaders($tokenData['token']));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email'
                    ]
                ]
            ]);
    }

    public function test_middleware_blocks_access_without_proper_app_token()
    {
        // Test with regular sanctum token (not app token)
        $user = User::factory()->create();
        $regularToken = $user->createToken('regular-token')->plainTextToken;

        $response = $this->getJson('/api/app/tasks', [
            'Authorization' => 'Bearer ' . $regularToken,
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401);
    }
}