<?php

declare(strict_types=1);

namespace App\Exceptions;

use LogicException;

class VisibilidadEsDefinitiva extends LogicException
{
    public function __construct()
    {
        parent::__construct('La visibilidad la elige el autor y no se puede cambiar después.');
    }
}
