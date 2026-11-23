<?php
namespace Tests\Feature;

use App\Models\MaintenanceCut;
use App\Models\MaintenanceProvider;
use App\Models\MaintenanceTicket;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MaintenanceReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_export_filtered_tickets_and_paid_cuts_without_spreadsheet_formulas(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('administrador', 'web'));
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $property = Property::create(['internal_name' => 'Casa', 'property_type_id' => $type->id, 'full_address' => 'Calle 1', 'status' => Property::STATUS_AVAILABLE]);
        $provider = MaintenanceProvider::create(['name' => 'Técnico', 'type' => 'tecnico_interno', 'is_active' => true]);
        $ticket = MaintenanceTicket::create(['property_id' => $property->id, 'current_provider_id' => $provider->id, 'reported_by_role' => 'administrador', 'reported_by_name' => 'Admin', 'category' => 'plomeria', 'priority' => 'media', 'status' => 'completado', 'title' => '=2+2', 'exact_location' => 'Cocina', 'description' => 'Trabajo', 'reported_at' => now(), 'completed_at' => now()]);
        $cut = MaintenanceCut::create(['paid_by_user_id' => $admin->id, 'ticket_count' => 1, 'labor_total' => 100, 'material_total' => 20, 'grand_total' => 120, 'paid_at' => now()]);
        $cut->items()->create(['ticket_id' => $ticket->id, 'labor_total' => 100, 'material_total' => 20, 'grand_total' => 120]);
        $secondProperty = Property::create(['internal_name' => 'Casa dos', 'property_type_id' => $type->id, 'full_address' => 'Calle 2', 'status' => Property::STATUS_AVAILABLE]);
        MaintenanceTicket::create(['property_id' => $secondProperty->id, 'master_ticket_id' => $ticket->id, 'current_provider_id' => $provider->id, 'reported_by_role' => 'administrador', 'reported_by_name' => 'Admin', 'category' => 'plomeria', 'priority' => 'media', 'status' => 'completado', 'title' => 'Hijo', 'exact_location' => 'Patio', 'description' => 'Trabajo', 'reported_at' => now(), 'completed_at' => now()]);
        $this->actingAs($admin)->get(route('maintenance.index'))->assertOk()->assertSee('Exportar tickets');
        $this->actingAs($admin)->get(route('maintenance-cuts.index'))->assertOk()->assertSee('Exportar historial de cortes');
        $pdf = $this->actingAs($admin)->get(route('maintenance.export', ['format' => 'pdf', 'statuses' => ['completado']]));
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $excel = $this->actingAs($admin)->get(route('maintenance.export', ['format' => 'excel', 'technician_ids' => [$provider->id]]));
        $excel->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'naboo-report-');
        file_put_contents($path, $excel->streamedContent());
        try {
            $sheet = IOFactory::load($path)->getActiveSheet();
            $this->assertSame('=2+2', $sheet->getCell('C8')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('C8')->getDataType());
        } finally { unlink($path); }
        $cutPdf = $this->actingAs($admin)->get(route('maintenance-cuts.export', ['format' => 'pdf', 'property_ids' => [$property->id]]));
        $cutPdf->assertOk();
        $this->assertStringStartsWith('%PDF', $cutPdf->getContent());
        $this->actingAs($admin)->get(route('maintenance-cuts.export', ['format' => 'excel', 'provider_ids' => [$provider->id]]))->assertOk();
        $childCutReport = $this->actingAs($admin)->get(route('maintenance-cuts.export', ['format' => 'excel', 'property_ids' => [$secondProperty->id]]));
        $path = tempnam(sys_get_temp_dir(), 'naboo-cut-report-');
        file_put_contents($path, $childCutReport->streamedContent());
        try { $this->assertSame($cut->display_reference, IOFactory::load($path)->getActiveSheet()->getCell('A12')->getValue()); } finally { unlink($path); }
        $outsider = User::factory()->create();
        $outsider->assignRole(Role::findOrCreate('tecnico', 'web'));
        $this->actingAs($outsider)->get(route('maintenance.export', 'pdf'))->assertForbidden();
        $this->actingAs($outsider)->get(route('maintenance-cuts.export', 'pdf'))->assertForbidden();
    }
}
