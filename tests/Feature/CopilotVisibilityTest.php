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
            ->assertSee('<span class="naboo-copilot__usage-label">Hoy</span>', false)
            ->assertSee('<span class="naboo-copilot__usage-label">Mes</span>', false)
            ->assertSee('Costo est.');
    }

    public function test_copilot_usage_summary_is_hidden_when_costs_are_disabled(): void
    {
        config([
            'services.openai.key' => 'test-openai-api-key',
            'services.openai.show_costs' => false,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-copilot', false)
            ->assertDontSee('<div class="naboo-copilot__usage"', false)
            ->assertDontSee('<span class="naboo-copilot__usage-label">Hoy</span>', false)
            ->assertDontSee('<span class="naboo-copilot__usage-label">Mes</span>', false)
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
