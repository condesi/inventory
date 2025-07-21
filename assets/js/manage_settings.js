// Funciones de gestión de configuraciones del almacén
var WarehouseSettings = {
    // Inicialización
    init: function() {
        this.bindEvents();
        this.initSelects();
        this.handleTabs();
    },

    // Eventos
    bindEvents: function() {
        $(document).on('click', '.save-settings', this.saveSettings);
        $(document).on('change', '.setting-input', this.handleSettingChange);
        $(document).on('submit', '.setting-form', this.handleFormSubmit);
    },

    // Inicializar selects
    initSelects: function() {
        // Select2 para selects largos
        $('.select-setting').select2({
            width: '100%',
            allowClear: true
        });

        // Selectpicker para selects con opciones personalizadas
        $('.selectpicker-setting').selectpicker({
            width: '100%'
        });
    },

    // Manejo de tabs
    handleTabs: function() {
        var hash = window.location.hash;
        if (hash) {
            $('a[href="' + hash + '"]').tab('show');
        }

        $('.nav-tabs a').on('click', function() {
            $(this).tab('show');
            var scrollmem = $('body').scrollTop();
            window.location.hash = this.hash;
            $('html,body').scrollTop(scrollmem);
        });
    },

    // Guardar configuraciones
    saveSettings: function(e) {
        e.preventDefault();
        var form = $(this).closest('form');
        var data = form.serialize();

        $.post(form.attr('action'), data)
            .done(function(response) {
                response = JSON.parse(response);
                if (response.success) {
                    alert_float('success', response.message);
                    // Recargar página si es necesario
                    if (response.reload) {
                        window.location.reload();
                    }
                } else {
                    alert_float('danger', response.message);
                }
            })
            .fail(function(xhr) {
                alert_float('danger', 'Error al guardar las configuraciones');
            });
    },

    // Manejar cambios en inputs de configuración
    handleSettingChange: function() {
        var input = $(this);
        var setting = input.data('setting');
        var value = input.val();

        if (input.is(':checkbox')) {
            value = input.prop('checked') ? 1 : 0;
        }

        // Actualizar valor en tiempo real si es necesario
        if (input.data('instant-save')) {
            WarehouseSettings.saveSetting(setting, value);
        }
    },

    // Guardar una configuración individual
    saveSetting: function(setting, value) {
        $.post(admin_url + 'warehouse/save_setting', {
            name: setting,
            value: value
        })
        .done(function(response) {
            response = JSON.parse(response);
            if (response.success) {
                alert_float('success', response.message);
            } else {
                alert_float('danger', response.message);
            }
        });
    },

    // Manejar envío de formularios
    handleFormSubmit: function(e) {
        e.preventDefault();
        var form = $(this);
        
        if (form.data('custom-handle')) {
            return;
        }

        var data = form.serialize();
        $.post(form.attr('action'), data)
            .done(function(response) {
                response = JSON.parse(response);
                if (response.success) {
                    alert_float('success', response.message);
                    if (response.reload) {
                        window.location.reload();
                    }
                } else {
                    alert_float('danger', response.message);
                }
            });
    },

    // Aprobaciones
    initApprovals: function() {
        $('.approval-setting-form').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var data = form.serialize();

            $.post(form.attr('action'), data)
                .done(function(response) {
                    response = JSON.parse(response);
                    if (response.success) {
                        alert_float('success', response.message);
                        if (response.reload) {
                            window.location.reload();
                        }
                    } else {
                        alert_float('danger', response.message);
                    }
                });
        });

        // Agregar nivel de aprobación
        $('.add-approval-level').on('click', function() {
            var template = $('.approval-level-template').html();
            var levelCount = $('.approval-level').length;
            template = template.replace(/{level}/g, levelCount + 1);
            $('.approval-levels').append(template);
            WarehouseSettings.initSelects();
        });

        // Eliminar nivel de aprobación
        $(document).on('click', '.remove-approval-level', function() {
            $(this).closest('.approval-level').remove();
        });
    }
};

// Inicializar cuando el documento esté listo
$(function() {
    WarehouseSettings.init();
});
