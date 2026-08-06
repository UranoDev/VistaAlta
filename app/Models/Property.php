<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'fraccionamiento_id',
        'owner_id',
        'section',
        'unit',
    ];

    public function fraccionamiento()
    {
        return $this->belongsTo(Fraccionamiento::class);
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }
}
