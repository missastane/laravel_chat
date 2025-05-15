<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\UniquePhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $route = Route::currentRouteName();
        if ($route == 'profile.update.contact') {
            return [
                'id' => 'required|min:11|max:64|regex:/^[a-zA-Z0-9_.@\+]*$/'
            ];
        } elseif ($route == 'profile.contact.confirm') {
            return [
                'otp' => 'required|min:6|max:6'
            ];
        } elseif ($route == 'profile.update.avatar') {
            return [
                'profile_photo_path' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ];
        } elseif ($route == 'profile.update.password') {
            return [
                'current_password' => 'required|string|min:8',
                'new_password' => ['required', 'unique:users', Password::min(8)->letters()->mixedCase()->numbers()->symbols()->uncompromised(), 'confirmed'],
            ];
        }
        return [
            'username' => ['required', 'string', 'max:120','min:2','regex:/^[ا-یa-zA-Z0-9\-۰-۹ء-ي.,؟?_\.! ]+$/u', Rule::unique('users', 'username')->ignore($this->user()->id)],
            'first_name' => ['nullable', 'string', 'max:120','min:2','regex:/^[ا-یa-zA-Z\-ء-ي ]+$/u'],
            'last_name' => ['nullable', 'string', 'max:120','min:2','regex:/^[ا-یa-zA-Z\-ء-ي ]+$/u'],
            'national_code' => ['nullable', 'string', 'max:255', Rule::unique('users', 'national_code')->ignore($this->user()->id)],
        ];
    }

    public function attributes()
    {
        return [
            'id' => 'ایمیل یا شماره موبایل',
            'otp' => 'رمز شش رقمی یکبار مصرف',
            'profile_photo_path' => 'تصویر پروفایل',
            'current_password' => 'کلمه عبور فعلی',
            'new_password' => 'کلمه عبور جدید',
            'username' => 'نام کاربری',
            'first_name' => 'نام',
            'last_name' => 'نام خانوادگی',
            'national_code' => 'کد ملی',
            'mobile' => 'موبایل',
            'email' => 'ایمیل',
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
