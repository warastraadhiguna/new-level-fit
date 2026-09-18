<?php

namespace App\Models\Member;

use App\Models\BranchStore;
use App\Models\User;
use App\Traits\HasFormatRupiah;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class MemberPackage extends Model
{
    use HasFormatRupiah;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'branch_store_id',
        'package_name',
        'days',
        'package_type_id',
        'package_category_id',
        'package_price',
        'admin_price',
        'description',
        'user_id',
        'is_all_club',
        'is_installment_plan',
        'installment_monthly_amount',
    ];

    protected $hidden = [];
    protected $casts = ['is_installment_plan' => 'boolean'];
    // public $timestamps = true;

    public function memberPackageType()
    {
        return $this->belongsTo(MemberPackageType::class, 'package_type_id', 'id');
    }

    public function memberPackageCategories()
    {
        return $this->belongsTo(MemberPackageCategory::class, 'package_category_id', 'id');
    }

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')
            ->withTrashed()
            ->withDefault([
                'full_name' => '-',
            ]);
    }

    public function branchStore()
    {
        return $this->belongsTo(BranchStore::class)
            ->withDefault([
                'name' => '-',
            ]);
    }

    /**
     * Paket gratis operasional hanya boleh dilihat/diberikan oleh user selain CS.
     */
    public function scopeVisibleToUser(Builder $query, ?User $user): Builder
    {
        if ($user && strtoupper((string) $user->role) === 'CS') {
            $query->where(function (Builder $query) {
                $query->where('package_price', '!=', 0)
                    ->orWhere('admin_price', '!=', 0);
            });
        }

        return $query;
    }

    public function isFreePackage(): bool
    {
        return (float) $this->package_price === 0.0
            && (float) $this->admin_price === 0.0;
    }

    public function ensureAssignableBy(?User $user): self
    {
        if ($user && strtoupper((string) $user->role) === 'CS' && $this->isFreePackage()) {
            throw ValidationException::withMessages([
                'member_package_id' => 'Customer Service tidak diperbolehkan memberikan paket membership dengan harga paket dan harga admin Rp. 0.',
            ]);
        }

        return $this;
    }
}
