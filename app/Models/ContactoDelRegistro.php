<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Otra persona a la que se le puede avisar por el mismo lote: la pareja, un
 * familiar, quien lo administra. Es distinta del contacto de emergencia, que va
 * en el propio registro y solo sirve para eso.
 */
#[Fillable(['nombre', 'telefono', 'correo'])]
class ContactoDelRegistro extends Model
{
    protected $table = 'contactos_del_registro';

    /**
     * @return BelongsTo<RegistroDePropietario, $this>
     */
    public function registro(): BelongsTo
    {
        return $this->belongsTo(RegistroDePropietario::class, 'registro_de_propietario_id');
    }
}
