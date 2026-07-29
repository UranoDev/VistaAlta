<?php

declare(strict_types=1);

namespace App\Exceptions;

use LogicException;

class ComentarioPrivadoNoSeModera extends LogicException
{
    public function __construct()
    {
        parent::__construct('Un comentario privado no pasa por la Cola de moderación.');
    }
}
