<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    /* payment_method : cash, credit_card, etc. */
    protected $fillable = [
        'transaction_number',
        'cashier_id',
        'total_price',
        'total_item',
        'payment_method',
    ];

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function order_items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
