{{-- resources/views/admin/diagrams/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Subir Diagrama/Manual')

@section('content')
<div class="container mt-5">
    <h1>Subir Nuevo Diagrama/Manual</h1>

    @if (session('error'))
        <div class="alert alert-danger mt-3" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('admin.diagrams.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
            <label for="name" class="form-label">Nombre del Archivo (para mostrar):</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required autofocus>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="type" class="form-label">Tipo:</label>
            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                <option value="">Selecciona un tipo</option>
                <option value="diagram" {{ old('type') == 'diagram' ? 'selected' : '' }}>Diagrama</option>
                <option value="manual" {{ old('type') == 'manual' ? 'selected' : '' }}>Manual</option>
            </select>
            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Unidad --}}
        <div class="mb-3">
            <label class="form-label">Unidad</label>
            <select name="unidad_id" class="form-select">
            <option value="">—</option>
            @foreach($unidades as $u)
                <option value="{{ $u->id }}" @selected(old('unidad_id', $diagram->unidad_id ?? null)==$u->id)>
                {{ $u->unidad }}
                </option>
            @endforeach
            </select>
            @error('unidad_id') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>

        {{-- Clasificación --}}
        <div class="mb-3">
            <label class="form-label">Clasificación</label>
            <select name="classification_id" class="form-select">
            <option value="">—</option>
            @foreach($classifications->groupBy('name') as $grp => $items)
                <optgroup label="{{ $grp ?? 'General' }}">
                @foreach($items as $c)
                    <option value="{{ $c->id }}" @selected(old('classification_id', $diagram->classification_id ?? null)==$c->id)>
                    {{ $c->name }}
                    </option>
                @endforeach
                </optgroup>
            @endforeach
            </select>
            @error('classification_id') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        
        {{-- Autómata --}}
        <div class="mb-3">
            <label class="form-label">Autómata</label>
            <select name="automata_id" class="form-select">
            <option value="">—</option>
            @foreach($automatas as $a)
                <option value="{{ $a->id }}" @selected(old('automata_id', $diagram->automata_id ?? null)==$a->id)>
                {{ $a->name }}
                </option>
            @endforeach
            </select>
            @error('automata_id') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        {{-- 
        <div class="mb-3">
            <label for="machine_category" class="form-label">Categoría de Máquina:</label>
            <select class="form-select @error('machine_category') is-invalid @enderror" id="machine_category" name="machine_category">
                <option value="">Selecciona o deja vacío</option>
                @foreach($availableCategories as $category)
                    <option value="{{ $category }}" {{ old('machine_category') == $category ? 'selected' : '' }}>{{ $category }}</option>
                @endforeach
                 Opcional: permitir introducir una nueva categoría si no está en la lista 
                 <option value="__new__">-- Nueva Categoría --</option>
            </select>
            @error('machine_category')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        --}}
        {{-- Si implementas 'Nueva Categoría', necesitas JS para mostrar un input de texto --}}


        <div class="mb-3">
            <label for="description" class="form-label">Descripción:</label>
            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="diagram_file" class="form-label">Archivo (PDF, PNG, JPG, GIF, SVG):</label>
            <input type="file" class="form-control @error('diagram_file') is-invalid @enderror" id="diagram_file" name="diagram_file" required>
            @error('diagram_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" value="1" id="is_active" name="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">
                Activo (Visible para usuarios)
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Subir Archivo</button>
        <a href="{{ route('admin.diagrams.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
@endsection