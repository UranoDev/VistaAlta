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
