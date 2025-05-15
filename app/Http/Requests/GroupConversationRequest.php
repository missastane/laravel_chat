<?php

namespace App\Http\Requests;

use App\Rules\ActiveConversationMember;
use App\Rules\NotInGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

class GroupConversationRequest extends FormRequest
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
        if ($route == 'conversation.group.store') {
            return [
                'users' => 'array|required',
                'users.*' => [
                    'required',
                    'integer',
                    Rule::exists('users', 'id')->where(function ($query) {
                        $query->where('id', '!=', auth()->id());
                    }),
                ],
                'privacy_type' => 'required|in:0,1,2',
                'name' => 'required|min:2|max:120|regex:/^[ا-یa-zA-Z0-9\-۰-۹ء-ي.,؟?_\.! ]+$/u'
            ];
        } elseif ($route == 'conversation.group.update-member-role') {
            return [
                'is_admin' => 'required|in:1,2',  // 1: true, 2: false
            ];
        } elseif ($route == 'conversation.group.add-member') {
            return [
                'users' => 'required|array',
                'users.*' => [
                    'required',
                    'exists:users,id',
                    new NotInGroup($this->route('groupConversation'))
                ],
            ];
        } elseif ($route == 'conversation.group.transfer-ownership') {
            $groupConversation = $this->route('groupConversation');
            $conversation = $groupConversation->conversation;
            return [
                'user_id' => [
                    'required',
                    'exists:users,id',
                    new ActiveConversationMember($conversation)
                ]
            ];
        }
        return [
            'avatar' => 'required|image|mimes:jpeg,jpg,png,gif'
        ];
    }

    public function attributes()
    {
        return [
            'users' => 'اعضای گروه',
            'privacy_type' => 'نوع حریم خصوصی',
            'name' => 'نام گروه',
            'is_admin' => 'آیا ادمین هست؟',
            'avatar' => 'تصویر آواتار گروه',
            'user.*' => 'عضو گروه',
            'user_id' => 'شناسه کاربری'
        ];
    }
}
