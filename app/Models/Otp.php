<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['telefono', 'proposito', 'codigo_hash', 'intentos', 'expira_en', 'verificado_en'])]
class Otp extends Model
{
    protected $table = 'otps';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expira_en' => 'datetime',
            'verificado_en' => 'datetime',
        ];
    }
}
