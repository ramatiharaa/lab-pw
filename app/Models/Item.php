<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'details',
        'min_bid',
        'available_untill',
        'image',
        'create_by_user_id',
    ];

    public function autoBids()
    {
        return $this->hasMany('App\Models\AutoBid');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_item', 'item_id', 'user_id');
    }
}
