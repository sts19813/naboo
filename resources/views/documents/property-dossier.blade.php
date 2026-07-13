@extends('layouts.app')

@section('title', 'Expediente de Propiedad | SuWork')

@section('content')
    @include('documents.partials.dossier-drive', [
        'entityType' => 'property',
        'entity' => $property,
        'title' => 'Expediente de propiedad',
        'entityName' => $property->internal_name,
        'entityMeta' => ($property->type?->name ?? '-') . ' | ' . ($property->zone?->name ?? '-'),
        'backUrl' => route('properties.show', $property),
        'backLabel' => 'Volver a propiedad',
        'storeRoute' => route('dossiers.properties.documents.store', $property),
        'uploadRouteResolver' => fn ($document) => route('dossiers.properties.documents.upload', [$property, $document->document_type]),
        'metadataUpdateRouteResolver' => fn ($document) => route('dossiers.properties.documents.update', [$property, $document->document_type]),
        'destroyRouteResolver' => fn ($document) => route('dossiers.properties.documents.destroy', [$property, $document->document_type]),
        'versionDestroyRouteResolver' => fn ($document, $version) => route('dossiers.properties.documents.versions.destroy', [$property, $document->document_type, $version]),
    ])
@endsection
