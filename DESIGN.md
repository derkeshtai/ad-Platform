# Ad Platform - Documento de Diseño Técnico

## 📐 Arquitectura del Sistema

### Visión General

Ad Platform es un sistema de gestión de anuncios de afiliados construido como plugin de WordPress, diseñado para servir anuncios contextuales tanto en el sitio WordPress como en sitios externos mediante un SDK JavaScript.

```
┌─────────────────────────────────────────────────────────┐
│               WORDPRESS (Sitio Principal)                │
│  ┌────────────────────────────────────────────────────┐ │
│  │           PLUGIN: AD PLATFORM                      │ │
│  │  ┌──────────────┐  ┌──────────────────────────┐  │ │
│  │  │ Admin Panel  │  │  REST API Endpoints      │  │ │
│  │  │ (PHP/HTML)   │  │  /wp-json/adplatform/v1/ │  │ │
│  │  └──────────────┘  └──────────────────────────┘  │ │
│  │                                                    │ │
│  │  ┌─────────────────────────────────────────────┐ │ │
│  │  │  Core System                                │ │ │
│  │  │  - Custom Post Types (Ads, Campaigns, Zones)│ │ │
│  │  │  - Tracking System (Impressions/Clicks)     │ │ │
│  │  │  - Contextual Matcher (Keywords + NLP)      │ │ │
│  │  │  - AliExpress Integration (API + Weights)   │ │ │
│  │  │  - Redirector (/go/ short links)            │ │ │
│  │  └─────────────────────────────────────────────┘ │ │
│  │                                                    │ │
│  │  ┌─────────────────────────────────────────────┐ │ │
│  │  │  Database                                   │ │ │
│  │  │  - wp_adp_impressions                       │ │ │
│  │  │  - wp_adp_clicks                            │ │ │
│  │  │  - wp_adp_conversions                       │ │ │
│  │  │  - wp_adp_aliexpress_products               │ │ │
│  │  │  - wp_adp_stats_daily                       │ │ │
│  │  │  - wp_adp_sites                             │ │ │
│  │  └─────────────────────────────────────────────┘ │ │
│  └────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
                          ↓
              ┌───────────────────────┐
              │   REST API (JSON)     │
              │  /wp-json/adplatform/ │
              └───────────────────────┘
                          ↓
        ┌─────────────────────────────────────┐
        │    SDK JavaScript (ad-sdk.js)       │
        │    - Auto-detect zones              │
        │    - Fetch ads from API             │
        │    - Render ads                     │
        │    - Track impressions/clicks       │
        └─────────────────────────────────────┘
                          ↓
        ┌─────────────────────────────────────┐
        │     SITIOS EXTERNOS                 │
        │  - xlatinas.com                     │
        │  - carlydean.com                    │
        │  - dragoncuantico.com               │
        │  - Blogspot                         │
        └─────────────────────────────────────┘
```

---

## 🏗️ Estructura de Archivos

```
ad-platform/
├── ad-platform.php              # Archivo principal del plugin
├── .htaccess                    # Seguridad del directorio raíz
├── index.php                    # Prevenir acceso directo
├── README.md                    # Documentación de usuario
├── DESIGN.md                    # Este archivo
│
├── includes/                    # Core del plugin
│   ├── .htaccess               # Proteger PHP files
│   ├── index.php
│   ├── class-installer.php     # Instalación y DB setup
│   ├── class-api.php           # REST API endpoints
│   ├── class-tracking.php      # Sistema de tracking
│   ├── class-matcher.php       # Motor de matching contextual
│   ├── class-aliexpress.php    # Integración AliExpress
│   ├── class-redirector.php    # Sistema /go/{id}
│   │
│   └── post-types/             # Custom Post Types
│       ├── .htaccess
│       ├── index.php
│       ├── class-ad.php        # CPT: Anuncios
│       ├── class-campaign.php  # CPT: Campañas
│       └── class-zone.php      # CPT: Zonas
│
├── admin/                       # Panel de administración
│   ├── .htaccess
│   ├── index.php
│   ├── class-admin.php         # Controlador admin
│   │
│   ├── views/                  # Vistas HTML
│   │   ├── settings.php
│   │   ├── aliexpress-products.php
│   │   └── stats.php
│   │
│   ├── css/
│   │   └── admin.css
│   │
│   └── js/
│       └── admin.js
│
├── public/                      # Frontend público
│   ├── .htaccess
│   ├── index.php
│   ├── class-public.php        # Controlador público
│   │
│   ├── css/
│   │   └── public.css          # Estilos de anuncios
│   │
│   └── js/
│       └── ad-sdk.js           # SDK para sitios externos
│
└── languages/                   # Traducciones (i18n)
    └── ad-platform.pot
```

