<?php
namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CrmSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_lead_lifecycle_and_api_login(): void
    {
        $workspace = Workspace::create([
            'name' => 'Acme Demo',
            'slug' => 'acme-demo',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::create([
            'workspace_id' => $workspace->id,
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'role' => 'super_admin',
            'password' => Hash::make('secret-password'),
        ]);

        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret-password',
            'device' => 'phpunit',
        ]);

        $login->assertOk()->assertJsonStructure(['token', 'user']);

        $token = $login->json('token');

        $created = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/leads', [
                'first_name' => 'Test',
                'last_name' => 'Lead',
                'mobile' => '9876543210',
                'company' => 'Acme Demo',
            ]);

        $created->assertCreated();

        $this->assertDatabaseHas('leads', [
            'first_name' => 'Test',
            'workspace_id' => $workspace->id,
        ]);

        $lead = Lead::where('workspace_id', $workspace->id)->firstOrFail();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/leads/'.$lead->id.'/convert-contact')
            ->assertOk()
            ->assertJsonPath('contact.source_lead_id', $lead->id);
    }
}
