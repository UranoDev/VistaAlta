<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
     * El video que explica la Propuesta. Se lee de config igual que en nvavista
     * y se incrusta tal cual, así que tiene que ser una URL para incrustar.
     * Mientras esté vacío, la página muestra un marcador en lugar del
     * reproductor: el argumento se lee completo sin el video.
     */
    'asociacion_civil' => [
        'video_url' => env('ASOCIACION_CIVIL_VIDEO_URL'),
    ],

    'otp' => [
        // 'log' escribe el código en el log (default/dev), 'array' es para pruebas,
        // 'twilio' manda un SMS de verdad.
        'channel' => env('OTP_CHANNEL', 'log'),
        'pais_lada' => env('OTP_PAIS_LADA', '+52'),

        /*
        |----------------------------------------------------------------------
        | Límite de envío de OTP
        |----------------------------------------------------------------------
        |
        | El endpoint que dispara el OTP es público y Twilio cobra por mensaje
        | (ver docs/adr/0001), así que se cuenta por dos dimensiones a la vez:
        |
        | - Por teléfono: 3 envíos cada 10 minutos. Es el límite del que habla
        |   el ADR y acota lo que se puede gastar sobre un mismo número.
        | - Por IP: umbral más alto y ventana más larga. Existe solo para acotar
        |   la rotación de números desde un mismo origen, así que se dejó
        |   deliberadamente holgado: detrás de una IP puede haber un CGNAT de
        |   telefonía móvil o el internet compartido del fraccionamiento, y
        |   bloquear ahí castiga a colonos que no hicieron nada. 30 por hora da
        |   lugar a una ráfaga legítima —treinta personas validando tras el
        |   aviso de una Asamblea— y aun así topa el gasto de un solo origen.
        |
        | Los cuatro valores son ajustables por env a propósito: si una ráfaga
        | real llega al tope, el aviso queda en el log y subirlo es un cambio de
        | configuración, no un despliegue.
        |
        */
        'limite' => [
            'telefono' => [
                'intentos' => (int) env('OTP_LIMITE_TELEFONO_INTENTOS', 3),
                'ventana_minutos' => (int) env('OTP_LIMITE_TELEFONO_VENTANA_MINUTOS', 10),
            ],
            'ip' => [
                'intentos' => (int) env('OTP_LIMITE_IP_INTENTOS', 30),
                'ventana_minutos' => (int) env('OTP_LIMITE_IP_VENTANA_MINUTOS', 60),
            ],
        ],
    ],

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM'),
        'pais_lada' => env('TWILIO_PAIS_LADA', '+52'),
    ],

];
