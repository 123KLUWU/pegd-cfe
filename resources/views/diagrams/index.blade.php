{{-- resources/views/diagrams/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Diagramas y Manuales')

@section('content')
<div class="container mt-5">
    <h1>Diagramas y Manuales</h1>

    {{-- Formulario de Búsqueda y Filtros --}}
    <form method="GET" class="row g-2 mb-3">
        {{-- Búsqueda --}}
        <div class="col-md-4">
          <input type="text" name="search" class="form-control" placeholder="Buscar..."
                 value="{{ $search_query }}">
        </div>
      
        {{-- Tipo --}}
        <div class="col-md-2">
          <select name="type" class="form-select">
            <option value="">Tipo (todos)</option>
            <option value="diagram" @selected($selected_type==='diagram')>Diagrama</option>
            <option value="manual" @selected($selected_type==='manual')>Manual</option>
          </select>
        </div>
      
        {{-- Categoría de máquina 
        <div class="col-md-3">
          <select name="category" class="form-select">
            <option value="">Categoría de máquina (todas)</option>
            @foreach($available_categories as $cat)
              <option value="{{ $cat }}" @selected($selected_category===$cat)>{{ $cat }}</option>
            @endforeach
          </select>
        </div>
        --}}
        {{-- Unidad --}}
        <div class="col-md-3">
          <select name="unidad_id" class="form-select">
            <option value="">Unidad (todas)</option>
            @foreach($unidades as $u)
              <option value="{{ $u->id }}" @selected($selected_unidad==$u->id)>{{ $u->unidad }}</option>
            @endforeach
          </select>
        </div>
      
        {{-- Clasificación (con optgroup por group si viene) --}}
        <div class="col-md-4">
          <select name="classification_id" class="form-select">
            <option value="">Clasificación (todas)</option>
            @foreach($classifications->groupBy('group') as $grp => $items)
              <optgroup label="{{ $grp ?? 'General' }}">
                @foreach($items as $c)
                  <option value="{{ $c->id }}" @selected($selected_classification==$c->id)>{{ $c->name }}</option>
                @endforeach
              </optgroup>
            @endforeach
          </select>
        </div>
      
        {{-- Autómata --}}
        <div class="col-md-4">
          <select name="automata_id" class="form-select">
            <option value="">Autómata (todos)</option>
            @foreach($automatas as $a)
              <option value="{{ $a->id }}" @selected($selected_automata==$a->id)>{{ $a->name }}</option>
            @endforeach
          </select>
        </div>

        {{-- Autómata --}}
          <div class="col-md-4">
            <select name="sistema_id" class="form-select">
                <option value="">sistema (todos)</option>
                  @foreach($sistemas as $sis)
                    <option value="{{ $sis->id }}" @selected($selected_sistema==$sis->id)>{{ $sis->clave }} {{ $sis->sistema }}

                    </option>
                  @endforeach
            </select>
          </div>

        {{-- Botones --}}
        <div class="col-md-2 d-grid">
          <button class="btn btn-primary" type="submit">Filtrar</button>
        </div>
        {{-- 
        <div class="col-md-2 d-grid">
          <a class="btn btn-outline-secondary" href="{{ route('diagrams.index') }}">Limpiar</a>
        </div>
        --}}
      </form>
      

    <div class="row">
        @forelse ($diagrams as $diagram)
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">{{ $diagram->name }}</h5>
                        <p class="card-text"><small class="text-muted">Tipo: {{ ucfirst($diagram->type) }}</small></p>
                        @if($diagram->machine_category)
                            <p class="card-text"><small class="text-muted">Máquina: {{ $diagram->machine_category }}</small></p>
                        @endif
                        <p class="card-text">{{ Str::limit($diagram->description, 100) }}</p> {{-- Muestra una descripción limitada --}}

                        <div class="mt-3 d-flex justify-content-between align-items-center">
                            {{-- Botón "Abrir/Ver" --}}
                            <a href="{{ route('diagrams.show', $diagram->id) }}" class="btn btn-primary btn-sm">Ver Diagrama</a>

                            {{-- Botón "Generar QR" (Opciones adicionales) --}}
                            <a href="{{ route('diagrams.generate_qr_pdf', $diagram->id) }}" class="btn btn-outline-secondary btn-sm">Generar QR</a>
                            {{-- Podrías usar un dropdown aquí si hay más opciones --}}
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info" role="alert">
                    No se encontraron diagramas o manuales con los criterios de búsqueda/filtro.
                </div>
            </div>
        @endforelse
    </div>

    {{-- Paginación --}}
    <div class="d-flex justify-content-center">
        {{ $diagrams->appends(request()->query())->links() }}
    </div>
</div>
@endsection