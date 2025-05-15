<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Block extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['blocker_id', 'blockable_id','blockable_type'];
    public function blocker()
    {
        return $this->belongsTo(User::class,'blocker_id');
    }

    public function blockable()
    {
        return $this->morphTo();
    }

    // یه متد برای چک کردن بلاک بودن کاربر
    public function scopeUserBlocked($query, $userId)
    {
        return $query->where('blockable_type', User::class)
                     ->where('blockable_id', $userId);
    }

    // یه متد برای چک کردن بلاک بودن گروه
    public function scopeGroupBlocked($query, $groupId)
    {
        return $query->where('blockable_type', Conversation::class)
                     ->where('blockable_id', $groupId);
    }
}