---

## 🗄️ Estructura de Base de Datos

### Tabla: `wp_adp_impressions`

Registra cada vez que un anuncio es mostrado.

```sql
CREATE TABLE wp_adp_impressions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ad_id           BIGINT UNSIGNED NOT NULL,
    site_url        VARCHAR(255) NOT NULL,
    page_url        VARCHAR(512),
    user_ip         VARCHAR(45),      -- Anonimizada si GDPR
    user_agent      TEXT,
    country_code    VARCHAR(2),
    device_type     VARCHAR(20) DEFAULT 'desktop',
    referrer        VARCHAR(512),
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX(ad_id),
    INDEX(created_at),
    INDEX(site_url)
);
```

### Tabla: `wp_adp_clicks`

Registra cada click en un anuncio.

```sql
CREATE TABLE wp_adp_clicks (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ad_id           BIGINT UNSIGNED NOT NULL,
    impression_id   BIGINT UNSIGNED,  -- FK a impressions
    site_url        VARCHAR(255) NOT NULL,
    page_url        VARCHAR(512),
    user_ip         VARCHAR(45),
    user_agent      TEXT,
    country_code    VARCHAR(2),
    device_type     VARCHAR(20) DEFAULT 'desktop',
    referrer        VARCHAR(512),
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX(ad_id),
    INDEX(impression_id),
    INDEX(created_at)
);
```

### Tabla: `wp_adp_conversions`

Tracking de conversiones (futuro).

```sql
CREATE TABLE wp_adp_conversions (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ad_id               BIGINT UNSIGNED NOT NULL,
    click_id            BIGINT UNSIGNED,
    conversion_value    DECIMAL(10,2) DEFAULT 0.00,
    commission_value    DECIMAL(10,2) DEFAULT 0.00,
    status              VARCHAR(20) DEFAULT 'pending', -- pending|approved|rejected
    external_id         VARCHAR(100),  -- ID de la red de afiliados
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX(ad_id),
    INDEX(click_id),
    INDEX(status)
);
```

### Tabla: `wp_adp_aliexpress_products`

**Caché de productos de AliExpress con sistema de pesos.**

```sql
CREATE TABLE wp_adp_aliexpress_products (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id          VARCHAR(100) NOT NULL UNIQUE,
    title               VARCHAR(500) NOT NULL,
    description         TEXT,
    image_url           VARCHAR(512),
    price               DECIMAL(10,2) DEFAULT 0.00,
    original_price      DECIMAL(10,2) DEFAULT 0.00,
    discount_percent    INT(3) DEFAULT 0,
    commission_rate     DECIMAL(5,2) DEFAULT 0.00,
    commission_value    DECIMAL(10,2) DEFAULT 0.00,
    rating              DECIMAL(3,2) DEFAULT 0.00,
    orders_count        INT(11) DEFAULT 0,
    category            VARCHAR(100),
    affiliate_url       VARCHAR(1000) NOT NULL,
    keywords            TEXT,          -- Para matching contextual
    free_shipping       TINYINT(1) DEFAULT 0,

    -- SISTEMA DE PESOS
    manual_weight       INT(3) DEFAULT 0,   -- 0 = automático, 1-10 = manual
    auto_score          DECIMAL(5,2) DEFAULT 0.00,  -- Score automático

    status              VARCHAR(20) DEFAULT 'pending',  -- pending|approved|rejected
    impressions_count   INT(11) DEFAULT 0,
    clicks_count        INT(11) DEFAULT 0,
    last_updated        DATETIME,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX(keywords(100)),
    INDEX(status),
    INDEX(manual_weight),
    INDEX(auto_score)
);
```

**Lógica de Pesos:**
- `manual_weight > 0`: Producto seleccionado manualmente (prioridad)
- `manual_weight = 0`: Producto automático (usa `auto_score`)

**Selección de Producto:**
```sql
-- Primero: Intentar productos manuales
SELECT * FROM wp_adp_aliexpress_products
WHERE manual_weight > 0 AND status = 'approved'
AND keywords LIKE '%gaming%'
ORDER BY manual_weight DESC, RAND()
LIMIT 1;

-- Si no hay: Usar productos automáticos
SELECT * FROM wp_adp_aliexpress_products
WHERE status = 'approved'
AND keywords LIKE '%gaming%'
ORDER BY auto_score DESC, RAND()
LIMIT 1;
```

