<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PinnedMessage extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'message_id', 'conversation_id', 'is_public'];

    protected $hidden = ['is_public','conversation_id','user_id','message_id','updated_at','deleted_at'];
    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
