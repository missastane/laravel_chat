<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MessageUserStatus extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'message_user_status';
    protected $fillable = ['message_id', 'user_id', 'delivered_at', 'read_at', 'status'];
    protected $hidden = ['status','user_id','message_id'];
    protected $appends = ['status_value'];
    public function getStatusValueAttribute()
    {
        switch ($this->status) {
            case 0:
                $result = 'ارسال شده';
                break;
            case 1:
                $result = 'تحویل داده شده';
                break;
            case 2:
                $result = 'خوانده شده';
                break;
            default:
                $result = 'ارسال شده';
                break;
        }
        return $result;
    }
    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
