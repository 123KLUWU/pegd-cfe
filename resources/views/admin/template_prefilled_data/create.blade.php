{{-- resources/views/admin/template_prefilled_data/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Crear prellenado: ' . ($template->name ?? ('Plantilla #'.$template->id)))

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
        $raw = $template->mapping_rules_json ?? '[]';
        $rules = is_array($raw) ? $raw : json_decode($raw, true);
        $rules = $rules ?: [];
        $keys  = array_keys($rules);
    @endphp

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h1 class="h5 mb-0">Crear prellenado</h1>
            <span class="badge bg-light text-dark">Plantilla: {{ $template->name ?? ('#'.$template->id) }}</span>
        </div>

        <div class="card-body">
            @if(empty($keys))
                <div class="alert alert-warning">
                    Esta plantilla no tiene <code>mapping_rules_json</code> o está vacío.
                    Agrega reglas a la plantilla antes de crear un prellenado.
                </div>
            @else
                <form action="{{ route('admin.templates.prefilled-data.store',  ['template' => $template->id]) }}" method="POST" novalidate>
                    @csrf
                    <input type="hidden" name="template_id" value="{{ $template->id }}">

                    <div class="row">
                        {{-- Columna izquierda: metadatos y relaciones --}}
                        <div class="col-lg-4 border-end">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nombre / Alias (opcional)</label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       id="name" name="name" value="{{ old('name') }}"
                                       placeholder="Ej. Prellenado Toma de muestras A-01">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="tag_id" class="form-label">Tag / Instrumento</label>
                                <select class="form-select @error('tag_id') is-invalid @enderror" id="tag_id" name="tag_id">
                                    <option value="">— Selecciona —</option>
                                    @foreach(($tags ?? collect()) as $t)
                                        <option value="{{ $t->id }}" @selected(old('tag_id')==$t->id)>{{ $t->name ?? $t->tag ?? ('#'.$t->id) }}</option>
                                    @endforeach
                                </select>
                                @error('tag_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="sistema_id" class="form-label">Sistema</label>
                                <select class="form-select @error('sistema_id') is-invalid @enderror" id="sistema_id" name="sistema_id">
                                    <option value="">— Selecciona —</option>
                                    @foreach(($sistemas ?? collect()) as $s)
                                        <option value="{{ $s->id }}" @selected(old('sistema_id')==$s->id)>{{ $s->name ?? $s->sistema ?? ('#'.$s->id) }}</option>
                                    @endforeach
                                </select>
                                @error('sistema_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="servicio_id" class="form-label">Servicio</label>
                                <select class="form-select @error('servicio_id') is-invalid @enderror" id="servicio_id" name="servicio_id">
                                    <option value="">— Selecciona —</option>
                                    @foreach(($servicios ?? collect()) as $sv)
                                        <option value="{{ $sv->id }}" @selected(old('servicio_id')==$sv->id)>{{ $sv->name ?? $sv->servicio ?? ('#'.$sv->id) }}</option>
                                    @endforeach
                                </select>
                                @error('servicio_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-4">
                                <label for="unidad_id" class="form-label">Unidad</label>
                                <select class="form-select @error('unidad_id') is-invalid @enderror" id="unidad_id" name="unidad_id">
                                    <option value="">— Selecciona —</option>
                                    @foreach(($unidades ?? collect()) as $u)
                                        <option value="{{ $u->id }}" @selected(old('unidad_id')==$u->id)>{{ $u->name ?? $u->unidad ?? ('#'.$u->id) }}</option>
                                    @endforeach
                                </select>
                                @error('unidad_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="small text-muted">
                                <div class="mb-1"><strong>Reglas encontradas:</strong> {{ count($keys) }}</div>
                                @if(!empty($template->description))
                                    <div class="mb-1">{{ $template->description }}</div>
                                @endif
                                <div>Las claves se fijan por plantilla; aquí sólo capturas **valores**.</div>
                            </div>
                        </div>

                        {{-- Columna derecha: claves fijas + valores --}}
                        <div class="col-lg-8">
                            <h2 class="h6 mb-3">Datos prellenados (desde reglas de mapeo)</h2>

                            <div class="row g-3">
                                @foreach($keys as $key)
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
                                                       value="{{ old('data_values.'.$key) }}"
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
                                <div class="form-text">Se guardará en <code>data_json</code>.</div>
                            </div>

                            <div class="d-flex gap-2 justify-content-end mt-4">
                                <a href="{{ url('admin/prefilled-data') }}" class="btn btn-outline-secondary">Cancelar</a>
                                <button class="btn btn-primary" type="submit">Guardar prellenado</button>
                            </div>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>

{{-- Script ligero para armar la vista previa JSON en vivo --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const inputs = document.querySelectorAll('.js-value-input');
    const preview = document.getElementById('jsonPreview');

    function buildPreview() {
        const obj = {};
        inputs.forEach(function (el) {
            // name="data_values[CLAVE]"
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
