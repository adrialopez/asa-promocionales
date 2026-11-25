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

## Sitio WordPress (prueba en asa.makiba.com.mx)

La maqueta HTML de arriba quedó sustituida en el servidor por WordPress (copia en `~/backups/asa-makiba/`).

- `wp/plugins/asa-core`: sincronización con Promo Opción, lista de cotización, leads, atribución, reporte, SEO.
- `wp/themes/asa-theme`: tema propio.
- `wp/setup/seed.php`: contenido inicial (`wp eval-file seed.php`); no sobrescribe páginas existentes.
- `tools/promo_api.py`: consultas locales a la API (credenciales en `.env`, fuera de git).

Órdenes: `wp asa sync [catalog|stock]` y `wp asa import catalogo.json existencias.json` (copia local).
Credenciales en el servidor: constantes `ASA_PROMO_EMAIL` y `ASA_PROMO_PASSWORD` (archivo fuera de la web).

### API
- 3 inicios de sesión por email cada 24 h; el plugin reutiliza el token y hace como máximo 2.
- El 25-11-2026 empezó a responder con casi todos los campos vacíos. El plugin rechaza esas respuestas
  y conserva el catálogo.

### Antes de producción
Activar la indexación (y quitar la cabecera `X-Robots-Tag`), WhatsApp y correo reales, aviso de privacidad
validado, contenido confirmado por ASA, redirecciones desde el sitio actual y SMTP para los avisos.

La web de prueba está protegida con clave HTTP y con la sincronización pausada (eventos de WP-Cron borrados y
línea del crontab marcada `#PAUSADO`). Para reanudar: quitar la marca y desactivar y activar `asa-core`.
La tarea del crontab no necesita la clave porque usa WP-CLI.
