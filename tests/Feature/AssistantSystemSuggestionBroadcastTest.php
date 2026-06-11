<?php

namespace Tests\Feature;

use App\Events\HeadCoachSystemSuggestion;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssistantSystemSuggestionBroadcastTest extends TestCase
{
    use DatabaseTransactions;

    protected Sport $sport;

    protected function setUp(): void
    {
        parent::setUp();

        $sport = Sport::first();
        if (!$sport) {
            $sport = new Sport();
            $sport->title = 'Football';
            $sport->save();
        }
        $this->sport = $sport;
    }

    protected function createHeadCoach(): User
    {
        $user = new User();
        $user->name = 'Head Coach';
        $user->email = 'hc_' . uniqid() . '@test.com';
        $user->password = Hash::make('12345678');
        $user->role = 'head_coach';
        $user->status = 'approved';
        $user->sport_id = $this->sport->id;
        $user->save();

        return $user;
    }

    protected function createAssistant(User $headCoach): User
    {
        $user = new User();
        $user->name = 'Assistant Coach';
        $user->email = 'ac_' . uniqid() . '@test.com';
        $user->password = Hash::make('12345678');
        $user->role = 'assistant_coach';
        $user->status = 'approved';
        $user->sport_id = $this->sport->id;
        $user->head_coach_id = $headCoach->id;
        $user->save();

        return $user;
    }

    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'down' => 2,
            'weather' => 'Rain',
            'strategies' => 'regular',
            'expected_yardage_gain' => 10,
            'h_mark_position' => 'hmark_center',
            'game_id' => 36,
            'league_id' => 22,
            'mode' => 'practice',
        ], $overrides);
    }

    /** @test */
    public function assistant_coach_can_broadcast_system_suggestion_to_head_coach(): void
    {
        Event::fake([HeadCoachSystemSuggestion::class]);

        $headCoach = $this->createHeadCoach();
        $assistant = $this->createAssistant($headCoach);
        Sanctum::actingAs($assistant);

        $response = $this->postJson('/api/assistant-coach/system-suggestion/broadcast', $this->validPayload());

        $response->assertOk()
            ->assertJson([
                'status' => 200,
                'message' => 'System suggestion broadcast sent to head coach.',
                'data' => [
                    'head_coach_id' => $headCoach->id,
                    'channel' => 'coach-group.' . $headCoach->id,
                    'event' => 'head.coach.suggestion',
                    'payload' => [
                        'down' => 2,
                        'weather' => 'Rain',
                        'strategies' => 'regular',
                        'expected_yardage_gain' => 10,
                        'h_mark_position' => 'hmark_center',
                        'game_id' => 36,
                        'league_id' => 22,
                        'mode' => 'practice',
                        'actor_id' => $assistant->id,
                        'actor_name' => $assistant->name,
                    ],
                ],
            ]);

        Event::assertDispatched(HeadCoachSystemSuggestion::class, function (HeadCoachSystemSuggestion $event) use ($headCoach, $assistant) {
            return $event->data['down'] === 2
                && $event->data['weather'] === 'Rain'
                && $event->data['strategies'] === 'regular'
                && $event->data['expected_yardage_gain'] === 10
                && $event->data['h_mark_position'] === 'hmark_center'
                && $event->data['game_id'] === 36
                && $event->data['league_id'] === 22
                && $event->data['mode'] === 'practice'
                && $event->data['actor_id'] === $assistant->id
                && $event->data['actor_name'] === $assistant->name;
        });
    }

    /** @test */
    public function accepts_frontend_field_aliases(): void
    {
        Event::fake([HeadCoachSystemSuggestion::class]);

        $headCoach = $this->createHeadCoach();
        $assistant = $this->createAssistant($headCoach);
        Sanctum::actingAs($assistant);

        $response = $this->postJson('/api/assistant-coach/system-suggestion/broadcast', [
            'down' => 1,
            'weather_status' => 'Snow',
            'strategies' => 'aggressive',
            'yardage' => 15,
            'h_mark_position' => 'hmark_left',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 200)
            ->assertJsonPath('data.event', 'head.coach.suggestion');

        Event::assertDispatched(HeadCoachSystemSuggestion::class, function (HeadCoachSystemSuggestion $event) {
            return $event->data['weather'] === 'Snow'
                && $event->data['expected_yardage_gain'] === 15
                && $event->data['h_mark_position'] === 'hmark_left';
        });
    }

    /** @test */
    public function head_coach_cannot_call_assistant_broadcast_endpoint(): void
    {
        Event::fake([HeadCoachSystemSuggestion::class]);

        $headCoach = $this->createHeadCoach();
        Sanctum::actingAs($headCoach);

        $response = $this->postJson('/api/assistant-coach/system-suggestion/broadcast', $this->validPayload());

        $response->assertStatus(403);
        Event::assertNotDispatched(HeadCoachSystemSuggestion::class);
    }

    /** @test */
    public function validation_fails_for_invalid_h_mark_position(): void
    {
        Event::fake([HeadCoachSystemSuggestion::class]);

        $headCoach = $this->createHeadCoach();
        $assistant = $this->createAssistant($headCoach);
        Sanctum::actingAs($assistant);

        $response = $this->postJson('/api/assistant-coach/system-suggestion/broadcast', $this->validPayload([
            'h_mark_position' => 'invalid_mark',
        ]));

        $response->assertStatus(422);
        Event::assertNotDispatched(HeadCoachSystemSuggestion::class);
    }

    /** @test */
    public function validation_fails_when_required_fields_missing(): void
    {
        Event::fake([HeadCoachSystemSuggestion::class]);

        $headCoach = $this->createHeadCoach();
        $assistant = $this->createAssistant($headCoach);
        Sanctum::actingAs($assistant);

        $response = $this->postJson('/api/assistant-coach/system-suggestion/broadcast', [
            'down' => 1,
        ]);

        $response->assertStatus(422);
        Event::assertNotDispatched(HeadCoachSystemSuggestion::class);
    }
}
