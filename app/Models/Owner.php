<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Owner extends Model
{
    /** @use HasFactory<\Database\Factories\OwnerFactory> */
    use HasFactory;

    protected $fillable = [
        'fraccionamiento_id',
        'name',
        'email',
        'phone',
        'is_committee_member',
    ];

    protected $casts = [
        'is_committee_member' => 'boolean',
    ];

    public function fraccionamiento()
    {
        return $this->belongsTo(Fraccionamiento::class);
    }
}
