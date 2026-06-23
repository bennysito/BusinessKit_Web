<?php

namespace Tests\Feature\Auth;

use App\Models\Department;
use App\Models\EmployeeInformation;
use App\Models\Position;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_user_can_register_and_receives_employee_role(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.email', 'jane@example.com')
            ->assertJsonPath('user.roles.0', 'employee');

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
        ]);
    }

    public function test_user_can_log_in_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'jane@example.com',
            'password' => 'password',
        ]);

        $user->assignRole('employee');

        $response = $this->postJson('/api/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('user.email', 'jane@example.com')
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'roles'],
            ]);
    }

    public function test_authenticated_user_can_view_profile_with_employee_details(): void
    {
        $department = Department::factory()->create(['name' => 'Operations']);
        $position = Position::factory()->create(['name' => 'Supervisor']);
        $user = User::factory()->create();
        $user->assignRole('manager');

        EmployeeInformation::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'email' => $user->email,
        ]);

        $token = $user->createToken('test-token');

        $response = $this->withToken($token->plainTextToken)->getJson('/api/auth/me');

        $response
            ->assertOk()
            ->assertJsonPath('email', $user->email)
            ->assertJsonPath('roles.0', 'manager')
            ->assertJsonPath('employee_profile.department', 'Operations')
            ->assertJsonPath('employee_profile.position', 'Supervisor');
    }

    public function test_authenticated_user_can_log_out_and_revoke_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('logout-token');

        $response = $this->withToken($token->plainTextToken)->postJson('/api/auth/logout');

        $response->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }
}
