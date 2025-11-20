# Ad Platform - Sistema de Gestión de Anuncios de Afiliados

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.8+-green.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)

**Sistema completo de gestión de anuncios de afiliados con integración AliExpress, tracking avanzado, matching contextual y SDK para sitios externos.**

---

## 🚀 Características Principales

### 📊 Gestión Completa de Anuncios
- ✅ Custom Post Types para Anuncios, Campañas y Zonas
- ✅ Múltiples tipos de anuncios: Banner, Texto, HTML, Native
- ✅ Sistema de pesos/prioridad (1-10)
- ✅ Programación de campañas (fechas, horarios, límites)
- ✅ Targeting por país, dispositivo y keywords
- ✅ **Filtrado de Contenido Adulto** (para sitios como xlatinas.com)
- ✅ A/B Testing automático

### 🛒 Integración con AliExpress
- ✅ **Sistema de Pesos Manual + Automático**
  - Productos manuales tienen prioridad
  - Fallback automático cuando no hay productos manuales
- ✅ Modos: Automático, Semi-automático, Manual
- ✅ Filtros avanzados (comisión, rating, ventas, precio)
- ✅ Algoritmo de score balanceado
- ✅ Banco de productos con aprobación/rechazo
- ✅ Actualización automática de precios (cron)

### 📈 Tracking y Analytics
- ✅ Registro de impresiones y clicks
- ✅ CTR (Click-Through Rate)
- ✅ Conversiones (tracking futuro)
- ✅ Geolocalización de usuarios
- ✅ Detección de dispositivo
- ✅ Dashboard de estadísticas
- ✅ Cumplimiento GDPR (anonimización de IP)

### 🎯 Matching Contextual
- ✅ Extracción automática de keywords del contenido
- ✅ Score de relevancia
- ✅ Análisis de título, tags, categorías y contenido
- ✅ Filtrado de stopwords en español e inglés

### 🔗 Sistema de Enlaces Cortos
- ✅ Redirección `/go/{id}`
- ✅ Slug personalizable
- ✅ Tracking de clicks antes de redirigir
- ✅ Delay opcional con página intermedia
- ✅ Tipos de redirección: 301, 302, 307

### 🌐 SDK JavaScript
- ✅ Script ligero para sitios externos
- ✅ Integración en WordPress y sitios estáticos
- ✅ Detección automática de keywords de página
- ✅ Renderizado responsive
- ✅ Tracking automático

### 📄 Landing Pages de Productos
- ✅ Custom Post Type para landing pages de afiliados
- ✅ Generación automática desde AliExpress
- ✅ Templates profesionales y responsivos
- ✅ Galería de imágenes interactiva
- ✅ Integración con Facebook Pixel
- ✅ SEO optimizado
- ✅ Taxonomía por plataforma (AliExpress, Hotmart, Fiverr, etc.)

### 📱 Facebook Pixel & Remarketing
- ✅ Integración completa con Facebook Pixel
- ✅ Tracking de PageView, ViewContent, AddToCart
- ✅ Advanced Matching para mejor precisión
- ✅ **Carrusel de productos vistos** (remarketing)
- ✅ Widget y shortcodes para productos recientes
- ✅ Filtrado por plataforma

### 💾 Backup y Restauración
- ✅ Exportar configuraciones y contenido a JSON
- ✅ Importar desde backup
- ✅ Opciones selectivas (solo settings, solo contenido)
- ✅ Opción de sobrescribir o mantener existentes

### 🔒 Seguridad
- ✅ Archivos `.htaccess` en todos los directorios
- ✅ Sanitización y validación de datos
- ✅ Nonces en formularios
- ✅ Protección contra XSS, SQL Injection, CSRF
- ✅ Cabeceras de seguridad HTTP

---

## 📦 Instalación

### Requisitos
- WordPress 5.8 o superior
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web con mod_rewrite habilitado

### Pasos

1. **Clonar el repositorio**
```bash
cd wp-content/plugins/
git clone https://github.com/derkeshtai/ad-Platform.git
```

2. **Activar el plugin**
   - Ir a WordPress Admin → Plugins
   - Buscar "Ad Platform"
   - Click en "Activar"

