<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasPermissions;

class Role extends Model
{
    use HasFactory, HasPermissions;
    protected $fillable = ['name','guard_name'];

    public function users()
    {
        return $this->morphedByMany(User::class, 'model', 'model_has_roles');
    }
    
    public function permissions()
    {
        return $this->belongsToMany(
            Config('permission.models.permission'),
            config('permission.table_names.role_has_permissions'),
        );
    }

}
