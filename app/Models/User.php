<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles, Auditable;

    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'username',
        'password_hash',
        'full_name',
        'email',
        'default_branch_id',
        'preferred_language',
        'is_active',
        'last_login_at',
        'email_verified_at',
        'remember_token',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Override default auth password accessor for password_hash column.
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * Mutator & Accessor for password attribute (maps to password_hash).
     */
    public function setPasswordAttribute($value): void
    {
        $this->attributes['password_hash'] = Hash::isHashed($value) ? $value : Hash::make($value);
    }

    public function getPasswordAttribute(): ?string
    {
        return $this->password_hash;
    }

    /**
     * Accessor for Breeze Blade & route compatibility ($user->id).
     */
    public function getIdAttribute()
    {
        return $this->user_id;
    }

    /**
     * Accessor for Breeze Blade compatibility ($user->name).
     */
    public function getNameAttribute(): string
    {
        return $this->full_name;
    }

    public function defaultBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'default_branch_id', 'branch_id');
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'user_branch_access', 'user_id', 'branch_id');
    }

    public function openShift()
    {
        return $this->hasOne(CashierShift::class, 'user_id', 'user_id')->where('status', 'OPEN');
    }

    public function assignedCounters(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Counter::class, 'assigned_user_id', 'user_id');
    }
}
