@extends('layouts.app')

@section('title', 'Carga masiva de diagramas')

@section('content')
<div class="container py-4">
  <h1 class="mb-3">Carga masiva (PDF)</h1>

  @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if (session('warning'))
    <div class="alert alert-warning">{{ session('warning') }}</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger">
      <strong>Revisa los errores:</strong>
      <ul class="mb-0">
        @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card shadow-sm">
    <div class="card-body">
      <form action="{{ route('admin.diagramas.bulk.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Unidad (todos)</label>
            <select name="unidad_id" class="form-select">
              <option value="">— Sin asignar —</option>
              @foreach($unidades as $u)
                <option value="{{ $u->id }}">{{ $u->unidad }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Sistema (todos)</label>
            <select name="sistema_id" class="form-select">
              <option value="">— Sin asignar —</option>
              @foreach($sistemas as $s)
                <option value="{{ $s->id }}">{{ $s->sistema }} @if($s->clave) ({{ $s->clave }}) @endif</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Autómata (todos)</label>
            <select name="automata_id" class="form-select">
              <option value="">— Sin asignar —</option>
              @foreach($automatas as $a)
                <option value="{{ $a->id }}">{{ $a->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Clasificación (todos)</label>
            <select name="classification_id" class="form-select">
              <option value="">— Sin asignar —</option>
              @foreach($classif as $c)
                <option value="{{ $c->id }}">{{ $c->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label">Tipo</label>
            <select name="type_for_all" class="form-select">
              <option value="diagram">Diagrama</option>
              <option value="manual">Manual</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Activo</label>
            <select name="is_active_all" class="form-select">
              <option value="1">Sí</option>
              <option value="0">No</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Categoría de máquina</label>
            <input type="text" name="machine_category_all" class="form-control" placeholder="Opcional">
          </div>
          <div class="col-12">
            <label class="form-label">Descripción (todos)</label>
            <textarea name="description_all" class="form-control" rows="2" placeholder="Opcional"></textarea>
          </div>

          <div class="col-12">
            <label class="form-label">Selecciona PDFs</label>
            <input type="file" class="form-control" name="files[]" accept="application/pdf" multiple required>
            <div class="form-text">Puedes seleccionar varios a la vez. Máx 500MB en total.</div>
          </div>

          <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Subir</button>
            <a href="{{ route('admin.diagramas.bulk.create') }}" class="btn btn-outline-secondary">Cancelar</a>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
