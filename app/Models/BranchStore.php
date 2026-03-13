<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BranchStore extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'address',
        'city',
        'phone',
        'email',
        'admin_logo',
    ];

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
