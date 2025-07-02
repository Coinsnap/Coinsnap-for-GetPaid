jQuery(function ($) {
    
    let connectionCheckElement = '';
    
    if($('.nav-tab-wrapper').length){
        connectionCheckElement = '.nav-tab-wrapper';
    }
    
    
    
    if($('input#wpinv_settings\\[save_gateway\\]').length && $('input#wpinv_settings\\[save_gateway\\]').val() === 'coinsnap'){
        $('#wpinv_settings\\[save_gateway\\]').after('<div id="coinsnapConnectionStatus"></div>');
    }
    
    if(connectionCheckElement !== ''){
    
        let ajaxurl = coinsnapgp_ajax['ajax_url'];
        let data = {
            action: 'coinsnapgp_connection_handler',
            _wpnonce: coinsnapgp_ajax['nonce']
        };

        jQuery.post( ajaxurl, data, function( response ){

            connectionCheckResponse = $.parseJSON(response);
            let resultClass = (connectionCheckResponse.result === true)? 'success' : 'error';
            $connectionCheckMessage = '<div id="coinsnapConnectionTopStatus" class="message '+resultClass+' notice edd-notice" style="margin-top: 10px;"><p>'+ connectionCheckResponse.message +'</p></div>';

            $(connectionCheckElement).after($connectionCheckMessage);

            if($('#coinsnapConnectionStatus').length){
                $('#coinsnapConnectionStatus').html('<span class="'+resultClass+'">'+ connectionCheckResponse.message +'</span>');
            }
        });
    }
    
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }

    function setCookie(name, value, days) {
        const expDate = new Date(Date.now() + days * 86400000);
        const expires = "expires=" + expDate.toUTCString();
        document.cookie = name + "=" + value + ";" + expires + ";path=/";
    }
});

