# ASA Promocionales - maqueta HTML

Réplica modernizada de https://asapromocionales.com/ (hecha con Durable). Solo front: HTML + CSS + JS, sin build.

Todo lo que hay que rellenar está en `js/config.js`:
- `whatsapp`: número en formato internacional (52 + 10 dígitos). Alimenta todos los botones `data-wa`.
- `gadsId` y `gadsConversions`: etiqueta de Google Ads y conversiones (formulario, WhatsApp, catálogo).
- `mailchimpUrl`: URL de acción del formulario incrustado de Mailchimp.
- `catalogPdf`: URL del PDF del catálogo.
- `contactEndpoint`: endpoint del formulario (en WP lo sustituye CF7/WPForms).

Mientras un valor sea el de ejemplo, esa integración queda inactiva y se simula.

## Paso a WordPress
- Banner: 3 `.slide` (uno con imagen completa, dos maquetados). En WP, un slider (Smart Slider / Slider Revolution) o un CPT "Promoción" con imagen, título, enlace y fechas.
- Newsletter: plugin Mailchimp for WP o el formulario incrustado.
- Contacto: Contact Form 7 / WPForms con los mismos campos; reCAPTCHA v3.
- WhatsApp: botón flotante (CTA Chat / Click to Chat) o este mismo snippet.
- Google Ads: Site Kit o GTM4WP; reutilizar los eventos `generate_lead`, `whatsapp_click`, `catalog_download`.
- Ideal como tema propio (como los otros del servidor) para no depender de un constructor.

Vista previa: `python3 -m http.server` en esta carpeta.
