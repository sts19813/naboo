@extends('layouts.app')

@section('title', 'Expediente de Inquilino | SuWork')

@section('content')
    @include('documents.partials.dossier-drive', [
        'entityType' => 'tenant',
        'entity' => $tenant,
        'title' => 'Expediente de inquilino',
        'entityName' => $tenant->full_name,
        'entityMeta' => trim(($tenant->phone_primary ?? '-') . ($tenant->email ? ' | ' . $tenant->email : '')),
        'backUrl' => route('tenants.edit', $tenant),
        'backLabel' => 'Volver a inquilino',
        'storeRoute' => route('dossiers.tenants.documents.store', $tenant),
        'uploadRouteResolver' => fn ($document) => route('dossiers.tenants.documents.upload', [$tenant, $document->document_type]),
        'metadataUpdateRouteResolver' => fn ($document) => route('dossiers.tenants.documents.update', [$tenant, $document->document_type]),
        'destroyRouteResolver' => fn ($document) => route('dossiers.tenants.documents.destroy', [$tenant, $document->document_type]),
        'versionDestroyRouteResolver' => fn ($document, $version) => route('dossiers.tenants.documents.versions.destroy', [$tenant, $document->document_type, $version]),
    ])
@endsection
