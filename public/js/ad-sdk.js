/**
 * Ad Platform SDK
 * SDK para mostrar anuncios en sitios externos
 */

(function() {
    'use strict';

    // Configuración
    const AdPlatformSDK = {
        config: window.adpConfig || {},
        initialized: false,
        impressionTracked: new Set(),

        /**
         * Inicializar SDK
         */
        init: function() {
            if (this.initialized) return;

            // Detectar zonas de anuncio en la página
            const zones = document.querySelectorAll('.adp-zone');

            zones.forEach(zone => {
                this.loadAd(zone);
            });

            this.initialized = true;
        },

        /**
         * Cargar anuncio en una zona
         */
        loadAd: function(zoneElement) {
            const zoneId = zoneElement.dataset.zoneId;
            const keywords = zoneElement.dataset.keywords || this.extractPageKeywords();
            const category = zoneElement.dataset.category || '';
            const adultSite = zoneElement.dataset.adultSite || this.config.adult_site || false;

            // Mostrar loading
            zoneElement.innerHTML = '<div class="adp-loading">Cargando anuncio...</div>';

            // Construir URL de API
            const params = new URLSearchParams({
                zone_id: zoneId || '',
                keywords: keywords,
                category: category,
                device: this.getDeviceType(),
                country: this.getCountryCode(),
                adult_site: adultSite ? '1' : '0'
            });

            const apiUrl = this.config.api_url + 'ad/get?' + params.toString();

            // Fetch anuncio
            fetch(apiUrl)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.ad) {
                        this.renderAd(zoneElement, data.ad);
                        this.trackImpression(data.ad.id);
                    } else {
                        zoneElement.innerHTML = '';
                    }
                })
                .catch(error => {
                    console.error('AdPlatform error:', error);
                    zoneElement.innerHTML = '';
                });
        },

        /**
         * Renderizar anuncio
         */
        renderAd: function(container, ad) {
            let html = '';

            switch (ad.type) {
                case 'banner_image':
                    html = this.renderBanner(ad);
                    break;
                case 'text':
                    html = this.renderText(ad);
                    break;
                case 'html':
                    html = ad.html;
                    break;
                case 'native':
                    html = this.renderNative(ad);
                    break;
                case 'aliexpress':
                    html = this.renderAliExpress(ad);
                    break;
                default:
                    html = this.renderBanner(ad);
            }

            container.innerHTML = html;

            // Añadir event listener para clicks
            const links = container.querySelectorAll('a[data-ad-id]');
            links.forEach(link => {
                link.addEventListener('click', (e) => {
                    this.trackClick(ad.id);
                });
            });
        },

        /**
         * Renderizar banner
         */
        renderBanner: function(ad) {
            return `
                <div class="adp-ad adp-ad-banner" data-ad-id="${ad.id}">
                    <a href="${ad.url}" target="${ad.target}" data-ad-id="${ad.id}" rel="noopener sponsored">
                        ${ad.image ? `<img src="${ad.image}" alt="${this.escapeHtml(ad.title)}" class="adp-ad-image">` : ''}
                    </a>
                </div>
            `;
        },

        /**
         * Renderizar texto
         */
        renderText: function(ad) {
            return `
                <div class="adp-ad adp-ad-text" data-ad-id="${ad.id}">
                    <div class="adp-ad-content">
                        <h3 class="adp-ad-title">${this.escapeHtml(ad.text_title || ad.title)}</h3>
                        ${ad.text_description ? `<p class="adp-ad-description">${this.escapeHtml(ad.text_description)}</p>` : ''}
                        <a href="${ad.url}" target="${ad.target}" data-ad-id="${ad.id}" class="adp-ad-cta" rel="noopener sponsored">
                            ${this.escapeHtml(ad.cta)}
                        </a>
                    </div>
                </div>
            `;
        },

        /**
         * Renderizar nativo
         */
        renderNative: function(ad) {
            return `
                <div class="adp-ad adp-ad-native" data-ad-id="${ad.id}">
                    <div class="adp-ad-label">Publicidad</div>
                    ${ad.image ? `
                        <div class="adp-ad-image-wrapper">
                            <img src="${ad.image}" alt="${this.escapeHtml(ad.title)}" class="adp-ad-image">
                        </div>
                    ` : ''}
                    <div class="adp-ad-content">
                        <h3 class="adp-ad-title">${this.escapeHtml(ad.text_title || ad.title)}</h3>
                        ${ad.text_description ? `<p class="adp-ad-description">${this.escapeHtml(ad.text_description)}</p>` : ''}
                        <a href="${ad.url}" target="${ad.target}" data-ad-id="${ad.id}" class="adp-ad-cta" rel="noopener sponsored">
                            ${this.escapeHtml(ad.cta)}
                        </a>
                    </div>
                </div>
            `;
        },

        /**
         * Renderizar AliExpress
         */
        renderAliExpress: function(ad) {
            const template = ad.template || 'card';

            if (template === 'card') {
                return `
                    <div class="adp-ad adp-ad-aliexpress adp-ad-aliexpress-card" data-ad-id="${ad.id}">
                        <div class="adp-ad-label">Publicidad</div>
                        <a href="${ad.url}" target="_blank" data-ad-id="${ad.id}" class="adp-ad-link" rel="noopener sponsored">
                            ${ad.image ? `
                                <div class="adp-ad-image-wrapper">
                                    <img src="${ad.image}" alt="${this.escapeHtml(ad.title)}" class="adp-ad-image">
                                </div>
                            ` : ''}
                            <div class="adp-ad-content">
                                <h3 class="adp-ad-title">${this.escapeHtml(ad.title)}</h3>

                                ${ad.rating > 0 ? `
                                    <div class="adp-ad-rating">
                                        <span class="adp-stars">${this.renderStars(ad.rating)}</span>
                                        <span class="adp-rating-number">${ad.rating.toFixed(1)}</span>
                                        ${ad.orders > 0 ? `<span class="adp-orders">(${this.formatNumber(ad.orders)} ventas)</span>` : ''}
                                    </div>
                                ` : ''}

                                <div class="adp-ad-price">
                                    <span class="adp-price-current">$${ad.price.toFixed(2)}</span>
                                    ${ad.original_price > ad.price ? `
                                        <span class="adp-price-original">$${ad.original_price.toFixed(2)}</span>
                                        <span class="adp-discount">${ad.discount}% OFF</span>
                                    ` : ''}
                                </div>

                                ${ad.free_shipping ? '<div class="adp-ad-badge adp-badge-shipping">🚚 Envío Gratis</div>' : ''}

                                <div class="adp-ad-cta-wrapper">
                                    <span class="adp-ad-cta">${this.escapeHtml(ad.cta)}</span>
                                </div>
                            </div>
                        </a>
                    </div>
                `;
            } else {
                // Banner
                return `
                    <div class="adp-ad adp-ad-aliexpress adp-ad-aliexpress-banner" data-ad-id="${ad.id}">
                        <a href="${ad.url}" target="_blank" data-ad-id="${ad.id}" class="adp-ad-link" rel="noopener sponsored">
                            ${ad.image ? `<img src="${ad.image}" alt="${this.escapeHtml(ad.title)}" class="adp-ad-image">` : ''}
                            <div class="adp-ad-overlay">
                                <h3>${this.escapeHtml(ad.title)}</h3>
                                <div class="adp-price">$${ad.price.toFixed(2)}</div>
                            </div>
                        </a>
                    </div>
                `;
            }
        },

        /**
         * Renderizar estrellas
         */
        renderStars: function(rating) {
            const fullStars = Math.floor(rating);
            let stars = '';

            for (let i = 0; i < fullStars; i++) {
                stars += '⭐';
            }

            if (rating - fullStars >= 0.5) {
                stars += '⭐';
            }

            return stars;
        },

        /**
         * Trackear impresión
         */
        trackImpression: function(adId) {
            // Evitar trackear múltiples veces
            if (this.impressionTracked.has(adId)) return;

            this.impressionTracked.add(adId);

            const apiUrl = this.config.api_url + 'track/impression';

            fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    ad_id: adId
                })
            }).catch(error => {
                console.error('AdPlatform tracking error:', error);
            });
        },

        /**
         * Trackear click
         */
        trackClick: function(adId) {
            const apiUrl = this.config.api_url + 'track/click';

            fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    ad_id: adId
                })
            }).catch(error => {
                console.error('AdPlatform tracking error:', error);
            });
        },

        /**
         * Extraer keywords de la página
         */
        extractPageKeywords: function() {
            const keywords = [];

            // Título
            const title = document.title;
            if (title) {
                keywords.push(...title.toLowerCase().split(/\s+/).slice(0, 5));
            }

            // Meta keywords
            const metaKeywords = document.querySelector('meta[name="keywords"]');
            if (metaKeywords) {
                keywords.push(...metaKeywords.content.toLowerCase().split(',').map(k => k.trim()));
            }

            // Meta description
            const metaDesc = document.querySelector('meta[name="description"]');
            if (metaDesc) {
                keywords.push(...metaDesc.content.toLowerCase().split(/\s+/).slice(0, 10));
            }

            // Filtrar keywords cortos y duplicados
            return [...new Set(keywords.filter(k => k.length > 3))].slice(0, 10).join(',');
        },

        /**
         * Obtener tipo de dispositivo
         */
        getDeviceType: function() {
            const ua = navigator.userAgent;

            if (/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i.test(ua)) {
                return 'tablet';
            }
            if (/Mobile|Android|iP(hone|od)|IEMobile|BlackBerry|Kindle|Silk-Accelerated|(hpw|web)OS|Opera M(obi|ini)/.test(ua)) {
                return 'mobile';
            }
            return 'desktop';
        },

        /**
         * Obtener código de país (si está disponible en localStorage o cookie)
         */
        getCountryCode: function() {
            // Intentar desde localStorage
            const storedCountry = localStorage.getItem('adp_country');
            if (storedCountry) return storedCountry;

            // Aquí podrías integrar con un servicio de geolocalización
            return '';
        },

        /**
         * Formatear número
         */
        formatNumber: function(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            }
            if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num.toString();
        },

        /**
         * Escapar HTML
         */
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // Auto-inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            AdPlatformSDK.init();
        });
    } else {
        AdPlatformSDK.init();
    }

    // Exponer globalmente para uso manual
    window.AdPlatformSDK = AdPlatformSDK;

})();
