<?php

namespace App\Domains\Auth\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Database\Factories\UserFactory as DomainUserFactory;
use App\Domains\Auth\PublicApi\Roles;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\App\Domains\Auth\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'password',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Will load the roles eagerly
     */
    protected $with = ['roles'];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The roles that belong to the user.
     */
    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Check if the user has admin role.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if the user is on probation
     *
     * @return bool
     */
    public function isOnProbation(): bool
    {
        return $this->hasRole(Roles::USER);
    }

    /**
     * Check if the user is confirmed
     *
     * @return bool
     */
    public function isConfirmed(): bool
    {
        return $this->hasRole(Roles::USER_CONFIRMED);
    }

    /**
     * Check if the user has a specific role.
     *
     * @param  string|array  $roles
     * @return bool
     */
    public function hasRole($roles): bool
    {
        if (is_string($roles)) {
            return $this->roles->contains('slug', $roles);
        }

        if (is_array($roles)) {
            return $this->roles->whereIn('slug', $roles)->isNotEmpty();
        }

        return false;
    }

    /**
     * Assign a role to the user.
     *
     * @param  string  $role
     * @return void
     */
    public function assignRole(string $role): void
    {
        $roleModel = Role::where('slug', $role)->first();
        if (!$roleModel) {
            $roleModel = Role::create([
                'name' => $role,
                'slug' => $role,
            ]);
        }
        $this->roles()->syncWithoutDetaching([$roleModel->id]);
    }

    /**
     * Remove a role from the user.
     *
     * @param  string  $role
     * @return void
     */
    public function removeRole(string $role): void
    {
        $roleModel = Role::where('slug', $role)->first();
        if ($roleModel) {
            $this->roles()->detach($roleModel->id);
        }
    }

    /**
     * Check if the user is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Activate the user.
     *
     * @return void
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the user.
     *
     * @return void
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Specify the factory for this model.
     */
    protected static function newFactory(): DomainUserFactory
    {
        return DomainUserFactory::new();
    }
}
