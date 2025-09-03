<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTemplatePrefilledDataRequest extends FormRequest
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
            'name'        => ['nullable','string','max:255'],
            'tag_id'      => ['nullable','integer','exists:tags,id'],
            'sistema_id'  => ['nullable','integer','exists:sistemas,id'],
            'servicio_id' => ['nullable','integer','exists:servicios,id'],
            'unidad_id'   => ['nullable','integer','exists:unidades,id'],
            'data_values' => ['required','array'],
            'data_values.*' => ['nullable','string'],
        ];
    }

}
