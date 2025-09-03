@extends('layouts.app')

@section('title', 'Editar prellenado: ' . ($prefilledData->name ?? ('#'.$prefilledData->id)))

@section('content')
<div class="container mt-4">

    <a href="{{ route('admin.templates.prefilled-data.index') }}" class="btn btn-secondary mb-3">← Volver</a>

    @if (session('status'))
        <div class="alert alert-success" role="alert">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Corrige los siguientes campos:</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        // $rules = arreglo asociativo de la plantilla (clave => ...algo)
        $ruleKeys = array_keys($rules ?? []);
        $current  = $current ?? []; // data_json actual
        $newKeys      = $newKeys ?? [];
        $obsoleteKeys = $obsoleteKeys ?? [];
    @endphp

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h1 class="h5 mb-0">Editar prellenado</h1>
            <span class="badge bg-light text-dark">Plantilla: {{ $template->name ?? ('#'.$template->id) }}</span>
        </div>

        <div class="card-body">
            @if(empty($ruleKeys))
                <div class="alert alert-warning">
                    Esta plantilla no tiene <code>mapping_rules_json</code>.
                    No es posible editar valores basados en reglas.
                </div>
            @else
                <form action="{{ route('admin.templates.prefilled-data.update',  ['prefilledData' => $prefilledData->id]) }}" method="POST" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="row">
                        {{-- Columna izquierda: metadatos y relaciones --}}
                        <div class="col-lg-4 border-end">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nombre / Alias (opcional)</label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       id="name" name="name"
                                       value="{{ old('name', $prefilledData->name) }}">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="tag_id" class="form-label">Tag / Instrumento</label>
                                <select class="form-select @error('tag_id') is-invalid @enderror" id="tag_id" name="tag_id">
                                    <option value="">— Selecciona —</option>
                                    @foreach(($tags ?? collect()) as $t)
                                        <option value="{{ $t->id }}"
                                          @selected(old('tag_id', $prefilledData->tag_id)==$t->id)>
                                          {{ $t->tag ?? ('#'.$t->id) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('tag_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="sistema_id" class="form-label">Sistema</label>
                                <select class="form-select @error('sistema_id') is-invalid @enderror" id="sistema_id" name="sistema_id">
                                    <option value="">— Selecciona —</option>
                                    @foreach(($sistemas ?? collect()) as $s)
                                        <option value="{{ $s->id }}"
                                          @selected(old('sistema_id', $prefilledData->sistema_id)==$s->id)>
                                          {{ $s->sistema ?? ('#'.$s->id) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('sistema_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="servicio_id" class="form-label">Servicio</label>
                                <select class="form-select @error('servicio_id') is-invalid @enderror" id="servicio_id" name="servicio_id">
                                    <option value="">— Selecciona —</option>
                                    @foreach(($servicios ?? collect()) as $sv)
                                        <option value="{{ $sv->id }}"
                                          @selected(old('servicio_id', $prefilledData->servicio_id)==$sv->id)>
                                          {{ $sv->servicio ?? ('#'.$sv->id) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('servicio_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-4">
                                <label for="unidad_id" class="form-label">Unidad</label>
                                <select class="form-select @error('unidad_id') is-invalid @enderror" id="unidad_id" name="unidad_id">
                                    <option value="">— Selecciona —</option>
                                    @foreach(($unidades ?? collect()) as $u)
                                        <option value="{{ $u->id }}"
                                          @selected(old('unidad_id', $prefilledData->unidad_id)==$u->id)>
                                          {{ $u->unidad ?? ('#'.$u->id) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('unidad_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- (Opcional) permitir limpiar obsoletas:
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="drop_obsolete" name="drop_obsolete">
                                <label class="form-check-label" for="drop_obsolete">
                                    Eliminar claves obsoletas al guardar
                                </label>
                            </div>
                            --}}

                            <div class="small text-muted mt-3">
                                <div class="mb-1"><strong>Claves de reglas:</strong> {{ count($ruleKeys) }}</div>
                                @if(count($newKeys))
                                    <div class="text-success">Nuevas claves detectadas: {{ implode(', ', $newKeys) }}</div>
                                @endif
                                @if(count($obsoleteKeys))
                                    <div class="text-warning">Claves obsoletas (se conservarán por defecto): {{ implode(', ', $obsoleteKeys) }}</div>
                                @endif
                            </div>
                        </div>

                        {{-- Columna derecha: claves fijas + valores --}}
                        <div class="col-lg-8">
                            <h2 class="h6 mb-3">Datos prellenados (reglas actuales de la plantilla)</h2>

                            <div class="row g-3">
                                @foreach($ruleKeys as $key)
                                    @php $val = old('data_values.'.$key, $current[$key] ?? null); @endphp
                                    <div class="col-12">
                                        <div class="row g-2 align-items-center">
                                            <div class="col-md-5">
                                                <div class="input-group">
                                                    <span class="input-group-text">Clave</span>
                                                    <input type="text" class="form-control" value="{{ $key }}" readonly tabindex="-1">
                                                </div>
                                            </div>
                                            <div class="col-md-7">
                                                <input type="text"
                                                       class="form-control @error('data_values.'.$key) is-invalid @enderror js-value-input"
                                                       name="data_values[{{ $key }}]"
                                                       value="{{ $val }}"
                                                       placeholder="Valor para {{ $key }}">
                                                @error('data_values.'.$key)
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Vista previa JSON --}}
                            <div class="mt-4">
                                <label class="form-label">Vista previa del JSON a guardar</label>
                                <textarea id="jsonPreview" class="form-control" rows="8" readonly style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;"></textarea>
                                <div class="form-text">Se guardará en <code>data_json</code>. Las claves obsoletas actuales se conservarán por defecto.</div>
                            </div>

                            {{-- (Opcional) Sección de sólo lectura con obsoletas --}}
                            @if(count($obsoleteKeys))
                                <div class="mt-4">
                                    <details>
                                        <summary class="mb-2">Ver claves obsoletas (sólo lectura)</summary>
                                        <div class="row g-2">
                                            @foreach($obsoleteKeys as $okey)
                                                <div class="col-12">
                                                    <div class="input-group">
                                                        <span class="input-group-text">Obsoleta</span>
                                                        <input type="text" class="form-control" value="{{ $okey }}" readonly>
                                                        <span class="input-group-text">Valor</span>
                                                        <input type="text" class="form-control" value="{{ $current[$okey] ?? '' }}" readonly>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                </div>
                            @endif

                            <div class="d-flex gap-2 justify-content-end mt-4">
                                <a href="{{ url('admin/prefilled-data') }}" class="btn btn-outline-secondary">Cancelar</a>
                                <button class="btn btn-primary" type="submit">Guardar cambios</button>
                            </div>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>

{{-- Script ligero para vista previa JSON --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const inputs = document.querySelectorAll('.js-value-input');
    const preview = document.getElementById('jsonPreview');

    function buildPreview() {
        const obj = {};
        inputs.forEach(function (el) {
            const name = el.getAttribute('name') || '';
            const m = name.match(/^data_values\[(.+)\]$/);
            if (m) {
                obj[m[1]] = el.value === '' ? null : el.value;
            }
        });
        preview.value = JSON.stringify(obj, null, 2);
    }

    inputs.forEach(el => el.addEventListener('input', buildPreview));
    buildPreview();
});
</script>
@endsection
