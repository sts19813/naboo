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
        config([
            'services.openai.key' => 'test-openai-api-key',
            'services.openai.show_costs' => true,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-copilot', false)
            ->assertSee('AI Copilot')
            ->assertSee('Costo est.');
    }

    public function test_copilot_costs_are_hidden_when_disabled(): void
    {
        config([
            'services.openai.key' => 'test-openai-api-key',
            'services.openai.show_costs' => false,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-copilot', false)
            ->assertSee('Hoy')
            ->assertSee('Mes')
            ->assertDontSee('Costo est.')
            ->assertDontSee('<strong data-copilot-usage-cost', false);
    }

    public function test_usage_reply_omits_costs_when_disabled(): void
    {
        config([
            'services.openai.key' => 'test-openai-api-key',
            'services.openai.show_costs' => false,
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('copilot.chat'), [
                'message' => '¿Cuántos tokens he usado?',
            ])
            ->assertOk()
            ->assertJsonPath('message.content', fn (string $content): bool => str_contains($content, 'tokens') && ! str_contains($content, 'costo estimado')
            );
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
