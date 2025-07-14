{{-- resources/views/documents/customize_form.blade.php --}}
@extends('layouts.app')

@section('title', 'Generar Documento: ' . $template->name)

@section('content')
<div class="container mt-5">
    <a href="{{ route('templates.index') }}" class="btn btn-secondary mb-3">← Volver a Plantillas</a>

    <h1>Generar Documento: {{ $template->name }}</h1>
    <p class="lead">{{ $template->description ?? 'Esta plantilla sirve para la generación de documentos.' }}</p>

    <div class="alert alert-info" role="alert">
        <strong>Nota:</strong> Los documentos generados serán públicos y editables por cualquier persona con el enlace durante 3 horas, después se harán privados.
    </div>

    @if (session('error'))
        <div class="alert alert-danger mt-3" role="alert">
            {{ session('error') }}
        </div>
    @endif

    {{-- Opciones de Generación --}}
    <div class="row">
        <div class="col-md-6">
            <div class="card h-100 mb-3">
                <div class="card-body text-center">
                    <h5 class="card-title">Generar en Blanco</h5>
                    <p class="card-text">Crea una copia limpia de la plantilla original.</p>
                    <form action="{{ route('documents.generate.blank') }}" method="POST">
                        @csrf
                        <input type="hidden" name="template_id" value="{{ $template->id }}">
                        <button type="submit" class="btn btn-primary">Generar</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100 mb-3">
                <div class="card-body text-center">
                    <h5 class="card-title">Personalizar Documento</h5>
                    <p class="card-text">Ingresa los datos exactos que deseas para tu documento.</p>
                    {{-- Este botón llevaría al formulario de personalización detallado --}}
                    <a href="#" class="btn btn-secondary" id="open-customize-form">Ir a Formulario</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Formulario de Personalización (inicialmente oculto, se muestra con JS) --}}
    <div id="customize-form-section" class="mt-5 border p-4 rounded">
        <h2>Formulario de Personalización</h2>
        <p>Rellena los campos deseados. Los datos se mapearán a la plantilla.</p>
        <form action="{{ route('documents.generate.custom') }}" method="POST">
            @csrf
            <input type="hidden" name="template_id" value="{{ $template->id }}">

            {{-- Campos de ejemplo para personalización --}}
            <div class="mb-3">
                <label for="tag_instrumento" class="form-label">Tag de Instrumento:</label>
                <div class="input-group">
                    <input type="text" class="form-control @error('tag_instrumento') is-invalid @enderror" id="tag_instrumento" name="tag_instrumento" value="{{ old('tag_instrumento') }}">
                    <button class="btn btn-outline-secondary lookup-button" type="button" data-field-id="tag_instrumento" data-lookup-type="tags">...</button>
                    @error('tag_instrumento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="unidad_maquina" class="form-label">Unidad de Máquina:</label>
                <div class="input-group">
                    <input type="text" class="form-control @error('unidad_maquina') is-invalid @enderror" id="unidad_maquina" name="unidad_maquina" value="{{ old('unidad_maquina') }}">
                    <button class="btn btn-outline-secondary lookup-button" type="button" data-field-id="unidad_maquina" data-lookup-type="unidades">...</button>
                    @error('unidad_maquina')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            
            <div class="mb-3">
                <label for="rango_min_operativo" class="form-label">Rango Mínimo Operativo:</label>
                <input type="number" step="any" class="form-control @error('rango_min_operativo') is-invalid @enderror" id="rango_min_operativo" name="rango_min_operativo" value="{{ old('rango_min_operativo') }}">
                @error('rango_min_operativo')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="observaciones" class="form-label">Observaciones:</label>
                <textarea class="form-control @error('observaciones') is-invalid @enderror" id="observaciones" name="observaciones" rows="3">{{ old('observaciones') }}</textarea>
                @error('observaciones')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="btn btn-success">Generar Documento Personalizado</button>
        </form>
    </div>
</div>
<div class="modal fade" id="lookupModal" tabindex="-1" aria-labelledby="lookupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lookupModalLabel">Seleccionar <span id="lookupModalTitle"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="text" class="form-control mb-3" id="lookupSearchInput" placeholder="Buscar...">
                <ul class="list-group" id="lookupResultsList">
                    {{-- Aquí se cargarán los resultados --}}
                </ul>
                <p id="lookupNoResults" class="text-muted text-center mt-3" style="display: none;">No se encontraron resultados.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@stack('scripts')

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const visibilitySelect = document.getElementById('document_visibility');
    const blankVisibilityInput = document.getElementById('visibility_blank');
    const predefinedVisibilityInput = document.getElementById('visibility_predefined');
    const customVisibilityInput = document.getElementById('visibility_custom');
    const customizeFormSection = document.getElementById('customize-form-section');
    const openCustomizeFormButton = document.getElementById('open-customize-form');

    visibilitySelect.addEventListener('change', function() {
        const selectedValue = this.value;
        blankVisibilityInput.value = selectedValue;
        predefinedVisibilityInput.value = selectedValue;
        customVisibilityInput.value = selectedValue;
    });

    visibilitySelect.dispatchEvent(new Event('change'));

    // Mostrar/Ocultar formulario de personalización
    openCustomizeFormButton.addEventListener('click', function(e) {
        e.preventDefault();
        customizeFormSection.style.display = 'block';
        customizeFormSection.scrollIntoView({ behavior: 'smooth' }); // Desplazarse a la sección
    });


    // Si hubo errores de validación para el formulario de personalización, mostrarlo al cargar
    @if ($errors->has('tag_instrumento') || $errors->has('unidad_maquina') || $errors->has('rango_min_operativo') || $errors->has('observaciones'))
        customizeFormSection.style.display = 'block';
        customizeFormSection.scrollIntoView({ behavior: 'smooth' });
    @endif
    
    const lookupButtons = document.querySelectorAll('.lookup-button');
    const lookupModal = new bootstrap.Modal(document.getElementById('lookupModal'));
    const lookupModalTitle = document.getElementById('lookupModalTitle');
    const lookupSearchInput = document.getElementById('lookupSearchInput');
    const lookupResultsList = document.getElementById('lookupResultsList');
    const lookupNoResults = document.getElementById('lookupNoResults');

    let currentLookupFieldId = null; // El ID del input al que se le asignará el valor
    let currentLookupType = null; // 'tags', 'unidades', etc.

    lookupButtons.forEach(button => {
        button.addEventListener('click', function() {
            currentLookupFieldId = this.dataset.fieldId;
            currentLookupType = this.dataset.lookupType;

            // Actualizar título del modal
            lookupModalTitle.textContent = currentLookupType.charAt(0).toUpperCase() + currentLookupType.slice(1); // Capitalizar
            lookupSearchInput.value = ''; // Limpiar búsqueda
            lookupResultsList.innerHTML = ''; // Limpiar resultados anteriores
            lookupNoResults.style.display = 'none';

            fetchLookupData(''); // Cargar datos iniciales (vacío para todos)
            lookupModal.show(); // Mostrar el modal
        });
    });

    // Escuchar cambios en el input de búsqueda del modal
    lookupSearchInput.addEventListener('input', debounce(function() {
        fetchLookupData(this.value);
    }, 300)); // Debounce para no hacer muchas peticiones

    // Función para hacer la petición a la API y mostrar resultados
    function fetchLookupData(searchTerm) {
        if (!currentLookupType) return;

        const apiUrl = `/api/lookup/${currentLookupType}?search=${searchTerm}`; // Usa tu ruta API

        fetch(apiUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest', // Laravel espera esto para peticiones AJAX
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') // Si usas CSRF en APIs
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            lookupResultsList.innerHTML = ''; // Limpiar lista
            if (data.length === 0) {
                lookupNoResults.style.display = 'block';
            } else {
                lookupNoResults.style.display = 'none';
                data.forEach(item => {
                    const listItem = document.createElement('li');
                    listItem.classList.add('list-group-item', 'list-group-item-action');
                    // El texto a mostrar. Asume que el API devuelve 'name' o 'tag'
                    listItem.textContent = item.name || item.tag; 
                    listItem.dataset.id = item.id; // Guarda el ID si lo necesitas
                    listItem.dataset.value = item.name || item.tag; // Guarda el valor a insertar
                    listItem.addEventListener('click', function() {
                        // Asignar el valor seleccionado al campo principal
                        document.getElementById(currentLookupFieldId).value = this.dataset.value;
                        lookupModal.hide(); // Ocultar el modal
                    });
                    lookupResultsList.appendChild(listItem);
                });
            }
        })
        .catch(error => {
            console.error('Error fetching lookup data:', error);
            lookupResultsList.innerHTML = '<li class="list-group-item text-danger">Error al cargar datos.</li>';
            lookupNoResults.style.display = 'none';
        });
    }

    // Función debounce para limitar la frecuencia de las peticiones de búsqueda
    function debounce(func, delay) {
        let timeout;
        return function(...args) {
            const context = this;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), delay);
        };
    }
});

</script>
@endpush
@endsection