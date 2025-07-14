document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('key-value-fields-container');
    const addButton = document.getElementById('add-key-value-row');

    if (addButton) { // Asegúrate de que el botón exista en la página actual
        addButton.addEventListener('click', function () {
            addRow();
        });
    }

    if (container) { // Asegúrate de que el contenedor exista
        container.addEventListener('click', function (event) {
            if (event.target.classList.contains('remove-row-btn')) {
                removeRow(event.target);
            }
        });
    }
    // Función para añadir una fila de clave-valor
    function addRow(container, key = '', value = '') {
        const row = document.createElement('div');
        row.classList.add('row', 'mb-2', 'key-value-row');
        row.innerHTML = `
            <div class="col-5">
                <input type="text" name="dynamic_keys[]" class="form-control" placeholder="Clave Lógica" value="${key}">
            </div>
            <div class="col-5">
                <input type="text" name="dynamic_values[]" class="form-control" placeholder="Valor / Ubicación" value="${value}">
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-danger btn-sm remove-row-btn">X</button>
            </div>
        `;
        container.appendChild(row);
    }

    // Función para eliminar una fila de clave-valor
    function removeRow(button, container) {
        const rows = container.querySelectorAll('.key-value-row');
        if (rows.length > 1) { // No eliminar la última fila si queremos que siempre haya al menos una
            button.closest('.key-value-row').remove();
        } else {
            // Si es la última fila, simplemente vacía los campos
            const lastRow = rows[0];
            lastRow.querySelector('input[name="dynamic_keys[]"]').value = '';
            lastRow.querySelector('input[name="dynamic_values[]"]').value = '';
        }
    }

    // --- Aplicar lógica a los contenedores de campos dinámicos ---

    // Contenedor para mapping_rules_json (en create/edit de Template)
    const containerMapping = document.getElementById('key-value-fields-container-mapping');
    const addButtonMapping = document.getElementById('add-key-value-row-mapping');

    if (addButtonMapping && containerMapping) {
        addButtonMapping.addEventListener('click', function () {
            addRow(containerMapping);
        });
        containerMapping.addEventListener('click', function (event) {
            if (event.target.classList.contains('remove-row-btn')) {
                removeRow(event.target, containerMapping);
            }
        });
        // Asegurarse de que al menos una fila vacía esté presente al cargar si no hay datos
        if (containerMapping.querySelectorAll('.key-value-row').length === 0) {
            addRow(containerMapping);
        }
    }

    // Contenedor para data_json (en create/edit de TemplatePrefilledData)
    const containerData = document.getElementById('key-value-fields-container-data');
    const addButtonData = document.getElementById('add-key-value-row-data');

    if (addButtonData && containerData) {
        addButtonData.addEventListener('click', function () {
            addRow(containerData);
        });
        containerData.addEventListener('click', function (event) {
            if (event.target.classList.contains('remove-row-btn')) {
                removeRow(event.target, containerData);
            }
        });
        // Asegurarse de que al menos una fila vacía esté presente al cargar si no hay datos
        if (containerData.querySelectorAll('.key-value-row').length === 0) {
            addRow(containerData);
        }
    }


    // --- Lógica para mostrar/ocultar campos de ID/Archivo (para CREATE) ---
    const sourceOptionRadiosCreate = document.querySelectorAll('input[name="source_option"]');
    const googleDriveIdGroupCreate = document.getElementById('google_drive_id_group');
    const templateFileGroupCreate = document.getElementById('template_file_group');
    const googleDriveIdInputCreate = document.getElementById('google_drive_id');
    const templateFileInputCreate = document.getElementById('template_file');

    function toggleSourceFieldsCreate() {
        if (document.getElementById('source_id') && document.getElementById('source_id').checked) {
            googleDriveIdGroupCreate.style.display = 'block';
            templateFileGroupCreate.style.display = 'none';
            templateFileInputCreate.value = ''; // Limpiar campo de archivo
        } else if (document.getElementById('source_file') && document.getElementById('source_file').checked) {
            googleDriveIdGroupCreate.style.display = 'none';
            templateFileGroupCreate.style.display = 'block';
            googleDriveIdInputCreate.value = ''; // Limpiar campo de ID
        }
    }

    if (sourceOptionRadiosCreate.length > 0) {
        sourceOptionRadiosCreate.forEach(radio => {
            radio.addEventListener('change', toggleSourceFieldsCreate);
        });
        toggleSourceFieldsCreate(); // Ejecutar al cargar la página para establecer el estado inicial
    }


    // --- Lógica para mostrar/ocultar campos de ID/Archivo (para EDIT) ---
    const sourceOptionRadiosEdit = document.querySelectorAll('input[name="source_option"]');
    const googleDriveIdGroupEdit = document.getElementById('google_drive_id_group_edit');
    const templateFileGroupEdit = document.getElementById('template_file_group_edit');
    const googleDriveIdInputEdit = document.getElementById('google_drive_id_edit');
    const templateFileInputEdit = document.getElementById('template_file_edit');

    function toggleSourceFieldsEdit() {
        if (document.getElementById('source_id_edit') && document.getElementById('source_id_edit').checked) {
            googleDriveIdGroupEdit.style.display = 'block';
            templateFileGroupEdit.style.display = 'none';
            templateFileInputEdit.value = ''; // Limpiar campo de archivo
        } else if (document.getElementById('source_file_edit') && document.getElementById('source_file_edit').checked) {
            googleDriveIdGroupEdit.style.display = 'none';
            templateFileGroupEdit.style.display = 'block';
            googleDriveIdInputEdit.value = ''; // Limpiar campo de ID
        }
    }

    if (sourceOptionRadiosEdit.length > 0) {
        sourceOptionRadiosEdit.forEach(radio => {
            radio.addEventListener('change', toggleSourceFieldsEdit);
        });
        // Ejecutar al cargar la página para establecer el estado inicial
        // Si no hay old('source_option'), usar el ID de Drive como default
        const initialSourceOptionEdit = document.getElementById('source_id_edit') ? "{{ old('source_option', 'id') }}" : null;
        if (initialSourceOptionEdit === 'file') {
            document.getElementById('source_file_edit').checked = true;
        } else if (document.getElementById('source_id_edit')) { // Asegurarse de que el radio exista
            document.getElementById('source_id_edit').checked = true;
        }
        toggleSourceFieldsEdit();
    }
});