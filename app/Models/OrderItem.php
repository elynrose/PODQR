<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'design_id',
        'product_id',
        'printful_variant_id',
        'size',
        'color',
        'quantity',
        'unit_price',
        'total_price',
        'design_data',
        'printful_item_id',
    ];

    protected $casts = [
        'design_data' => 'array',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function design()
    {
        return $this->belongsTo(Design::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
