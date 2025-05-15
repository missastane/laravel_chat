<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * @OA\Schema(
 *     schema="Contact",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="contact_name", type="string", example="محمد اردوخانی"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="creation datetime", example="2025-02-22T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="update datetime", example="2025-02-22T10:00:00Z"),
 *     @OA\Property(property="deleted_at", type="string", format="datetime",description="delete datetime", example="2025-02-22T14:30:00Z"),
 *     @OA\Property(property="owner", type="object",
 *       @OA\Property(property="id", type="integer", example=2),
 *       @OA\Property(property="username", type="string", example="ایمان"),
 *       @OA\Property(property="first_name", type="string", example="ایمان"),
 *       @OA\Property(property="last_name", type="string", example="مدائنی"),
 * ),
 *     @OA\Property(property="contactUser", type="object",
 *       @OA\Property(property="id", type="integer", example=2),
 *       @OA\Property(property="username", type="string", example="محمد001"),
 *       @OA\Property(property="first_name", type="string", example="محمد"),
 *       @OA\Property(property="last_name", type="string", example="اردوخانی"),
 * ),
 * )
 */
class Contact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'contact_user_id',
        'contact_name',
    ];

    protected $hidden = ['user_id','contact_user_id'];
    protected $appends = ['owner','contactUser'];
    
    public function getOwnerAttribute()
    {
        $owner = $this->owner()->first(['id','username','first_name','last_name']);
        $owner->makeHidden(['activation_value','user_type_value','is_discoverable_value']);
        return $owner;
    }
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getContactUserAttribute()
    {
        $contact = $this->contactUser()->first(['id','username','first_name','last_name']);
        $contact->makeHidden(['activation_value','user_type_value','is_discoverable_value']);
        return $contact;
    }
    public function contactUser()
    {
        return $this->belongsTo(User::class, 'contact_user_id');
    }
}
