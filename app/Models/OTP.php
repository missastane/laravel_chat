<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OTP extends Model
{
    use HasFactory;

    protected $table = 'o_t_p_s';
    protected $fillable = ['token', 'user_id', 'otp_code', 'login_id', 'type', 'used', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
