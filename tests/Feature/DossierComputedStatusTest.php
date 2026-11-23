<?php
namespace Tests\Feature;

use App\Models\OwnerDocument;
use App\Models\PropertyDocument;
use App\Models\TenantDocument;
use Tests\TestCase;

class DossierComputedStatusTest extends TestCase
{
    public function test_document_status_reflects_file_expiration_and_preserves_rejections(): void
    {
        foreach ([PropertyDocument::class, TenantDocument::class, OwnerDocument::class] as $class) {
            $document = new $class(['status' => $class::STATUS_UPLOADED, 'file_path' => null]);
            $this->assertSame($class::STATUS_PENDING, $document->computed_status);
            $document->file_path = 'documents/test.pdf';
            $this->assertSame($class::STATUS_APPROVED, $document->computed_status);
            $document->expires_at = now()->subDay();
            $this->assertSame($class::STATUS_EXPIRED, $document->computed_status);
            $document->status = $class::STATUS_REJECTED;
            $this->assertSame($class::STATUS_REJECTED, $document->computed_status);
            $this->assertSame($class::STATUS_REJECTED, $document->status);
        }
    }
}
