<?php

declare(strict_types=1);

namespace App\Support\Otp;

/**
 * Entrega un código de un solo uso por WhatsApp.
 *
 * Gemela de `OtpSender` (SMS) y separada a propósito: WhatsApp no manda texto
 * libre a quien no ha escrito primero, manda una plantilla de categoría
 * Autenticación aprobada por Meta, y esa plantilla solo lleva el código. No puede
 * llevar un enlace, y por eso el enlace de confirmación sale únicamente por
 * correo.
 */
interface WhatsAppOtpSender
{
    /**
     * @param  string  $telefono  Diez dígitos, como se guardan.
     */
    public function send(string $telefono, string $codigo): void;
}
