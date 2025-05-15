<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use function PHPUnit\Framework\returnArgument;
/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="email", type="string", example="missastaneh@yahoo.com"),
 *     @OA\Property(property="mobile", type="string", example="09125478963"),
 *     @OA\Property(property="national_code", type="string", example="2732548965"),
 *     @OA\Property(property="first_name", type="string", example="ایمان"),
 *     @OA\Property(property="last_name", type="string", example="مدائنی"),
 *     @OA\Property(property="slug", type="string", maxLength=255, example="example-slug"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", description="email verify datetime", example="2025-02-22T10:00:00Z"),
 *     @OA\Property(property="mobile_verified_at", type="string", format="date-time", description="mobile verify datetime", example="2025-02-22T10:00:00Z"),
 *     @OA\Property(property="profile_photo_path", type="string", format="uri", example="\path\image.jpg"),
 *     @OA\Property(property="activation_date", type="string", format="date-time", description="activation datetime", example="2025-02-22T10:00:00Z"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="creation datetime", example="2025-02-22T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="update datetime", example="2025-02-22T10:00:00Z"),
 *     @OA\Property(property="deleted_at", type="string", format="datetime",description="delete datetime", example="2025-02-22T14:30:00Z"),
 *     @OA\Property(property="google_id", type="string", example="27325489656859526545"),
 *     @OA\Property(property="activation_value", type="string", description="Activation Value: 'active' if 1, 'inactive' if 2", example="فعال"),
 *     @OA\Property(property="user_type_value", type="string", description="User Type Value: 'admin' if 1, 'user' if 2", example="ادمین"),
 *     @OA\Property(property="is_discoverable_value", type="string", description="Is Discoverable Value: 'discoverable' if 1, 'hidden' if 2", example="کاربر قابل جستجوست"),
 * )
 */
class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, Sluggable, HasRoles, HasPermissions;

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => ['first_name', 'last_name']
            ]
        ];
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'first_name',
        'last_name',
        'national_code',
        'mobile',
        'slug',
        'profile_photo_path',
        'activation',
        'activation_date',
        'user_type',
        'is_discoverable',
        'is_blocked'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'user_type',
        'activation',
        'is_discoverable',
        'is_blocked'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'mobile_verified_at' => 'datetime',
            'activation_date' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected $appends = ['activation_value', 'user_type_value', 'is_discoverable_value','is_blocked_value'];

    public function getActivationValueAttribute()
    {
        if ($this->activation == 1) {
            return 'فعال';
        } else {
            return 'غیرفعال';
        }
    }
    public function getIsBlockedValueAttribute()
    {
        if ($this->is_blocked == 1) {
            return 'کاربر مسدود شده';
        } else {
            return 'کاربر آزاد';
        }
    }
    public function getUserTypeValueAttribute()
    {
        if ($this->user_type == 1) {
            return 'ادمین';
        } else {
            return 'کاربر عادی';
        }
    }
    public function getIsDiscoverableValueAttribute()
    {
        if ($this->is_discoverable == 1) {
            return 'کاربر قابل جستجوست';
        } else {
            return 'کاربر مخفی یا غیرقابل جستجوست';
        }
    }
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function groupConversations()
    {
        return $this->belongsToMany(GroupConversation::class, 'conversation_user')
            ->withPivot('is_admin')
            ->withTimestamps();
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user', 'user_id', 'conversation_id');
    }

    public function media()
    {
        return $this->hasMany(Media::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }
    public function blocks()
    {
        return $this->hasMany(Block::class, 'blocker_id');
    }

    public function requests()
    {
        return $this->hasMany(JoinRequest::class);
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class, 'user_id');
    }

    // who does save this user in his/her contacts list?
    public function inContactsOf()
    {
        return $this->hasMany(Contact::class, 'contact_user_id');
    }
    public function favoriteMessages()
    {
        return $this->hasMany(FavoriteMessage::class);
    }

    // public function roles()
    // {
    //     return $this->belongsToMany(Role::class);
    // }

    // public function permissions()
    // {
    //     return $this->belongsToMany(Permission::class);
    // }
}
