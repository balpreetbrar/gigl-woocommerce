document.addEventListener("DOMContentLoaded", function () {

    if (typeof jQuery !== "undefined") {
        jQuery(function ($) {

            $('select[name^="action"]').append(
                $('<option>')
                    .val('update_order_status')
                    .text(gigl_admin.bulk_action_label)
            );

        });
    }

}); 
