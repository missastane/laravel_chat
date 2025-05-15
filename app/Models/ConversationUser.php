<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConversationUser extends Model
{
    protected $table = 'conversation_user';
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'conversation_id',
        'user_id',
        'is_admin',
        'status',
        'joined_at',
        'left_at',
        'is_archived',
        'is_muted',
        'is_favorite',
        'is_pinned',
        'last_seen_message_id'
    ];

    protected $hidden = ['conversation_id','user_id','is_archived','is_pinned','is_muted','is_favorite','last_seen_message_id','status','updated_at','deleted_at','left_at','membership_status'];
    protected $appends = ['is_archived_value','is_pinned_value','is_muted_value','is_favorite_value','role'];
    public function getIsArchivedValueAttribute()
    {
        if($this->is_archived == 2)
        {
            return 'مکالمه برای این کاربر آرشیو نشده است';
        }
        else{
            return 'مکالمه برای این کاربر آرشیو شده است';
        }
    }
    public function getIsAdminValueAttribute()
    {
        if($this->is_admin == 1)
        {
            return 'کاربر ادمین گروه است';
        }elseif($this->is_admin == 2){
            return 'کاربر ادمین گروه نیست';
        }
        else{
            return 'این مکالمه گروهی نیست';
        }
    }
    public function getIsPinnedValueAttribute()
    {
        if($this->is_pinned == 2)
        {
            return 'مکالمه برای این کاربر پین نشده است';
        }
        else{
            return 'مکالمه برای این کاربر پین شده است';
        }
    }
    public function getIsMutedValueAttribute()
    {
        if($this->is_muted == 2)
        {
            return 'مکالمه برای این کاربر بیصدا نشده است';
        }
        else{
            return 'مکالمه برای این کاربر بیصدا شده است';
        }
    }
    public function getIsFavoriteValueAttribute()
    {
        if($this->is_favorite == 2)
        {
            return 'مکالمه برای این کاربر به لیست علاقمندی ها اضافه نشده است';
        }
        else{
            return 'مکالمه برای این کاربر به لیست علاقمندی ها اضافه شده است';
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getRoleAttribute()
    {
        $role = ConversationRole::where('conversation_id',$this->conversation_id)->where('user_id',$this->user_id)->select('role_type_id','assigned_by','user_id')
        ->with('roleType:id,name')->first();
        return $role;
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}
