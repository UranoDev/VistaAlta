<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fraccionamiento extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'address',
        'contact',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function owners()
    {
        return $this->hasMany(Owner::class);
    }
}
