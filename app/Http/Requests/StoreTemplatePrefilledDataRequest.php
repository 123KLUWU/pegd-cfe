<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTemplatePrefilledDataRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'template_id' => ['required','integer','exists:templates,id'],
            'name'        => ['nullable','string','max:255'],

            // Relaciones (ajusta nombres de tablas si cambian)
            'tag_id'      => ['nullable','integer','exists:tags,id'],
            'sistema_id'  => ['nullable','integer','exists:sistemas,id'],
            'servicio_id' => ['nullable','integer','exists:servicios,id'],
            'unidad_id'   => ['nullable','integer','exists:unidades,id'],

            // El formulario te manda solo valores (las claves vienen de las reglas)
            // Si envías como data_values[key] => valor
            'data_values' => ['required','array'],
            'data_values.*' => ['nullable','string'], // simple por ahora (sin "type")
        ];
    }
    public function messages(): array
    {
        return [
            'template_id.required' => 'Debes elegir una plantilla.',
            'template_id.exists'   => 'La plantilla seleccionada no existe.',
            'data_values.required' => 'Faltan los valores del prellenado.',
        ];
    }
}
