<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

class MessageRequest extends FormRequest
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
        if ($route == 'message.send' || $route == 'admin.message.send') {
            return [
                'content' => 'nullable|required_without:media|string|max:1000',
                'media' => 'nullable|required_without:content',
                'media.*' => 'nullable|file|max:20480|mimes:jpeg,png,jpg,mp4,avi,mov'
            ];
        } elseif ($route == 'message.update' || $route == 'admin.message.update') {
            return [
                'content' => 'required|string|max:1000',
            ];
        } elseif ($route == 'message.reply-privately') {
            return [
                'content' => 'nullable|required_without:media|string|max:1000',
                'media' => 'nullable|required_without:content',
                'media.*' => 'nullable|file|max:20480|mimes:jpeg,png,jpg,mp4,avi,mov'
            ];
        } elseif ($route == 'message.reply-to-message') {
            return [
                'parent_id' => 'nullable|exists:messages,id',
                'content' => 'nullable|required_without:media|string|max:1000',
                'media' => 'nullable|required_without:content',
                'media.*' => 'nullable|file|max:20480|mimes:jpeg,png,jpg,mp4,avi,mov'
            ];
        } elseif ($route == 'message.forward') {
            $message = $this->route('message');
            return [
                'conversations' => 'required|array',
                'conversations.*' => ['required', Rule::exists('conversations','id')]
            ];
        }
        return [
            'reason' => 'required|string|max:500',
        ];
    }

    public function attributes()
    {
        return [
            'conversation_id' => 'مکالمه',
            'content' => 'محتوا',
            'message_type' => 'نوع پیام',
            'media' => 'فایل ضمیمه',
            'reason' => 'دلیل گزارش',
            'conversations' => 'مکالمه',
            'conversations.*' => 'مکالمه',
        ];
    }
}
