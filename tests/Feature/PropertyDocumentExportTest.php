<?php
namespace Tests\Feature;

use App\Models\DossierDocumentRequirement;
use App\Models\Property;
use App\Models\PropertyDocument;
use App\Models\PropertyType;
use App\Models\User;
use App\Services\DossierDocumentRequirementService;
use App\Services\PropertyDocumentPdfExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PropertyDocumentExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_reports_respect_advisor_visibility_and_expired_pdfs(): void
    {
        $advisor = User::factory()->create();
        $advisor->assignRole(Role::findOrCreate('asesores', 'web'));
        $advisor->givePermissionTo(Permission::findOrCreate('propiedades.control_ver', 'web'));
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $visible = Property::create(['internal_name' => '=2+2', 'property_type_id' => $type->id, 'full_address' => 'Calle 1', 'status' => Property::STATUS_AVAILABLE, 'created_by' => $advisor->id]);
        Property::create(['internal_name' => 'Propiedad privada', 'property_type_id' => $type->id, 'full_address' => 'Calle 2', 'status' => Property::STATUS_AVAILABLE]);
        DossierDocumentRequirement::create(['entity_type' => 'property', 'document_type' => 'contrato', 'label' => 'Contrato', 'is_active' => true, 'sort_order' => 1]);
        app(DossierDocumentRequirementService::class)->flushCache();
        PropertyDocument::create(['property_id' => $visible->id, 'document_type' => 'contrato', 'label' => 'Contrato', 'status' => PropertyDocument::STATUS_APPROVED, 'file_path' => 'documents/test.pdf', 'expires_at' => now()->subDay()]);
        $report = app(PropertyDocumentPdfExportService::class)->report($advisor);
        $this->assertCount(1, $report['summaryRows']);
        $this->assertStringContainsString('Contrato (vencido o rechazado)', $report['summaryRows'][0]['missing_labels']);
        $this->actingAs($advisor)->get(route('properties.control'))->assertOk()->assertSee('Documentos Excel');
        $excel = $this->actingAs($advisor)->get(route('properties.control.documents-pdf.export'))->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'naboo-docs-'); file_put_contents($path, $excel->streamedContent());
        try {
            $sheet = IOFactory::load($path)->getSheet(0);
            $this->assertSame('=2+2', $sheet->getCell('A8')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('A8')->getDataType());
            $this->assertNull($sheet->getCell('A9')->getValue());
        } finally { unlink($path); }
        $pdf = $this->actingAs($advisor)->get(route('properties.control.documents-pdf.export-pdf'))->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $this->actingAs(User::factory()->create())->get(route('properties.control.documents-pdf.export'))->assertForbidden();
    }
}