### Tabla: `wp_adp_stats_daily`

Estadísticas agregadas por día (para rendimiento).

```sql
CREATE TABLE wp_adp_stats_daily (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ad_id           BIGINT UNSIGNED NOT NULL,
    date            DATE NOT NULL,
    impressions     INT(11) DEFAULT 0,
    clicks          INT(11) DEFAULT 0,
    conversions     INT(11) DEFAULT 0,
    revenue         DECIMAL(10,2) DEFAULT 0.00,

    UNIQUE KEY(ad_id, date),
    INDEX(date)
);
```

### Tabla: `wp_adp_sites`

Sitios registrados para usar el SDK.

```sql
CREATE TABLE wp_adp_sites (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_url            VARCHAR(255) NOT NULL UNIQUE,
    site_name           VARCHAR(255),
    api_key             VARCHAR(64) NOT NULL UNIQUE,  -- Para autenticación
    status              VARCHAR(20) DEFAULT 'active',
    allowed_zones       TEXT,  -- JSON de zonas permitidas
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_request_at     DATETIME,

    INDEX(status)
);
```

---

## 🔄 Flujo de Datos

### 1. Servir un Anuncio

```
Usuario visita página
    ↓
SDK detecta <div class="adp-zone" data-zone-id="1">
    ↓
SDK extrae keywords de la página (título, meta, contenido)
    ↓
SDK hace GET /wp-json/adplatform/v1/ad/get?zone_id=1&keywords=hosting,web
    ↓
API busca anuncios que cumplan:
    - Zona = 1
    - Keywords match
    - País permitido
    - Dispositivo permitido
    - Dentro de fechas programadas
    - No ha alcanzado límite diario
    ↓
API calcula relevancia (score)
    ↓
API hace selección ponderada por peso
    ↓
Si NO encuentra anuncio manual:
    ↓
    API llama a AliExpress Integration
        ↓
        Busca producto con manual_weight > 0 y keywords match
            ↓
            Si existe → Retorna producto
            ↓
        Si NO existe → Busca automático con auto_score
    ↓
API retorna JSON con datos del anuncio
    ↓
SDK renderiza HTML del anuncio
    ↓
SDK trackea impresión: POST /track/impression
    ↓
Usuario ve el anuncio
```

### 2. Registrar un Click

```
Usuario hace click en anuncio
    ↓
SDK intercepta click
    ↓
SDK trackea: POST /track/click {ad_id: 123}
    ↓
API registra en wp_adp_clicks
    ↓
Si usa_short_link = true:
    ↓
    Redirige a /go/123
        ↓
        Redirector busca URL de afiliado
        ↓
        Redirige 302 a URL de afiliado
        ↓
    ↓
Sino:
    ↓
    Abre URL de afiliado directamente
```

---

## 🧠 Sistema de Pesos (Manual vs Automático)

### Algoritmo de Selección

```php
function get_product_ad($keywords) {
    // PASO 1: Intentar productos MANUALES (manual_weight > 0)
    $manual_product = DB::query("
        SELECT * FROM wp_adp_aliexpress_products
        WHERE manual_weight > 0
        AND status = 'approved'
        AND keywords LIKE '%$keywords%'
        ORDER BY manual_weight DESC, RAND()
        LIMIT 1
    ");

    if ($manual_product) {
        return $manual_product;  // ✅ PRIORIDAD
    }

    // PASO 2: Si no hay manuales, usar AUTOMÁTICOS
    $auto_product = DB::query("
        SELECT * FROM wp_adp_aliexpress_products
        WHERE status = 'approved'
        AND keywords LIKE '%$keywords%'
        ORDER BY auto_score DESC, RAND()
        LIMIT 1
    ");

    if ($auto_product) {
        return $auto_product;  // ✅ FALLBACK
    }

    // PASO 3: Si tampoco hay en caché, buscar en API
    return search_api_and_cache($keywords);
}
```

### Cálculo de Score Automático

```php
function calculate_auto_score($product) {
    $score = 0;

    // 40% - Comisión
    $score += ($product['commission_rate'] / 20) * 40;

    // 30% - Rating
    $score += ($product['rating'] / 5) * 30;

    // 20% - Popularidad (ventas)
    $orders_normalized = min($product['orders_count'] / 10000, 1);
    $score += $orders_normalized * 20;

    // 10% - Base
    $score += 10;

    // Bonus: Envío gratis
    if ($product['free_shipping']) {
        $score += 5;
    }

    // Penalización: Precio muy bajo
    if ($product['price'] < 5) {
        $score -= 10;
    }

    return round($score, 2);
}
```

