@extends('layouts.app')

@section('title', 'Revisión de carga masiva')

@section('content')
<div class="container py-4">
  <h1 class="mb-3">Revisión de carga masiva</h1>

  @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  @if (!empty($errors) && count($errors))
    <div class="alert alert-warning">
      <strong>Archivos con error durante la carga:</strong>
      <ul class="mb-0">
        @foreach($errors as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card shadow-sm">
    <div class="card-body">
      <form action="{{ route('admin.diagramas.bulk.update') }}" method="POST">
        @csrf

        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>Archivo</th>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Activo</th>
                <th>Unidad</th>
                <th>Sistema</th>
                <th>Autómata</th>
                <th>Clasificación</th>
                <th>Categoría máquina</th>
                <th>Descripción</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($diagrams as $i => $d)
              <tr>
                <td>
                  <div class="small text-muted">{{ $d->file_original_name }}</div>
                  <input type="hidden" name="diagram_id[]" value="{{ $d->id }}">
                </td>
                <td>
                  <input type="text" name="name[]" class="form-control form-control-sm" value="{{ $d->name }}">
                </td>
                <td>
                  <select name="type[]" class="form-select form-select-sm">
                    <option value="diagram" @selected($d->type==='diagram')>Diagrama</option>
                    <option value="manual"  @selected($d->type==='manual')>Manual</option>
                  </select>
                </td>
                <td class="text-center">
                  <input type="hidden" name="is_active[]" value="0">
                  <input type="checkbox" class="form-check-input" name="is_active[]" value="1" @checked($d->is_active)>
                </td>
                <td>
                  <select name="unidad_id[]" class="form-select form-select-sm">
                    <option value="">— Sin —</option>
                    @foreach($unidades as $u)
                      <option value="{{ $u->id }}" @selected($d->unidad_id==$u->id)>{{ $u->unidad }}</option>
                    @endforeach
                  </select>
                </td>
                <td>
                  <select name="sistema_id[]" class="form-select form-select-sm">
                    <option value="">— Sin —</option>
                    @foreach($sistemas as $s)
                      <option value="{{ $s->id }}" @selected($d->sistema_id==$s->id)>
                        {{ $s->sistema }} @if($s->clave) ({{ $s->clave }}) @endif
                      </option>
                    @endforeach
                  </select>
                </td>
                <td>
                  <select name="automata_id[]" class="form-select form-select-sm">
                    <option value="">— Sin —</option>
                    @foreach($automatas as $a)
                      <option value="{{ $a->id }}" @selected($d->automata_id==$a->id)>{{ $a->name }}</option>
                    @endforeach
                  </select>
                </td>
                <td>
                  <select name="classification_id[]" class="form-select form-select-sm">
                    <option value="">— Sin —</option>
                    @foreach($classif as $c)
                      <option value="{{ $c->id }}" @selected($d->classification_id==$c->id)>{{ $c->name }}</option>
                    @endforeach
                  </select>
                </td>
                <td>
                  <input type="text" name="machine_category[]" class="form-control form-control-sm" value="{{ $d->machine_category }}">
                </td>
                <td>
                  <textarea name="description[]" class="form-control form-control-sm" rows="1">{{ $d->description }}</textarea>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">Guardar cambios</button>
          <a href="{{ route('admin.diagramas.bulk.create') }}" class="btn btn-outline-secondary">Omitir y volver</a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
