<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\RecurringExpenseItem;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_index_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('expenses.index'));

        $response->assertOk();
        $response->assertSee('Gastos');
        $response->assertSee('js-expense-delete-form', false);
    }

    public function test_expense_can_be_created_with_attachments(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $property = $this->createPropertyFixture($user);

        $response = $this
            ->actingAs($user)
            ->from(route('expenses.index'))
            ->post(route('expenses.store'), [
                'property_id' => $property->id,
                'concept' => 'Mantenimiento elevador',
                'amount' => 2500,
                'due_date' => now()->addDays(3)->toDateString(),
                'description' => 'Servicio mensual',
                'files' => [
                    UploadedFile::fake()->image('elevador.jpg'),
                    UploadedFile::fake()->create('factura.pdf', 120, 'application/pdf'),
                ],
            ]);

        $response->assertRedirect(route('expenses.index'));
        $this->assertDatabaseHas('expenses', [
            'property_id' => $property->id,
            'concept' => 'Mantenimiento elevador',
            'description' => 'Servicio mensual',
        ]);
        $this->assertDatabaseCount('expense_files', 2);

        $this->actingAs($user)
            ->get(route('properties.show', $property))
            ->assertOk()
            ->assertSee('id="expenseFilePreviewModal"', false)
            ->assertSee('js-expense-file-preview', false)
            ->assertSee('data-confirm-title="Eliminar gasto"', false)
            ->assertSee('window.Swal?.fire', false)
            ->assertDontSee("onsubmit=\"return confirm('¿Deseas eliminar este gasto?');\"", false);
    }

    public function test_expense_can_be_marked_as_paid(): void
    {
        $user = User::factory()->create();
        $property = $this->createPropertyFixture($user);

        $expense = Expense::create([
            'property_id' => $property->id,
            'concept' => 'Internet',
            'amount' => 800,
            'due_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('expenses.index'))
            ->post(route('expenses.mark-paid', $expense));

        $response->assertRedirect(route('expenses.index'));
        $this->assertNotNull($expense->fresh()->paid_at);
    }

    public function test_property_expense_notification_setup_can_be_customized(): void
    {
        $user = User::factory()->create();
        $property = $this->createPropertyFixture($user);

        $response = $this
            ->actingAs($user)
            ->put(route('expenses.properties.setup', $property), [
                'use_global_setup' => 0,
                'days_before' => 5,
                'emails' => 'admin@example.com, pagos@example.com',
                'phones' => '9991234567',
            ]);

        $response->assertRedirect(route('properties.show', $property) . '#tab-expenses');
        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'use_global_expense_notifications' => false,
            'expense_notification_days_before' => 5,
        ]);

        $property->refresh();
        $this->assertSame(['admin@example.com', 'pagos@example.com'], $property->expense_notification_emails);
        $this->assertSame(['9991234567'], $property->expense_notification_phones);
    }

    public function test_monthly_expense_item_generates_records_without_duplicates(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-10 10:00:00'));

        try {
            $user = User::factory()->create();
            $property = $this->createPropertyFixture($user);

            $this->actingAs($user)
                ->post(route('expenses.recurring-items.store', $property), [
                    'concept' => 'Cuota de mantenimiento',
                    'amount' => 2500,
                    'frequency' => RecurringExpenseItem::FREQUENCY_MONTHLY,
                    'starts_on' => '2026-01-31',
                    'occurrences_count' => 12,
                    'description' => 'Cuota mensual del edificio',
                ])
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('properties.show', $property) . '#tab-expenses');

            $item = RecurringExpenseItem::query()->where('property_id', $property->id)->firstOrFail();

            $this->assertDatabaseHas('expenses', [
                'property_id' => $property->id,
                'recurring_expense_item_id' => $item->id,
                'concept' => 'Cuota de mantenimiento',
                'amount' => 2500,
                'due_date' => '2026-01-31 00:00:00',
                'recurrence_date' => '2026-01-31 00:00:00',
            ]);
            $this->assertDatabaseHas('expenses', [
                'recurring_expense_item_id' => $item->id,
                'due_date' => '2026-02-28 00:00:00',
            ]);
            $this->assertDatabaseHas('expenses', [
                'recurring_expense_item_id' => $item->id,
                'due_date' => '2026-03-31 00:00:00',
            ]);
            $this->assertDatabaseHas('expenses', [
                'recurring_expense_item_id' => $item->id,
                'due_date' => '2026-04-30 00:00:00',
            ]);
            $this->assertSame(12, $item->expenses()->count());

            $this->actingAs($user)
                ->put(route('expenses.recurring-items.update', $item), [
                    'concept' => 'Cuota de mantenimiento actualizada',
                    'amount' => 2750,
                    'frequency' => RecurringExpenseItem::FREQUENCY_MONTHLY,
                    'starts_on' => '2026-01-31',
                    'occurrences_count' => 12,
                    'description' => 'Nueva cuota mensual',
                    'is_active' => 1,
                ])
                ->assertSessionHasNoErrors();

            $this->assertDatabaseHas('expenses', [
                'recurring_expense_item_id' => $item->id,
                'concept' => 'Cuota de mantenimiento actualizada',
                'amount' => 2750,
                'due_date' => '2026-02-28 00:00:00',
            ]);

            Artisan::call('expenses:generate-recurring');
            Artisan::call('expenses:generate-recurring');

            $this->assertSame(12, $item->expenses()->count());

            $this->actingAs($user)
                ->put(route('expenses.recurring-items.update', $item), [
                    'concept' => 'Cuota única',
                    'amount' => 2750,
                    'frequency' => RecurringExpenseItem::FREQUENCY_ONCE,
                    'starts_on' => '2026-01-31',
                    'occurrences_count' => 99,
                    'is_active' => 1,
                ])
                ->assertSessionHasNoErrors();

            $item->refresh();
            $this->assertSame(RecurringExpenseItem::FREQUENCY_ONCE, $item->frequency);
            $this->assertSame(1, $item->occurrences_count);
            $this->assertSame(1, $item->expenses()->count());

            $this->actingAs($user)
                ->get(route('properties.show', $property))
                ->assertOk()
                ->assertSee('Pago único')
                ->assertSee('js-recurring-count', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_recurring_expense_attachments_are_copied_to_generated_records_independently(): void
    {
        Storage::fake('public');
        Carbon::setTestNow(Carbon::parse('2026-01-10 10:00:00'));

        try {
            $user = User::factory()->create();
            $property = $this->createPropertyFixture($user);

            $this->actingAs($user)
                ->post(route('expenses.recurring-items.store', $property), [
                    'concept' => 'Cuota de mantenimiento',
                    'amount' => 2500,
                    'frequency' => RecurringExpenseItem::FREQUENCY_MONTHLY,
                    'starts_on' => '2026-01-15',
                    'occurrences_count' => 2,
                    'files' => [
                        UploadedFile::fake()->create('factura-base.pdf', 120, 'application/pdf'),
                    ],
                ])
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('properties.show', $property) . '#tab-expenses');

            $item = RecurringExpenseItem::query()->where('property_id', $property->id)->firstOrFail();
            $expenses = $item->expenses()->with('files')->orderBy('due_date')->get();

            $this->assertDatabaseCount('recurring_expense_item_files', 1);
            $this->assertDatabaseCount('expense_files', 2);
            $this->assertCount(2, $expenses);
            $this->assertSame(1, $expenses[0]->files->count());
            $this->assertSame(1, $expenses[1]->files->count());
            $this->assertNotSame($expenses[0]->files->first()->path, $expenses[1]->files->first()->path);

            $firstOriginalPath = $expenses[0]->files->first()->path;
            $secondOriginalPath = $expenses[1]->files->first()->path;

            $this->actingAs($user)
                ->put(route('expenses.update', $expenses[0]), [
                    'concept' => 'Cuota enero ajustada',
                    'amount' => 2600,
                    'due_date' => '2026-01-15',
                    'description' => 'Ajuste de enero',
                    'remove_file_ids' => [$expenses[0]->files->first()->id],
                    'files' => [
                        UploadedFile::fake()->create('factura-enero.pdf', 90, 'application/pdf'),
                    ],
                    'property_context' => $property->uuid,
                ])
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('properties.show', $property) . '#tab-expenses');

            Storage::disk('public')->assertMissing($firstOriginalPath);
            Storage::disk('public')->assertExists($secondOriginalPath);

            $expenses[0]->refresh()->load('files');
            $expenses[1]->refresh()->load('files');

            $this->assertSame('Cuota enero ajustada', $expenses[0]->concept);
            $this->assertSame('Cuota de mantenimiento', $expenses[1]->concept);
            $this->assertSame(1, $expenses[0]->files->count());
            $this->assertSame(1, $expenses[1]->files->count());
            $this->assertSame($secondOriginalPath, $expenses[1]->files->first()->path);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_annual_expense_item_generates_once_per_year_and_can_be_paused(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-10 10:00:00'));

        try {
            $user = User::factory()->create();
            $property = $this->createPropertyFixture($user);

            $this->actingAs($user)
                ->post(route('expenses.recurring-items.store', $property), [
                    'concept' => 'Seguro anual',
                    'amount' => 12500,
                    'frequency' => RecurringExpenseItem::FREQUENCY_ANNUAL,
                    'starts_on' => '2026-08-20',
                    'occurrences_count' => 2,
                ])
                ->assertSessionHasNoErrors();

            $item = RecurringExpenseItem::query()->where('property_id', $property->id)->firstOrFail();
            $this->assertDatabaseHas('expenses', [
                'recurring_expense_item_id' => $item->id,
                'due_date' => '2027-08-20 00:00:00',
            ]);
            $this->assertSame(2, $item->expenses()->count());

            $this->actingAs($user)
                ->put(route('expenses.recurring-items.update', $item), [
                    'concept' => 'Seguro anual',
                    'amount' => 12500,
                    'frequency' => RecurringExpenseItem::FREQUENCY_ANNUAL,
                    'starts_on' => '2026-08-20',
                    'occurrences_count' => 2,
                    'is_active' => 0,
                ])
                ->assertSessionHasNoErrors();

            Carbon::setTestNow(Carbon::parse('2028-07-10 10:00:00'));
            Artisan::call('expenses:generate-recurring');

            $this->assertSame(2, $item->expenses()->count());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_february_uses_day_28_for_items_starting_on_day_29_or_later(): void
    {
        $user = User::factory()->create();
        $property = $this->createPropertyFixture($user);

        $this->actingAs($user)
            ->post(route('expenses.recurring-items.store', $property), [
                'concept' => 'Cuota fin de mes',
                'amount' => 900,
                'frequency' => RecurringExpenseItem::FREQUENCY_MONTHLY,
                'starts_on' => '2028-01-29',
                'occurrences_count' => 2,
            ])
            ->assertSessionHasNoErrors();

        $item = RecurringExpenseItem::query()->where('property_id', $property->id)->firstOrFail();

        $this->assertDatabaseHas('expenses', [
            'recurring_expense_item_id' => $item->id,
            'due_date' => '2028-02-28 00:00:00',
        ]);
        $this->assertDatabaseMissing('expenses', [
            'recurring_expense_item_id' => $item->id,
            'due_date' => '2028-02-29 00:00:00',
        ]);
    }

    private function createPropertyFixture(User $user): Property
    {
        $type = PropertyType::create(['name' => 'Departamento', 'slug' => 'departamento', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Montebello', 'slug' => 'montebello', 'is_active' => true]);

        return Property::create([
            'internal_name' => 'Depto 301',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle 1 #301',
            'status' => Property::STATUS_OCCUPIED,
            'onboarding_step' => 5,
            'created_by' => $user->id,
        ]);
    }
}
