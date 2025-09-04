@extends('layouts.app')

@section('title', 'Catálogos de Administración')
@section('meta_description', 'Listado de todos los catálogos administrables')

@section('content')
<div class="container py-4">
  <h1 class="mb-4">Catálogos de Administración</h1>

  <div class="row g-3">
    <div class="col-sm-6 col-lg-4">
      <a class="card h-100 text-decoration-none" href="{{ route('admin.templates.index') }}">
        <div class="card-body">
          <h5 class="card-title">Plantillas</h5>
          <p class="card-text text-muted">Gestiona plantillas base.</p>
        </div>
      </a>
    </div>

    <div class="col-sm-6 col-lg-4">
      <a class="card h-100 text-decoration-none" href="{{ route('admin.templates.prefilled-data.index') }}">
        <div class="card-body">
          <h5 class="card-title">Prellenados</h5>
          <p class="card-text text-muted">Reglas y datos predefinidos.</p>
        </div>
      </a>
    </div>

    <div class="col-sm-6 col-lg-4">
      <a class="card h-100 text-decoration-none" href="{{ route('admin.diagrams.index') }}">
        <div class="card-body">
          <h5 class="card-title">Diagramas/Manuales</h5>
          <p class="card-text text-muted">Clasificación, filtros y carga.</p>
        </div>
      </a>
    </div>

    <div class="col-sm-6 col-lg-4">
      <a class="card h-100 text-decoration-none" href="{{ route('admin.supervisores.index') }}">
        <div class="card-body">
          <h5 class="card-title">Supervisores</h5>
          <p class="card-text text-muted">Altas, bajas y ediciones.</p>
        </div>
      </a>
    </div>

    <div class="col-sm-6 col-lg-4">
      <a class="card h-100 text-decoration-none" href="{{ route('admin.equipos-patrones.index') }}">
        <div class="card-body">
          <h5 class="card-title">Equipos patrón</h5>
          <p class="card-text text-muted">Instrumentos de referencia.</p>
        </div>
      </a>
    </div>

    {{-- Repite tarjetas para marcas, unidades, tags, servicios, sistemas, etc. --}}
  </div>
</div>
@endsection

