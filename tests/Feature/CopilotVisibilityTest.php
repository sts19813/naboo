<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CopilotVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_copilot_is_hidden_when_openai_api_key_is_not_configured(): void
    {
        config(['services.openai.key' => null]);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('data-copilot', false)
            ->assertDontSee('AI Copilot');
    }

    public function test_copilot_is_shown_when_openai_api_key_is_configured(): void
    {
        config(['services.openai.key' => 'test-openai-api-key']);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-copilot', false)
            ->assertSee('AI Copilot');
    }

    public function test_copilot_is_hidden_when_openai_api_key_contains_only_whitespace(): void
    {
        config(['services.openai.key' => '   ']);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('data-copilot', false)
            ->assertDontSee('AI Copilot');
    }
}
