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
        'admin_owner_id',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function owners()
    {
        return $this->hasMany(Owner::class);
    }

    public function properties()
    {
        return $this->hasMany(Property::class);
    }

    public function monthlyFees()
    {
        return $this->hasMany(MonthlyFee::class);
    }

    /** Cuota con start_date más reciente que ya entró en vigor. */
    public function currentFee(): ?MonthlyFee
    {
        return $this->monthlyFees()
            ->where('start_date', '<=', today())
            ->orderByDesc('start_date')
            ->first();
    }

    /** Cuota programada a futuro (start_date > hoy), si existe. */
    public function scheduledFee(): ?MonthlyFee
    {
        return $this->monthlyFees()
            ->where('start_date', '>', today())
            ->orderBy('start_date')
            ->first();
    }

    public function administrator()
    {
        return $this->belongsTo(Owner::class, 'admin_owner_id');
    }
}
