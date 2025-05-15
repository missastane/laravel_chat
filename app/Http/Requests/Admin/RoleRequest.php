<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
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
        $route = Route::currentRouteName();
        if ($route === 'admin.role.store') {
            return [
                'name' => 'required|string|unique:roles,name|regex:/^[a-zA-Z\, ]+$/u'
            ];
        }
        return [
            'name' => ['required', 'string', 'regex:/^[a-zA-Z\, ]+$/u', Rule::unique('roles', 'name')->ignore($this->route('role'))]
        ];
    }
}
