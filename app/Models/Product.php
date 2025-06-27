<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'printful_id',
        'printful_product_id',
        'name',
        'description',
        'type',
        'brand',
        'model',
        'category_id',
        'clothes_type_id',
        'sizes',
        'colors',
        'base_price',
        'image_url',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'sizes' => 'array',
        'colors' => 'array',
        'base_price' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function category()
    {
        return $this->belongsTo(ClothesCategory::class);
    }

    public function clothesType()
    {
        return $this->belongsTo(ClothesType::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByClothesType($query, $typeId)
    {
        return $query->where('clothes_type_id', $typeId);
    }

    public function getFormattedPriceAttribute()
    {
        return '$' . number_format($this->base_price, 2);
    }

    public function getPrimaryImageAttribute()
    {
        return $this->image_url ?? 'images/blank.png';
    }
}
