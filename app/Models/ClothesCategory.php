<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClothesCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'external_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function clothesTypes()
    {
        return $this->hasMany(ClothesType::class, 'category_id');
    }

    /**
     * Scope to find category by external ID
     */
    public function scopeByExternalId($query, $externalId)
    {
        return $query->where('external_id', $externalId);
    }

    /**
     * Find category by external ID
     */
    public static function findByExternalId($externalId)
    {
        return static::byExternalId($externalId)->first();
    }
}
