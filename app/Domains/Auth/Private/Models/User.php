<?php

namespace App\Domains\Auth\Private\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Domains\Auth\Private\Models\Role;
use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'password',
        'is_active',
        'terms_accepted_at',
        'is_under_15',
        'parental_authorization_verified_at',
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
            'terms_accepted_at' => 'datetime',
            'is_under_15' => 'boolean',
            'parental_authorization_verified_at' => 'datetime',
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
     * Check if the user has accepted the terms and conditions.
     *
     * @return bool
     */
    public function hasAcceptedTerms(): bool
    {
        return $this->terms_accepted_at !== null;
    }

    /**
     * Mark the terms as accepted.
     *
     * @return void
     */
    public function acceptTerms(): void
    {
        $this->update(['terms_accepted_at' => now()]);
    }

    /**
     * Check if parental authorization is required.
     *
     * @return bool
     */
    public function needsParentalAuthorization(): bool
    {
        return $this->is_under_15 && $this->parental_authorization_verified_at === null;
    }

    /**
     * Mark parental authorization as verified.
     *
     * @return void
     */
    public function verifyParentalAuthorization(): void
    {
        $this->update(['parental_authorization_verified_at' => now()]);
    }

    /**
     * Check if the user is fully compliant (terms accepted and parental auth if needed).
     *
     * @return bool
     */
    public function isCompliant(): bool
    {
        return $this->hasAcceptedTerms() && !$this->needsParentalAuthorization();
    }
}