**Ejemplo:**
```
Producto A:
- Comisión: 10% → (10/20)*40 = 20 puntos
- Rating: 4.8 → (4.8/5)*30 = 28.8 puntos
- Ventas: 5000 → (5000/10000)*20 = 10 puntos
- Base: 10 puntos
- Envío gratis: +5 puntos
TOTAL: 73.8 puntos
```

---

## 🎯 Motor de Matching Contextual

### Extracción de Keywords

```php
function extract_keywords_from_post($post_id) {
    $keywords = [];

    // 1. Título (peso alto)
    $title = get_the_title($post_id);
    $keywords[] = normalize($title);

    // 2. Tags de WordPress
    $tags = get_the_tags($post_id);
    foreach ($tags as $tag) {
        $keywords[] = $tag->name;
    }

    // 3. Categorías
    $categories = get_the_category($post_id);
    foreach ($categories as $cat) {
        $keywords[] = $cat->name;
    }

    // 4. Contenido (extraer top 15 palabras)
    $content = get_post_field('post_content', $post_id);
    $content_keywords = extract_from_text($content, 15);
    $keywords = array_merge($keywords, $content_keywords);

    return array_unique($keywords);
}
```

### Cálculo de Relevancia

```php
function calculate_relevance_score($ad, $keywords) {
    $score = 0;
    $ad_keywords = get_ad_keywords($ad->ID);

    foreach ($keywords as $keyword) {
        // Coincidencia exacta
        if (in_array($keyword, $ad_keywords)) {
            $score += 10;
        }
        // Coincidencia parcial
        else {
            foreach ($ad_keywords as $ad_keyword) {
                if (stripos($ad_keyword, $keyword) !== false) {
                    $score += 5;
                }
            }
        }
    }

    // Bonus: Keyword en título del anuncio
    $title = strtolower(get_the_title($ad->ID));
    foreach ($keywords as $keyword) {
        if (stripos($title, $keyword) !== false) {
            $score += 15;
        }
    }

    return $score;
}
```

---

## 🔐 Seguridad

### 1. Prevención de Acceso Directo

Todos los archivos PHP:
```php
if (!defined('ABSPATH')) {
    exit;
}
```

### 2. Archivos .htaccess

**Raíz del plugin:**
```apache
<FilesMatch "\.(php|phtml|php3|php4|php5|phps)$">
    Order Deny,Allow
    Deny from all
</FilesMatch>

<FilesMatch "^(ad-platform\.php)$">
    Order Allow,Deny
    Allow from all
</FilesMatch>

Options -Indexes
```

**Directorios internos (includes/, admin/):**
```apache
Order Deny,Allow
Deny from all
```

**CSS/JS públicos:**
```apache
Order Deny,Allow
Deny from all
<FilesMatch "\.(css|js)$">
    Order Allow,Deny
    Allow from all
</FilesMatch>
```

### 3. Sanitización de Datos

```php
// Texto
$value = sanitize_text_field($_POST['field']);

// Email
$email = sanitize_email($_POST['email']);

// URL
$url = esc_url_raw($_POST['url']);

// HTML (permitir tags seguros)
$html = wp_kses_post($_POST['content']);

// SQL Queries
$wpdb->prepare("SELECT * FROM table WHERE id = %d", $id);
```

### 4. Nonces en Formularios

```php
// Crear
wp_nonce_field('adp_settings_nonce');

// Verificar
if (!wp_verify_nonce($_POST['_wpnonce'], 'adp_settings_nonce')) {
    die('Security check failed');
}
```

### 5. Capabilities

```php
if (!current_user_can('manage_options')) {
    wp_die('Unauthorized');
}
```

---

## 🚀 Rendimiento

### 1. Caché

- **Transients API** para cachear anuncios
- **Object Cache** si está disponible (Redis, Memcached)
- Duración configurable (default: 1 hora)

### 2. Lazy Loading

El SDK carga anuncios de forma asíncrona:
```javascript
// No bloquea el renderizado de la página
fetch(apiUrl).then(data => renderAd(data));
```

### 3. Estadísticas Agregadas

Tabla `wp_adp_stats_daily` para evitar queries pesados:
```php
// En vez de COUNT(*) en tiempo real
SELECT impressions, clicks FROM wp_adp_stats_daily
WHERE ad_id = 123 AND date = CURDATE();
```

### 4. Índices de Base de Datos

