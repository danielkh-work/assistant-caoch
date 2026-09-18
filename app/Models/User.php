<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Permission;
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes {
        HasRoles::hasPermissionTo as roleHasPermissionTo;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'head_coach_id',
        'league_id',
        'team_id',
        'sport_id',
        'is_subscribe',
        'role',
        'status',
        'subscription_id',
        'image','is_subscribed',
        'code',
        'encrypted_password',
        'session_id',
        'is_loggin',
    ];
 
      
    
    public function assistants()
    {
        return $this->hasMany(User::class, 'head_coach_id')->where('role', 'assistant_coach');
      
    }

    public function league()
    {
        return $this->belongsTo(League::class);
    }

    public function leagueTeam()
    {
        return $this->belongsTo(LeagueTeam::class, 'team_id');
    }
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'encrypted_password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_loggin' => 'boolean',
    ];

       public static function generateUniqueCode()
    {
        do {
            $code = rand(1000, 9999); // 4-digit number
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /**
     * Permissions explicitly revoked for this one user, overriding whatever
     * their role(s) would otherwise grant. Spatie has no native "deny" concept -
     * this table is how a per-user decrease from the role default is represented.
     */
    public function deniedPermissions()
    {
        return $this->belongsToMany(Permission::class, 'user_denied_permissions')->withTimestamps();
    }

    /**
     * Overrides Spatie's HasPermissions::hasPermissionTo() so an explicit deny
     * always wins, even if a role would otherwise grant the same permission.
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        $name = $permission instanceof Permission ? $permission->name : $permission;

        if (is_string($name) && $this->deniedPermissions()->where('name', $name)->exists()) {
            return false;
        }

        return $this->roleHasPermissionTo($permission, $guardName);
    }

    /**
     * Single source of truth for "what can this user actually do": role-granted
     * permissions plus direct grants, minus explicit per-user denials.
     */
    public function effectivePermissionNames()
    {
        $names = $this->getAllPermissions()->pluck('name')
            ->diff($this->deniedPermissions()->pluck('name'))
            ->values();

        // getAllPermissions() lazily loads Spatie's own `permissions` relation
        // (direct grants only, usually empty for role-based users) onto this
        // model. Callers commonly do $user['permissions'] = effectivePermissionNames();
        // then serialize $user - Eloquent's toArray() merges relations AFTER
        // attributes, so that now-cached empty relation silently overwrites
        // the attribute we just set. Clear it so serialization isn't shadowed.
        $this->unsetRelation('permissions');

        return $names;
    }
}
