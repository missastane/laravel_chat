<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversationRole extends Model
{
    use HasFactory;
    protected $table = 'conversation_role';
    protected $fillable = ['conversation_id','user_id','role_type_id','assigned_by','assigned_at','role_status'];

    protected $hidden = ['conversation_id','user_id','role_type_id','assigned_by','role_status'];

    protected $appends = ['assigner'];

    public function getAssignerAttribute()
    {
        $assigner = $this->assigner()->first(['id','username']);
        if($assigner){
        $assigner->makeHidden([
            'activation_value',
            'user_type_value',
            'is_discoverable_value'
        ]);
        return $assigner;
    }
      return null;  
    }
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assigner()
    {
        return $this->belongsTo(User::class,'assigned_by','id');
    }

    public function roleType()
    {
        return $this->belongsTo(ConversationRoleType::class, 'role_type_id');
    }
}
