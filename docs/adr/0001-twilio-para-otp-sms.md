# Twilio para el envío de OTP por SMS

El OTP que habilita comentar se manda por SMS con Twilio, no con AWS. La alternativa
obvia era AWS End User Messaging / SNS, porque el ecosistema ya tiene credenciales y
política listas (`nvavista/aws_policy_SNS.json`), pero esa política está redactada
sobre las acciones del **sandbox de SMS** (`CreateVerifiedDestinationNumber`,
`SendDestinationNumberVerificationCode`), que solo entrega mensajes a números
previamente verificados a mano. Este sitio abre el OTP a cualquier celular del
público, así que el sandbox lo rompería desde el primer día.

Se reutiliza el `TwilioOtpSender` de nvavista: son ~30 líneas sobre el `Http` de
Laravel, sin SDK de Twilio como dependencia, detrás de la interfaz `OtpSender`. La
decisión es barata de revertir (basta cambiar `OTP_CHANNEL`), pero se registra para
que nadie la "corrija" de vuelta a AWS sin antes sacar la cuenta del sandbox.

## Consecuencias

Twilio se cobra por mensaje y el endpoint de OTP es público, así que el gasto es
drenable por abuso (toll fraud). El Padrón de referencia **no** lo contiene: por
diseño no bloquea a los teléfonos que no están en la lista.

El límite propio queda en **3 intentos por ventana de 10 minutos**. Falta definir
sobre qué se cuenta —por teléfono, por IP o por ambos—: por teléfono solo, un abusador
rota números y sigue gastando; por IP sola, una familia detrás del mismo NAT se
bloquea entre sí.

## El destino lleva el `1` de móvil (URVA-58)

El número que se le manda a Twilio para un celular mexicano va como **`+521` + diez
dígitos**, no `+52` + diez dígitos. Es la lada que está en `TWILIO_PAIS_LADA` y en el
default de `config/services.php`, y **no se corrige de vuelta a `+52`**.

Sin el `1`, Twilio acepta el mensaje —HTTP 201, `status=queued`, la app lo da por
enviado— y el carrier mexicano lo descarta segundos después: el mensaje queda en
`status=undelivered` con `error_code=30008` y el código nunca llega al teléfono. En la
consola de Twilio el engaño se completa, porque normaliza el `to` de vuelta a `+52…` en
su registro: los envíos que fallan se ven idénticos a los que entregan, y la diferencia
solo existe en lo que se manda. Comprobado el 2026-08-02 contra la cuenta real:

| Remitente | Destino enviado | Resultado |
| --- | --- | --- |
| `+525629153623` (MX) | `+525531269267` | `undelivered` — 30008 |
| `+525629153623` (MX) | `+5215531269267` | **`delivered`** |
| `+12293049244` (EE.UU.) | `+525531269267` | **`delivered`** |

El `1` es de **móviles**. El sitio solo pide celular, así que se aplica sin condición y
no se detecta el tipo de línea; si algún día entra un fijo, `+521` + fijo no es válido
y esto hay que revisarlo.

La lada tiene **una sola fuente**: `services.twilio.pais_lada`. Convivía con un
`services.otp.pais_lada` (`OTP_PAIS_LADA`) que nadie leía —el sender siempre usó el de
Twilio— y que solo servía para que alguien creyera haber arreglado el formato editando
el valor equivocado. Se retiró.

## El cuerpo nombra a Vista Alta, y mide 70 (URVA-59)

El remitente **no lo controlamos**: los carriers mexicanos reescriben el `From`.
Comprobado el 2026-08-02 sobre envíos reales — el número de EE.UU. de la cuenta llega
mostrando `22622` y el mexicano llega como `Sms Twilio`. No es configuración nuestra:
la cuenta no tiene Alphanumeric Sender IDs ni short codes, y los envíos salen sin
Messaging Service. Arreglarlo exige registrar un sender ID o un short code mexicano
ante los carriers, que es trámite, no despliegue.

Como el remitente no es negociable, **el cuerpo es lo único que da contexto**, y por eso
abre con el nombre del fraccionamiento: `Vista Alta: tu código de verificación es
123456`. Un SMS anónimo con seis dígitos, a un colono que no está esperando nada, se lee
igual que el spam de siempre, y cada validación que no se completa es un comentario que
no entra.

El presupuesto del cuerpo es de **70 caracteres, no de 160**. `código` y `verificación`
llevan `ó`, que no está en el alfabeto GSM-7; con una sola de esas vocales Twilio
codifica el mensaje entero en UCS-2, donde un segmento son 70. Rebasarlo parte el SMS en
dos y duplica el costo de **cada** OTP que manda el sitio. El texto actual mide 47, y el
límite vive en `TwilioOtpSender::LIMITE_DE_UN_SEGMENTO` con un test que lo mira. Quien
lo reescriba mide contra eso — o le quita los acentos, y entonces el mensaje vuelve a
GSM-7 y el presupuesto sube a 160.

El nombre va escrito en el código y no sale de `app.name`: el presupuesto solo se puede
garantizar si el largo es fijo.

## Sobre qué se cuenta (resuelto en URVA-5)

Se cuenta por **las dos dimensiones a la vez, con umbrales distintos**:

- **Teléfono: 3 envíos por 10 minutos**, el límite que fija este ADR. Acota lo que se
  puede gastar sobre un mismo número.
- **IP: 30 envíos por hora.** Es el tope contra la rotación de números, y va holgado a
  propósito. Detrás de una IP puede haber un CGNAT de telefonía móvil o el internet
  compartido del fraccionamiento, así que un umbral apretado castigaría a colonos que
  no hicieron nada — y el costo de ese falso bloqueo es que se quedan sin poder
  comentar, sin saber por qué. Treinta por hora da lugar a la ráfaga legítima que cabe
  esperar tras el aviso de una Asamblea y aun así topa el gasto de un solo origen en
  ~30 SMS/hora en vez de dejarlo abierto.

Las dos dimensiones son ajustables por env (`OTP_LIMITE_*`) porque el número correcto
para la IP no se sabe hasta ver tráfico real: cuando una ráfaga legítima alcance el
tope queda un `warning` en el log, y subirlo es configuración, no despliegue.

El rechazo es **un solo mensaje**, siempre el mismo, que solo dice cuánto falta para
reintentar. No distingue cuál de los dos topes se alcanzó ni dice nada del número
escrito: quien lo reciba no puede deducir si ese teléfono existe o está en algún
padrón. El tope se cobra antes de mandar nada, así que un intento rechazado no gasta
SMS ni invalida el código que ese teléfono ya tuviera vigente.
