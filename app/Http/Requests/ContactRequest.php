<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;

class ContactRequest extends FormRequest
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
        if ($route == 'contact.bulkDestroy') {
            return [
                'ids' => ['required', 'array'],
                'ids.*' => ['required', 'integer', 'exists:contacts,id']
            ];
        } elseif ($route == 'contact.store') {
            return [
                'contact_user_id' => ['required', 'exists:users,id'],
                'contact_name' => ['nullable', 'string', 'max:255'],
            ];
        }elseif($route == 'contact.sync'){
            return [
                'mobiles' => ['required', 'array'],
                'mobiles.*' => ['required', 'string'],
            ];
        }
        return [
            'query' => ['required', 'string', 'max:255']
        ];
    }

    public function attributes()
    {
        return [
            'contact_user_id' => 'شناسه مخاطب',
            'contact_name' => 'نام مخاطب',
            'ids' => 'مخاطبین',
            'id.*' => 'مخاطب',
            'mobiles' => 'شماره های مخاطبین',
            'mobiles.*' => 'شماره مخاطب',
            'query' => 'عبارت جستجو',
        ];
    }
}
