(function($) {
    'use strict';

    $("#delivery_time").on('click', function() {
        var parent = $(this).closest('.delivery_details');
        var prodID = $(this).attr('data-id');
        var text = $('#delivery_time_desc', parent).text();
        if(text != ''){
            if($('#delivery_time_desc', parent).is(':visible')){
                $('#delivery_time_desc', parent).hide();
            }
            else{
                $('#delivery_time_desc', parent).show();
            }
            return;
        }
        var data = {
             action: 'deliveryTimeDesc',
             id:prodID
        }
        $.ajax({
            type: 'post',
            url: delivery_time.ajaxurl,
            data: data,
            beforeSend: function() {    
                jQuery('#delivery_time_desc').addClass('processing');
            },
            success: function(response) {
                jQuery('#delivery_time_desc').removeClass('processing');
                jQuery('#delivery_time_desc').text(response.data);

            },
            error: function(err) {
                jQuery('#delivery_time_desc').text(err.data);
            }
        });
    
        return false;
    });
})(jQuery);