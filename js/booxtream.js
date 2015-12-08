jQuery(document).ready(function($) {
    // refresh storedfiles
    var refresh_storedfiles = function() {
        var data = {
            'action': 'refresh_storedfiles',
            'booxtream-post-id': $(this).data('post-id')
        };

        $('#booxtream-select-storedfiles select').prop('disabled', true);

        $.post(ajaxurl, data, function(response) {
            // replace select
            $('#booxtream-select-storedfiles').replaceWith($(response));

            // reload event
            $('a.booxtream-refresh-storedfiles').on('click', refresh_storedfiles);

            $('#booxtream-select-storedfiles select').prop('disabled', false);
        }).fail(function() {
            alert('Could not retrieve list');
            $('#booxtream-select-storedfiles select').prop('disabled', false);
        });

        return false;

    };
    $('a.booxtream-refresh-storedfiles').on('click', refresh_storedfiles);

    var refresh_exlibrisfiles = function() {
        var data = {
            'action': 'refresh_exlibrisfiles',
            'booxtream-post-id': $(this).data('post-id')
        };

        $('#booxtream-select-exlibrisfiles select').prop('disabled', true);

        $.post(ajaxurl, data, function(response) {
            // replace select
            $('#booxtream-select-exlibrisfiles').replaceWith($(response));

            // reload event
            $('a.booxtream-refresh-exlibrisfiles').on('click', refresh_exlibrisfiles);

            $('#booxtream-select-exlibrisfiles select').prop('disabled', false);
        }).fail(function() {
            alert('Could not retrieve list');
            $('#booxtream-select-exlibrisfiles select').prop('disabled', false);
        });

        return false;

    };
    $('a.booxtream-refresh-exlibrisfiles').on('click', refresh_exlibrisfiles);

    if ($('input#_bx_booxtreamable').is(':checked')) {
        $('.show_if_booxtreamable').show();
    } else {
        $('.show_if_booxtreamable').hide();
    }

    $('input#_bx_booxtreamable').change(function () {
        show_and_hide_panels();
    });

    function show_and_hide_panels() {
        var product_type = $('select#product-type').val();
        var is_virtual = $('input#_virtual:checked').size();
        var is_downloadable = $('input#_downloadable:checked').size();
        var is_booxtreamable = $('input#_bx_booxtreamable:checked').size();

        // Hide/Show all with rules
        var hide_classes = '.hide_if_booxtreamable';
        var show_classes = '.show_if_booxtreamable';

        $.each(woocommerce_admin_meta_boxes.product_types, function (index, value) {
            hide_classes = hide_classes + ', .hide_if_' + value;
            show_classes = show_classes + ', .show_if_' + value;
        });

        $(hide_classes).show();
        $(show_classes).hide();

        // Shows rules
        if (is_booxtreamable) {
            $('.show_if_booxtreamable').show();
        }

        $('.show_if_' + product_type).show();

        // Hide rules
        if (is_booxtreamable) {
            $('.hide_if_booxtreamable').hide();
        }

        $('.hide_if_' + product_type).hide();

        $('input#_manage_stock').change();
    }

});