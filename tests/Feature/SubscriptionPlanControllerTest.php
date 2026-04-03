<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\PackageSubscription;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Schema;

class SubscriptionPlanControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected SubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (!Schema::hasTable('subscription_plans') || !Schema::hasTable('package_subscriptions')) {
            $this->markTestSkipped('Backend schema issue: required tables for subscription testing not found');
        }

        $this->user = User::factory()->create([
            'role' => 'head_coach',
            'status' => 'approved'
        ]);

        $this->plan = new SubscriptionPlan();
        $this->plan->title = 'Premium Plan';
        $this->plan->type = '1';
        $this->plan->amount = 19.99;
        $this->plan->month = 1;
        $this->plan->save();
    }

    protected function auth()
    {
        Sanctum::actingAs($this->user);
        $this->actingAs($this->user, 'api');
    }

    public function test_can_get_subscription_plans()
    {
        $this->auth();

        $response = $this->getJson('/api/subscription-plan');

        $response->assertStatus(200);
    }

    public function test_can_get_specific_plan()
    {
        $this->auth();

        $response = $this->getJson('/api/getPlane?id=' . $this->plan->id);

        $response->assertStatus(200);
    }

    public function test_can_add_subscription()
    {
        $this->auth();

        $response = $this->postJson('/api/addSubscription', [
            'id' => $this->plan->id
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('package_subscriptions', [
            'subscription_plan_id' => $this->plan->id,
            'user_id' => $this->user->id
        ]);
        
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'is_subscribe' => 1
        ]);
    }

    public function test_can_update_subscription()
    {
        $this->auth();

        $response = $this->postJson('/api/updateSubscription', [
            'id' => $this->plan->id
        ]);

        $response->assertStatus(200);
    }

    public function test_can_cancel_subscription()
    {
        $this->auth();
        
        PackageSubscription::create([
            'subscription_plan_id' => $this->plan->id,
            'user_id' => $this->user->id,
            'package_date' => now(),
            'end_date' => now()->addMonth(),
            'is_expire' => 0
        ]);

        $response = $this->getJson('/api/cancel-subscription');

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('package_subscriptions', [
            'user_id' => $this->user->id,
            'is_expire' => 1
        ]);
    }
}
