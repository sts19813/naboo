@extends('layouts.app')

@section('title', 'Expediente de Propietario | SuWork')

@section('content')
    @include('documents.partials.dossier-drive', [
        'entityType' => 'owner',
        'entity' => $owner,
        'title' => 'Expediente de propietario',
        'entityName' => $owner->name,
        'entityMeta' => trim(($owner->phone ?? '-') . ($owner->email ? ' | ' . $owner->email : '')),
        'backUrl' => route('owners.edit', $owner),
        'backLabel' => 'Volver a propietario',
        'storeRoute' => route('dossiers.owners.documents.store', $owner),
        'uploadRouteResolver' => fn ($document) => route('dossiers.owners.documents.upload', [$owner, $document->document_type]),
        'metadataUpdateRouteResolver' => fn ($document) => route('dossiers.owners.documents.update', [$owner, $document->document_type]),
        'destroyRouteResolver' => fn ($document) => route('dossiers.owners.documents.destroy', [$owner, $document->document_type]),
        'versionDestroyRouteResolver' => fn ($document, $version) => route('dossiers.owners.documents.versions.destroy', [$owner, $document->document_type, $version]),
    ])
@endsection
