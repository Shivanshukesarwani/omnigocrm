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

        $this->assertDatabaseHas('activity_logs', [
            'workspace_id' => $workspace->id,
            'action' => 'lead.converted',
            'subject_id' => $lead->id,
        ]);

        $tokenResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/device-tokens', [
                'token' => 'test-fcm-token',
                'platform' => 'android',
                'device_name' => 'PHPUnit Android',
            ]);

        $tokenResponse->assertOk()->assertJsonPath('registered', true);

    }

    public function test_workspace_cannot_access_another_workspace_lead(): void
    {
        $workspaceA = Workspace::create(['name'=>'Workspace A','slug'=>'workspace-a','plan'=>'trial','status'=>'active']);
        $workspaceB = Workspace::create(['name'=>'Workspace B','slug'=>'workspace-b','plan'=>'trial','status'=>'active']);

        $userA = User::create([
            'workspace_id'=>$workspaceA->id,
            'name'=>'User A',
            'email'=>'a@test.local',
            'role'=>'super_admin',
            'password'=>Hash::make('secret-password'),
        ]);

        $userB = User::create([
            'workspace_id'=>$workspaceB->id,
            'name'=>'User B',
            'email'=>'b@test.local',
            'role'=>'super_admin',
            'password'=>Hash::make('secret-password'),
        ]);

        $tokenB = $this->postJson('/api/login',[
            'email'=>$userB->email,
            'password'=>'secret-password',
            'device'=>'phpunit',
        ])->json('token');

        $leadA = Lead::create([
            'workspace_id'=>$workspaceA->id,
            'first_name'=>'Private',
            'mobile'=>'9000000000',
            'status'=>'new',
        ]);

        $this->withHeader('Authorization','Bearer '.$tokenB)
            ->getJson('/api/leads/'.$leadA->id)
            ->assertNotFound();
    }
}
