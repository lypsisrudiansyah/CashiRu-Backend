<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Http\Controllers\Api\AuthController;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;
    protected AuthController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new AuthController();
    }

    public function test_login_success()
    {
        $user = User::factory()->create([
            'email' => 'rudi@example.com',
            'password' => bcrypt('secret123'),
        ]);

        // $request = Request::create('/api/login', 'POST', [
        //     'email' => 'rudi@example.com',
        //     'password' => 'secret123',
        // ]);
        $request = new Request([
            'email' => 'rudi@example.com',
            'password' => 'secret123',
        ]);
        $response = $this->controller->login($request);

        $this->assertEquals(200, $response->status());
        $this->assertArrayHasKey('access_token', $response->getData(true));
        $this->assertArrayHasKey('user', $response->getData(true));
    }

    public function test_login_email_not_found()
    {
        $request = new Request([
            'email' => 'rudinew@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->controller->login($request);

        $this->assertEquals(404, $response->status());
        $this->assertEquals('Email not found', $response->getData(true)['message']);
    }

    public function test_login_wrong_password()
    {
        $user = User::factory()->create([
            'email' => 'rudi@example.com',
            'password' => bcrypt('antekmarun'),
        ]);

        $request = Request::create('/api/login', 'POST', [
            'email' => 'rudi@example.com',
            'password' => 'wrongpass',
        ]);

        $response = $this->controller->login($request);

        $this->assertEquals(401, $response->status());
        $this->assertEquals('Invalid password', $response->getData(true)['message']);
    }

    public function test_logout_success()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $token = $user->createToken('auth_token')->plainTextToken;

        $tokenInstance = \Laravel\Sanctum\PersonalAccessToken::find(
            explode('|', $token)[0]
        );
        $this->assertNotNull($tokenInstance);

        $request = Request::create('/api/logout', 'POST');
        $request->attributes->set('sanctum', $tokenInstance);

        // $request->setUserResolver(fn () => $user);
        $request->setUserResolver(function () use ($user, $tokenInstance) {
            $user->withAccessToken($tokenInstance); // ini kunci pentingnya
            return $user;
        });

        $response = $this->controller->logout($request);

        $this->assertEquals(200, $response->status());
        $this->assertEquals('Logout successful', $response->getData(true)['message']);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenInstance->id,
            'tokenable_id' => $user->id,
        ]);
    }
}
