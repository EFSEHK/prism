<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class NavigationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'parent_id' => 'nullable|exists:navigations,id',
            'component' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'permission_id' => 'nullable|exists:permissions,id',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'is_hidden' => 'nullable|boolean',
        ];

        // Add required rules for store, make them optional for update
        if ($this->isMethod('POST')) {
            $rules['name'] = 'required|string|max:255';
            $rules['path'] = 'required|string|max:255';
        } elseif ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['name'] = 'sometimes|required|string|max:255';
            $rules['path'] = 'sometimes|required|string|max:255';
        }

        return $rules;
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422));
    }
} 