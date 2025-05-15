<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rules\Password;

class AuthRequest extends FormRequest
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
        if ($route === 'register') {
            return [
                'username' => 'required|string|unique:users,username',
                'email' => 'required|string|email|unique:users,email',
                'password' => ['required', 'unique:users,password', Password::min(8)->letters()->mixedCase()->numbers()->symbols()->uncompromised(), 'confirmed'],
            ];
        }
        elseif($route === 'login')
        {
            return [
                'email' => 'required|string|email|exists:users,email',
                'password' => 'required'
            ];
        }
        elseif($route == 'profile.update.password'){
            return[
                'new_password' => ['required', 'unique:users,password', Password::min(8)->letters()->mixedCase()->numbers()->symbols()->uncompromised(), 'confirmed'],
            ];
        }
        return [

        ];
    }

    public function attributes()
    {
        return [
            'email' => 'ایمیل',
            'password' => 'کلمه عبور',
            'new_password' => 'کلمه عبور جدید',
        ];
    }
    public function messages()
    {
        return [

            'password.letters' => 'رمز عبور باید شامل حروف باشد',
            'password.mixed' => 'رمز عبور باید حروف بزرگ و کوچک داشته باشد',
            'password.numbers' => 'رمز عبور باید شامل اعداد باشد',
            'password.symbols' => 'رمز عبور باید شامل نمادها باشد',
            'password.uncompromised' => 'رمز عبور شما در معرض خطر است',
        ];
    }
}
