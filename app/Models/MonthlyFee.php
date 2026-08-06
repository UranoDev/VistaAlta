<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class MonthlyFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'fraccionamiento_id',
        'amount',
        'start_date',
        'surcharge_type',
        'surcharge_value',
    ];

    protected $casts = [
        'start_date' => 'date',
        'amount' => 'decimal:2',
        'surcharge_value' => 'decimal:2',
    ];

    public function fraccionamiento()
    {
        return $this->belongsTo(Fraccionamiento::class);
    }

    /** La cuota ya entró en vigor (start_date <= hoy). */
    public function isActive(): bool
    {
        return $this->start_date->lte(Carbon::today());
    }

    /** La cuota todavía no entra en vigor (start_date > hoy). */
    public function isFuture(): bool
    {
        return $this->start_date->gt(Carbon::today());
    }

    /**
     * Monto total adeudado cuando hay atraso.
     * El recargo se aplica una sola vez, sin importar cuántos meses tarde el propietario.
     */
    public function amountWithSurcharge(): float
    {
        if (!$this->surcharge_type || !$this->surcharge_value) {
            return (float) $this->amount;
        }

        if ($this->surcharge_type === 'percentage') {
            return (float) $this->amount + ((float) $this->amount * (float) $this->surcharge_value / 100);
        }

        return (float) $this->amount + (float) $this->surcharge_value;
    }
}
