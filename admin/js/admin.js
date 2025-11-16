/**
 * JavaScript del Panel de Administración
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Tabs functionality
        $('.nav-tab').on('click', function(e) {
            var href = $(this).attr('href');

            // Si es un hash (tab interno)
            if (href.startsWith('#')) {
                e.preventDefault();

                var target = $(this).attr('href');

                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');

                $('.adp-tab-content').removeClass('adp-tab-active');
                $(target).addClass('adp-tab-active');
            }
        });

        // Preview de imagen de anuncio
        $('#adp_image_url').on('change', function() {
            var url = $(this).val();
            if (url) {
                // Crear preview si no existe
                if (!$('#adp-image-preview').length) {
                    $(this).after('<div id="adp-image-preview" style="margin-top: 10px;"></div>');
                }
                $('#adp-image-preview').html('<img src="' + url + '" style="max-width: 300px; max-height: 200px;">');
            }
        });

        // Confirmación para aprobar/rechazar productos en lote
        $('button[name="adp_approve_all"]').on('click', function(e) {
            if (!confirm('¿Aprobar todos los productos seleccionados?')) {
                e.preventDefault();
            }
        });

        $('button[name="adp_reject_all"]').on('click', function(e) {
            if (!confirm('¿Rechazar todos los productos seleccionados?')) {
                e.preventDefault();
            }
        });

        // Auto-save de peso manual
        $('.adp-manual-weight-input').on('change', function() {
            var $input = $(this);
            var productId = $input.data('product-id');
            var weight = $input.val();

            $.ajax({
                url: adpAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'adp_update_product_weight',
                    nonce: adpAdmin.nonce,
                    product_id: productId,
                    weight: weight
                },
                success: function(response) {
                    if (response.success) {
                        $input.css('border-color', 'green');
                        setTimeout(function() {
                            $input.css('border-color', '');
                        }, 1000);
                    }
                }
            });
        });

        // Copiar al clipboard
        $('.adp-copy-to-clipboard').on('click', function(e) {
            e.preventDefault();
            var text = $(this).data('text');

            // Crear elemento temporal
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();

            // Feedback visual
            var $btn = $(this);
            var originalText = $btn.text();
            $btn.text('¡Copiado!');
            setTimeout(function() {
                $btn.text(originalText);
            }, 2000);
        });

        // Validación de formulario de configuración
        $('form[action*="adp-settings"]').on('submit', function(e) {
            var aliexpressEnabled = $('input[name="ad_platform_aliexpress[enabled]"]').is(':checked');

            if (aliexpressEnabled) {
                var appKey = $('input[name="ad_platform_aliexpress[app_key]"]').val();
                var appSecret = $('input[name="ad_platform_aliexpress[app_secret]"]').val();
                var trackingId = $('input[name="ad_platform_aliexpress[tracking_id]"]').val();

                if (!appKey || !appSecret || !trackingId) {
                    alert('Por favor completa todos los campos de credenciales de AliExpress');
                    e.preventDefault();

                    // Cambiar a la tab de AliExpress
                    $('a[href="#aliexpress"]').click();
                    return false;
                }
            }
        });

        // Test de conexión con AliExpress API
        $('#adp-test-aliexpress-connection').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var originalText = $btn.text();

            $btn.text('Probando...').prop('disabled', true);

            $.ajax({
                url: adpAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'adp_test_aliexpress_connection',
                    nonce: adpAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('✅ Conexión exitosa con AliExpress API');
                    } else {
                        alert('❌ Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('❌ Error al conectar con la API');
                },
                complete: function() {
                    $btn.text(originalText).prop('disabled', false);
                }
            });
        });
    });

})(jQuery);