Todos los campos de búsqueda frecuente tienen índices:
- `ad_id` en tablas de tracking
- `created_at` para filtros de fecha
- `keywords` en productos AliExpress
- `manual_weight` y `auto_score`

---

## 🔄 Tareas Cron

```php
// Actualizar estadísticas diarias
add_action('adp_update_daily_stats', 'Ad_Platform_Tracking::update_daily_stats');
wp_schedule_event(time(), 'daily', 'adp_update_daily_stats');

// Actualizar productos AliExpress
add_action('adp_update_aliexpress_products', 'Ad_Platform_AliExpress::update_products');
wp_schedule_event(time(), 'twicedaily', 'adp_update_aliexpress_products');

// Limpiar caché antiguo
add_action('adp_clean_old_cache', 'Ad_Platform_Tracking::clean_old_data');
wp_schedule_event(time(), 'weekly', 'adp_clean_old_cache');
```

---

## 🌐 REST API

### Endpoints

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/wp-json/adplatform/v1/ad/get` | Obtener anuncio |
| POST | `/wp-json/adplatform/v1/track/impression` | Trackear impresión |
| POST | `/wp-json/adplatform/v1/track/click` | Trackear click |
| GET | `/wp-json/adplatform/v1/stats/{ad_id}` | Estadísticas (admin) |

### Autenticación

- Endpoints públicos: `permission_callback => '__return_true'`
- Endpoints admin: `permission_callback => check_admin_permission()`

---

## 📱 Responsive Design

### Breakpoints

```css
/* Mobile */
@media (max-width: 768px) {
    .adp-ad-aliexpress-card {
        max-width: 100%;
    }
}

/* Desktop */
@media (min-width: 769px) {
    .adp-ad-aliexpress-card {
        max-width: 320px;
    }
}
```

---

## 🔮 Extensibilidad

### Hooks de Acción

```php
// Después de registrar impresión
do_action('adp_after_impression', $impression_id, $ad_id);

// Después de registrar click
do_action('adp_after_click', $click_id, $ad_id);

// Antes de servir anuncio
do_action('adp_before_serve_ad', $ad_id);
```

### Filtros

```php
// Modificar query de anuncios
$args = apply_filters('adp_ad_query_args', $args);

// Modificar score de relevancia
$score = apply_filters('adp_relevance_score', $score, $ad, $keywords);

// Modificar productos de AliExpress antes de cachear
$products = apply_filters('adp_aliexpress_products', $products);
```

---

## 🧪 Testing

### Unit Tests (Futuro)

```bash
# Instalar PHPUnit
composer require --dev phpunit/phpunit

# Ejecutar tests
vendor/bin/phpunit
```

### Test Manual

1. Crear anuncio de prueba
2. Agregar `[ad_zone id="1"]` en post
3. Verificar que se muestra
4. Click en anuncio
5. Verificar estadísticas

---

## 📊 Monitoreo

### Logs de Errores

```php
error_log('AdPlatform: ' . $error_message);
```

### Debug Mode

```php
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('AdPlatform Debug: ' . print_r($data, true));
}
```

---

## 🎓 Mejores Prácticas

### Código

- ✅ Usar namespaces para evitar conflictos
- ✅ Singleton pattern para clases principales
- ✅ Dependency Injection cuando sea posible
- ✅ Comentarios PHPDoc
- ✅ Seguir WordPress Coding Standards

### Base de Datos

- ✅ Usar prepared statements siempre
- ✅ Índices en campos de búsqueda frecuente
- ✅ Evitar `SELECT *`, especificar columnas
- ✅ Usar transacciones para operaciones múltiples

### Frontend

- ✅ Minificar CSS/JS en producción
- ✅ Lazy loading de recursos
- ✅ Evitar bloquear el renderizado
- ✅ Progressive enhancement

---

## 🔧 Mantenimiento

### Actualizaciones

1. Incrementar versión en `ad-platform.php`
2. Actualizar changelog en README.md
3. Ejecutar tests
4. Commit y push a GitHub
5. Tag release: `git tag v1.0.1`

### Migraciones de DB

```php
function adp_upgrade_db() {
    $current_version = get_option('ad_platform_db_version', '1.0');
    $new_version = '1.1';

    if (version_compare($current_version, $new_version, '<')) {
        // Ejecutar migración
        global $wpdb;
        $wpdb->query("ALTER TABLE ...");

        update_option('ad_platform_db_version', $new_version);
    }
}
```

---

¡Fin del documento de diseño! 🚀