3. **Configurar permalinks**
   - Ir a Ajustes → Enlaces Permanentes
   - Click en "Guardar cambios" (para flush rewrite rules)

4. **Configurar el plugin**
   - Ir a Ad Platform → Configuración
   - Completar los datos necesarios

---

## ⚙️ Configuración

### 1. Configuración General

```
Ad Platform → Configuración → General
```

- **Habilitar Tracking**: Registrar impresiones y clicks
- **Habilitar Caché**: Cachear anuncios para mejor rendimiento
- **Anuncio de Fallback**: Qué mostrar cuando no hay anuncios (AliExpress/Nada)

### 2. Integración con AliExpress

```
Ad Platform → Configuración → AliExpress
```

#### Obtener Credenciales de AliExpress

1. Registrarse en [AliExpress Affiliate Program](https://portals.aliexpress.com/)
2. Ir a Tools → API
3. Crear una aplicación para obtener:
   - **App Key**
   - **App Secret**
   - **Tracking ID**

#### Configurar Filtros

- **Comisión mínima**: 5% (recomendado)
- **Rating mínimo**: 4.0 ⭐
- **Órdenes mínimas**: 100 ventas
- **Rango de precios**: $1 - $500
- **Envío gratis**: Solo productos con envío gratis

#### Modos de Operación

**Automático**
- El plugin encuentra y aprueba productos automáticamente
- Cero intervención manual
- Usa algoritmo de score balanceado

**Semi-automático** (Recomendado)
- El plugin encuentra productos
- Tú apruebas los mejores
- Balance perfecto entre automatización y control

**Manual**
- Tú buscas y agregas productos manualmente
- Máximo control

### 3. Sistema de Pesos (Manual + Automático)

#### **PRIORIDAD:**
1. **Productos con peso manual > 0** (se muestran primero)
2. **Productos automáticos** (si no hay productos manuales)

#### Ejemplo de uso:

```
Tienes estos productos en tu banco:

Laptop ASUS - Peso Manual: 10 - Keywords: "laptop, gaming"
Mouse RGB - Peso Manual: 8 - Keywords: "mouse, gaming"
Teclado - Peso Manual: 0 (automático) - Score: 85
Audífonos - Peso Manual: 0 (automático) - Score: 90

Usuario busca "laptop gaming":
→ Se muestra "Laptop ASUS" (peso manual 10)

Usuario busca "audífonos":
→ Se muestra "Audífonos" (no hay manual, usa automático con score 90)
```

---

## 📝 Guía de Uso

### Crear un Anuncio

1. **Ir a Ad Platform → Añadir Nuevo**

2. **Completar formulario:**
   - **Título**: Nombre descriptivo
   - **Tipo**: Banner, Texto, HTML o Native
   - **URL de Afiliado**: Link completo (ej: `https://hostinger.com?ref=TU_CODIGO`)
   - **Imagen**: Subir o URL (opcional para banners)
   - **Texto**: Título y descripción (para anuncios de texto)
   - **Keywords**: `hosting, web, dominio` (separados por comas)

3. **Configurar Targeting:**
   - **Países**: `MX, US, ES, AR` (vacío = todos)
   - **Dispositivos**: Desktop, Móvil, Tablet
   - **Fechas**: Inicio y fin de campaña
   - **Límites**: Max impresiones/clicks por día

4. **Seleccionar Zonas:**
   - Header, Sidebar, In-Content, Footer

5. **Guardar**: Click en "Publicar"

### Usar Shortcodes

#### En posts/páginas de WordPress:

```php
// Mostrar zona específica
[ad_zone id="1"]

// Con keywords personalizados
[ad_zone id="1" keywords="hosting,web"]

// Anuncio específico
[ad_single id="123"]
```

#### En templates PHP:

```php
<?php
// Mostrar zona
if (function_exists('ad_platform_zone')) {
    ad_platform_zone(1);
}
?>
```

### Integrar en Sitios Externos

#### 1. Incluir SDK en `<head>`:

```html
<script src="https://tudominio.com/wp-content/plugins/ad-platform/public/js/ad-sdk.js"></script>
<script>
window.adpConfig = {
    api_url: 'https://tudominio.com/wp-json/adplatform/v1/',
    home_url: 'https://tudominio.com'
};
</script>
<link rel="stylesheet" href="https://tudominio.com/wp-content/plugins/ad-platform/public/css/public.css">
```

#### 2. Colocar zonas en HTML:

```html
<!-- En cualquier parte de tu sitio -->
<div class="adp-zone" data-zone-id="1"></div>

<!-- Con keywords personalizados -->
<div class="adp-zone" data-zone-id="1" data-keywords="hosting,web,dominio"></div>

<!-- En sitios de adultos (xlatinas.com) -->
<div class="adp-zone" data-zone-id="2" data-keywords="adult,dating"></div>

<!-- En blog de tecnología (dragoncuantico.com) -->
<div class="adp-zone" data-zone-id="1" data-keywords="crypto,bitcoin,blockchain"></div>
```

#### 3. Funciona en:
- ✅ WordPress (shortcodes + PHP functions)
- ✅ HTML estático
- ✅ Blogger/Blogspot
- ✅ Cualquier sitio web

---

## 🔞 Filtrado de Contenido Adulto

### ¿Para qué sirve?

Esta funcionalidad te permite mostrar anuncios de productos para adultos **SOLO** en sitios destinados a ese público (como **xlatinas.com**), mientras mantienes anuncios regulares en tus otros sitios.

### Tipos de Contenido Adulto Permitido

✅ **SÍ permitido:**
- Suplementos para adultos
- Masajeadores personales
- Productos de bienestar y salud
- Artículos de dormitorio
- Productos de cuidado personal

❌ **NO permitido:**
- Armas
- Drogas
- Contenido ilegal

### Configurar Anuncios de Adultos

#### 1. Marcar Anuncios Manuales

```
Ad Platform → Anuncios → Editar Anuncio
```

- Buscar la sección "Contenido Adulto"
- ✅ Marcar checkbox "Este anuncio contiene contenido para adultos"
- Guardar

**Resultado:** Este anuncio **SOLO** se mostrará en sitios marcados como adultos.

#### 2. Configurar Productos de AliExpress

```
Ad Platform → Configuración → AliExpress → Categorías de Adultos
```

Agregar categorías separadas por comas:
```
health,wellness,massage,supplements,bedroom,personal-care
```

Los productos de AliExpress de estas categorías se tratarán como contenido adulto.

### Integrar en Sitios Adultos

#### Para xlatinas.com (sitio adulto):

```html
<!-- En el <head> -->
<script src="https://tudominio.com/wp-content/plugins/ad-platform/public/js/ad-sdk.js"></script>
<script>
window.adpConfig = {
    api_url: 'https://tudominio.com/wp-json/adplatform/v1/',
    home_url: 'https://tudominio.com',
    adult_site: true  // ⚠️ IMPORTANTE: Habilitar contenido adulto
};
</script>
<link rel="stylesheet" href="https://tudominio.com/wp-content/plugins/ad-platform/public/css/public.css">

<!-- Zonas de anuncio -->
<div class="adp-zone" data-zone-id="1" data-adult-site="true"></div>
```

#### Para dragoncuantico.com (sitio normal):

```html
<!-- En el <head> -->
<script src="https://tudominio.com/wp-content/plugins/ad-platform/public/js/ad-sdk.js"></script>
<script>
window.adpConfig = {
    api_url: 'https://tudominio.com/wp-json/adplatform/v1/',
    home_url: 'https://tudominio.com',
    adult_site: false  // Contenido normal
};
</script>
<link rel="stylesheet" href="https://tudominio.com/wp-content/plugins/ad-platform/public/css/public.css">

<!-- Zonas de anuncio -->
<div class="adp-zone" data-zone-id="1"></div>
```

### Lógica de Filtrado

```
┌─────────────────────────────────────────┐
│  Usuario visita xlatinas.com (adulto)   │
└─────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────┐
│  SDK envía: adult_site = true           │
└─────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────┐
│  API puede mostrar:                     │
│  ✅ Anuncios adultos                    │
│  ✅ Anuncios normales                   │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│  Usuario visita dragoncuantico.com      │
└─────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────┐
│  SDK envía: adult_site = false          │
└─────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────┐
│  API puede mostrar:                     │
│  ✅ Anuncios normales SOLAMENTE         │
│  ❌ Anuncios adultos (bloqueados)       │
└─────────────────────────────────────────┘
```

### Ejemplos de Uso

**Anuncio: "Suplemento Premium para Adultos"**
- ✅ Marcado como contenido adulto
- Se muestra en: xlatinas.com ✅
- NO se muestra en: dragoncuantico.com ❌

**Anuncio: "Hosting Hostinger"**
- ⬜ NO marcado como adulto
- Se muestra en: xlatinas.com ✅
- Se muestra en: dragoncuantico.com ✅

### Ventajas

✅ **Cumplimiento**: Evita mostrar contenido adulto en sitios generales
✅ **Segmentación**: Anuncios relevantes para cada audiencia
✅ **Monetización**: Maximiza ingresos con productos específicos
✅ **Flexible**: Un solo sistema gestiona todos tus sitios

---

## 🔥 Gestión de Productos AliExpress

### Aprobar Productos Pendientes

1. **Ir a Ad Platform → AliExpress → Pendientes**

2. **Revisar productos:**
   - Ver imagen, precio, comisión, rating, ventas
   - Score automático calculado

3. **Aprobar con peso manual:**
   - Establecer peso 1-10 (10 = máxima prioridad)
   - Click en "✅ Aprobar"

4. **El producto se agrega al banco**

### Banco de Productos Aprobados

```
Ad Platform → AliExpress → Aprobados
```

- Ver todos los productos activos
- Estadísticas de rendimiento (impresiones, clicks, CTR)
- Ordenados por peso manual primero

### Buscar Productos Manualmente

```
Ad Platform → AliExpress → Buscar
```

- Buscar por keywords
- Filtrado automático según configuración
- Agregar al banco con peso manual

---

## 📄 Landing Pages de Productos

### ¿Qué son?

Landing pages completas para productos de afiliados. En lugar de solo mostrar banners, puedes crear páginas de producto con toda la información, imágenes, especificaciones y botones CTA.

### Crear Landing Pages

#### Desde AliExpress (Automático)

1. **Ir a Ad Platform → AliExpress → Aprobados**
2. **Click en "Crear Landing"** junto al producto
3. **Se genera automáticamente:**
   - Título
   - Descripción
   - Imágenes
   - Precio y descuento
   - Rating y ventas
   - Botón CTA

#### Manualmente (Otros programas)

1. **Ir a Ad Platform → Productos → Añadir Nuevo**
2. **Completar información:**
   - Título del producto
   - Descripción completa
   - Imagen destacada
   - Galería de imágenes
   - Características (una por línea)
   - Especificaciones (Nombre: Valor por línea)
   - URL de afiliado
   - Precio, rating, etc.
3. **Seleccionar plataforma:** Hotmart, Fiverr, Hostinger, etc.
4. **Publicar**

### Shortcodes

```php
// Mostrar productos recientes
[adp_products limit="6" platform="aliexpress"]

// Carrusel de productos
[adp_remarketing_carousel limit="5" title="Productos que viste"]
```

### URLs

- Producto individual: `tudominio.com/producto/nombre-producto/`
- Archivo de productos: `tudominio.com/productos/`
- Por plataforma: `tudominio.com/plataforma/aliexpress/`

---

## 📱 Facebook Pixel & Remarketing

### Configurar Facebook Pixel

1. **Obtener Pixel ID**
   - Ir a [Facebook Business Manager](https://business.facebook.com/)
   - Events Manager → Conectar origen de datos → Web → Facebook Pixel
   - Copiar el Pixel ID (15-16 dígitos)

2. **Configurar en el Plugin**

```
Ad Platform → Configuración → Facebook Pixel
```

- ✅ Habilitar Facebook Pixel
- **Pixel ID**: Pegar tu ID
- **Advanced Matching**: Habilitar para mejor precisión
- **Eventos**: Seleccionar qué trackear (PageView, ViewContent, AddToCart)

### Eventos Trackeados

| Evento | Cuándo se dispara | Datos enviados |
|--------|-------------------|----------------|
| PageView | Cada página | URL, referrer |
| ViewContent | Landing page de producto | product_id, price, currency |
| AddToCart | Click en botón CTA | product_id, price |

### Carrusel de Remarketing

Muestra productos que el usuario ha visto recientemente (como Instagram/Facebook).

#### Shortcode

```php
// Carrusel con 5 productos
[adp_remarketing_carousel limit="5" title="Productos que viste"]

// Solo productos de AliExpress
[adp_remarketing_carousel limit="5" platform="aliexpress"]

// Lista simple
[adp_recently_viewed limit="3" style="list"]

// Cuadrícula
[adp_recently_viewed limit="6" style="grid"]
```

#### Widget

1. **Ir a Apariencia → Widgets**
2. **Arrastrar "Ad Platform - Productos Vistos"** a sidebar
3. **Configurar:**
   - Título
   - Cantidad de productos
   - Plataforma (opcional)
   - Estilo (lista/cuadrícula)

### Configuración del Carrusel

```
Ad Platform → Configuración → Facebook Pixel → Remarketing
```

- ✅ Habilitar carrusel de productos vistos
- **Cantidad**: 1-10 productos
- **Filtrar por plataforma**: Todas, solo AliExpress, solo Hotmart, etc.

---

## 💾 Backup y Restauración

### ¿Para qué sirve?

- Crear copias de seguridad de toda la configuración
- Migrar a otro servidor
- Restaurar rápidamente si algo sale mal
- Guardar diferentes versiones de configuración

### Exportar Backup

```
Ad Platform → Backup → Exportar
```

1. **Seleccionar qué exportar:**
   - ✅ Configuraciones del plugin
   - ✅ Anuncios
   - ✅ Campañas
   - ✅ Zonas
   - ✅ Landing Pages
   - ✅ Productos de AliExpress

2. **Click en "Descargar Backup"**
3. **Se descarga archivo JSON** con toda la información

### Importar Backup

```
Ad Platform → Backup → Importar
```

1. **Seleccionar archivo JSON** de backup anterior
2. **Opciones:**
   - ✅ Importar configuraciones
   - ✅ Importar contenido
   - ⬜ Sobrescribir existentes (cuidado)
3. **Click en "Importar Backup"**
4. **Ver resultado:** X configuraciones, X posts, X términos importados

### Buenas Prácticas

- **Backup semanal**: Programar exportación cada semana
- **Antes de cambios**: Siempre hacer backup antes de cambios importantes
- **Guardar en la nube**: Subir backups a Google Drive, Dropbox, etc.
- **Probar restauración**: Verificar que el backup funciona en sitio de prueba

---

## 📊 Ver Estadísticas

```
Ad Platform → Estadísticas
```

### Dashboard General
- 👁️ Total de impresiones
- 🖱️ Total de clicks
- 📊 CTR promedio
- ✅ Conversiones
- 💰 Ingresos estimados

### Por Anuncio
- Top 10 anuncios por clicks
- Filtrar por rango de fechas
- Exportar reportes (futuro)

---

## 🎨 Personalización

### Estilos CSS

Puedes sobrescribir los estilos en tu tema:

```css
/* Personalizar anuncios */
.adp-ad {
    /* tus estilos */
}

/* Personalizar botón CTA */
.adp-ad-cta {
    background: #tu-color;
}

/* Personalizar tarjetas de AliExpress */
.adp-ad-aliexpress-card {
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}
```

### Templates

Crear templates personalizados en tu tema:

```
tu-tema/
  ad-platform/
    ad-banner.php
    ad-text.php
    ad-native.php
```

---

## 🔌 API REST

### Endpoints Disponibles

#### Obtener Anuncio
```
GET /wp-json/adplatform/v1/ad/get

Parámetros:
- zone_id: ID de zona (opcional)
- keywords: "hosting,web" (opcional)
- category: Categoría (opcional)
- country: Código ISO país (opcional)
- device: desktop|mobile|tablet (opcional)

Respuesta:
{
    "success": true,
    "ad": {
        "id": 123,
        "type": "banner_image",
        "title": "Hosting Premium",
        "url": "https://...",
        "image": "https://...",
        ...
    }
}
```

#### Trackear Impresión
```
POST /wp-json/adplatform/v1/track/impression

Body:
{
    "ad_id": 123
}
```

#### Trackear Click
```
POST /wp-json/adplatform/v1/track/click

Body:
{
    "ad_id": 123,
    "impression_id": 456 (opcional)
}
```

---

## 🛡️ Seguridad

### Mejores Prácticas Implementadas

- ✅ **Sanitización**: Todos los inputs son sanitizados
- ✅ **Nonces**: Protección CSRF en formularios
- ✅ **Prepared Statements**: Queries SQL seguras
- ✅ **Anonimización IP**: Cumplimiento GDPR
- ✅ **.htaccess**: Protección de archivos sensibles
- ✅ **Cabeceras HTTP**: X-Frame-Options, X-XSS-Protection, etc.

---

## 📱 Integración con Facebook Pixel

### Configurar Facebook Pixel

1. Obtener tu Pixel ID de Facebook Business Manager

2. Agregar al `<head>` de tus sitios:

```html
<!-- Facebook Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', 'TU_PIXEL_ID');
fbq('track', 'PageView');
</script>
```

3. Trackear eventos personalizados:

```javascript
// Cuando se muestra un anuncio
fbq('track', 'ViewContent', {
    content_name: 'Anuncio de Hostinger',
    content_category: 'Hosting'
});

// Cuando se hace click
fbq('track', 'Lead');
```

---

## 🚦 Solución de Problemas

### Los anuncios no se muestran

1. **Verificar permalinks**
   - Ajustes → Enlaces Permanentes → Guardar

2. **Verificar API REST**
   - Visitar: `tudominio.com/wp-json/adplatform/v1/ad/get`
   - Debe retornar JSON, no error

3. **Verificar JavaScript**
   - Abrir Consola del navegador (F12)
   - Buscar errores de AdPlatform

### AliExpress no funciona

1. **Verificar credenciales**
   - App Key, App Secret, Tracking ID correctos

2. **Verificar conexión**
   - Botón "Verificar Conexión" en Configuración

3. **Revisar logs de errores de WordPress**

### Links de redirección no funcionan

1. **Flush rewrite rules**
   - Ajustes → Enlaces Permanentes → Guardar

2. **Verificar .htaccess**
   - Debe tener reglas de WordPress

---

## 🔄 Tareas Cron

El plugin programa tareas automáticas:

- **Actualizar estadísticas diarias**: 1 vez al día
- **Actualizar productos AliExpress**: 2 veces al día
- **Limpiar caché antiguo**: 1 vez a la semana

### Verificar Cron Jobs

```php
wp cron event list
```

---

## 📞 Soporte

- **Documentación**: Ver `DESIGN.md` para arquitectura detallada
- **Issues**: [GitHub Issues](https://github.com/derkeshtai/ad-Platform/issues)
- **Email**: tu-email@ejemplo.com

---

## 📄 Licencia

GPL v2 or later

---

## 🙏 Créditos

Desarrollado por [DerKeshtai](https://github.com/derkeshtai)

---

## 🎯 Roadmap

### Características Completadas
- [x] Landing Pages de productos
- [x] Facebook Pixel integrado
- [x] Carrusel de remarketing
- [x] Sistema de backup/restauración
- [x] Widget de productos vistos
- [x] Filtrado de contenido adulto

### Próximas Características
- [ ] Integración con más redes de afiliados (Amazon, ClickBank, ShareASale)
- [ ] Machine Learning para optimización automática
- [ ] Bloque de Gutenberg
- [ ] Exportar reportes a CSV/PDF
- [ ] Audiencias personalizadas de Facebook
- [ ] Integración con Google Analytics
- [ ] Sistema de notificaciones por email
- [ ] API pública para terceros
- [ ] Editor visual de landing pages

---

¡Gracias por usar Ad Platform! 🚀
