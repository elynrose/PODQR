<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClothesType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category_id',
        'front_image',
        'back_image',
        'colors',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'colors' => 'array',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(ClothesCategory::class, 'category_id');
    }

    public function getFrontImageUrlAttribute()
    {
        if ($this->front_image) {
            return Storage::url($this->front_image);
        }
        
        // Return default placeholder based on category
        $defaultImages = [
            'T-Shirts' => asset('images/shirts/men1_blue_front.png'),
            'Hoodies' => asset('images/shirts/men1_blue_front.png'),
            'Tank Tops' => asset('images/shirts/women_black_front.png'),
            'Long Sleeve Shirts' => asset('images/shirts/men1_blue_front.png'),
            'Polo Shirts' => asset('images/shirts/men1_blue_front.png'),
            'Sweatshirts' => asset('images/shirts/men1_blue_front.png'),
            'V-Neck Shirts' => asset('images/shirts/women_black_front.png'),
            'Kids Clothing' => asset('images/shirts/men1_blue_front.png'),
        ];
        
        $categoryName = $this->category->name ?? 'T-Shirts';
        return $defaultImages[$categoryName] ?? asset('images/shirts/men1_blue_front.png');
    }

    public function getBackImageUrlAttribute()
    {
        if ($this->back_image) {
            return Storage::url($this->back_image);
        }
        
        // Return default back image
        return asset('images/shirts/men1_blue_back.png');
    }
}
