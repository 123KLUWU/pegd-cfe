// resources/js/app.js (o un nuevo archivo como dynamic-forms.js)

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

    function addRow(key = '', value = '') {
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

    function removeRow(button) {
        // Asegúrate de no eliminar la última fila si quieres que siempre haya al menos una
        if (container.querySelectorAll('.key-value-row').length > 1) {
            button.closest('.key-value-row').remove();
        } else {
            // Si es la última fila, simplemente vacía los campos
            const lastRow = container.querySelector('.key-value-row');
            lastRow.querySelector('input[name="dynamic_keys[]"]').value = '';
            lastRow.querySelector('input[name="dynamic_values[]"]').value = '';
        }
    }

    // Opcional: Asegurarse de que al menos una fila vacía esté presente al cargar la página
    // si no hay datos existentes. Ya lo maneja el @forelse en el Blade.
});