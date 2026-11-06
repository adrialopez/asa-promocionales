/* ================================================================
   Configuración de la maqueta. Todo lo que hay que rellenar está aquí.
   Mientras un valor sea el de ejemplo, la integración queda inactiva.
   ================================================================ */
window.ASA = {
  // WhatsApp: número en formato internacional, solo dígitos (México: 52 + 10 dígitos)
  whatsapp: '520000000000',

  // Google Ads: ID de la etiqueta (AW-XXXXXXXXXX) y etiquetas de conversión
  gadsId: 'AW-XXXXXXXXXX',
  gadsConversions: {
    // evento: 'AW-XXXXXXXXXX/etiqueta'
    generate_lead: 'AW-XXXXXXXXXX/AAAAAAAAAAA',   // formulario de contacto enviado
    whatsapp_click: 'AW-XXXXXXXXXX/BBBBBBBBBBB',  // clic en cualquier botón de WhatsApp
    catalog_download: 'AW-XXXXXXXXXX/CCCCCCCCCCC' // descarga o apertura del catálogo
  },

  // Mailchimp: URL de acción del formulario incrustado (Audience > Signup forms > Embedded forms)
  // Ejemplo: https://makiba.us21.list-manage.com/subscribe/post?u=XXXX&id=YYYY&f_id=ZZZZ
  mailchimpUrl: '',

  // Catálogo descargable: URL del PDF (en WordPress, el archivo de la Mediateca)
  catalogPdf: '',

  // Envío del formulario de contacto (en WordPress lo sustituye Contact Form 7 / WPForms)
  contactEndpoint: '',

  // Banners del hero: se rotan solos. Se editan en el HTML (.slide).
  heroAutoplayMs: 6500
};
