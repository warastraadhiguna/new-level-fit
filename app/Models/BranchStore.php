<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BranchStore extends Model
{
    use HasFactory;

    public const DASHBOARD_FINANCE_ALL_ROLES = 'ALL';

    public const DASHBOARD_FINANCE_ROLE_OPTIONS = [
        'ADMIN' => 'Administrator',
        'CS' => 'Customer Service',
        'CSPOS' => 'Customer Service POS',
        'FC' => 'Fitness Consultant',
    ];

    protected $fillable = [
        'name',
        'slug',
        'address',
        'city',
        'phone',
        'email',
        'admin_logo',
        'is_payment_strict',
        'member_installment_enabled',
        'member_installment_reminder_days',
        'member_installment_grace_days',
        'member_installment_cancel_days',
        'member_discount_enabled',
        'trainer_discount_enabled',
        'pos_inventory_enabled',
        'dashboard_finance_visible_roles',
        'class_booking_advance_days',
        'type',
    ];

    protected $casts = [
        'is_payment_strict' => 'boolean',
        'member_installment_enabled' => 'boolean',
        'member_installment_reminder_days' => 'integer',
        'member_discount_enabled' => 'boolean',
        'trainer_discount_enabled' => 'boolean',
        'pos_inventory_enabled' => 'boolean',
        'dashboard_finance_visible_roles' => 'array',
        'class_booking_advance_days' => 'integer',
    ];

    public function canRoleViewDashboardFinance(?string $role): bool
    {
        if (strtoupper((string) $role) === 'OWNER') {
            return true;
        }

        $allowedRoles = $this->dashboard_finance_visible_roles;

        // Data lama yang belum diatur tetap mempertahankan perilaku sebelumnya.
        if (empty($allowedRoles)) {
            return true;
        }

        $allowedRoles = array_map('strtoupper', $allowedRoles);
        $role = strtoupper((string) $role);

        return in_array(self::DASHBOARD_FINANCE_ALL_ROLES, $allowedRoles, true)
            || in_array($role, $allowedRoles, true);
    }

    protected $appends = [
        'admin_logo_url',
        'admin_favicon_url',
    ];

    public function getAdminLogoUrlAttribute(): ?string
    {
        if ($this->admin_logo) {
            return $this->resolveMediaUrl($this->admin_logo);
        }

        return null;
    }

    public function getAdminFaviconUrlAttribute(): string
    {
        if ($this->admin_logo) {
            return $this->resolveMediaUrl($this->admin_logo);
        }

        return asset('admingym/images/gym/fav-icon.png');
    }

    private function resolveMediaUrl(string $path): string
    {
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        if (Str::startsWith($path, ['storage/', '/storage/'])) {
            return asset(ltrim($path, '/'));
        }

        return Storage::url($path);
    }
}
