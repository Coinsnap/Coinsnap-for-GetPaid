jQuery(document).ready(function ($) {
    
    if($('#wpinv-settings-coinsnap_provider').length){
        
        setProvider();
        $('#wpinv-settings-coinsnap_provider').change(function(){
            setProvider();
        });
    }
    
    function setProvider(){
        if($('#wpinv-settings-coinsnap_provider').val() === 'coinsnap'){
            $('tr.btcpay').hide();
            $('tr.btcpay input[type=text]').removeAttr('required');
            $('tr.coinsnap').show();
            $('tr.coinsnap input[type=text]').attr('required','required');
        }
        else {
            $('tr.coinsnap').hide();
            $('tr.coinsnap input[type=text]').removeAttr('required');
            $('tr.btcpay').show();
            $('tr.btcpay input[type=text]').attr('required','required');
        }
    }
    
    function isValidUrl(serverUrl) {
        try {
            const url = new URL(serverUrl);
            if (url.protocol !== 'https:' && url.protocol !== 'http:') {
                return false;
            }
	}
        catch (e) {
            console.error(e);
            return false;
	}
        return true;
    }

    $('.btcpay-apikey-link').click(function(e) {
        e.preventDefault();
        const host = $('#wpinv-settings-btcpay_server_url').val();
	if (isValidUrl(host)) {
            let data = {
                'action': 'coinsnapgp_btcpay_server_apiurl_handler',
                'host': host,
                'apiNonce': coinsnapgp_ajax.nonce
            };
            
            $.post(coinsnapgp_ajax.ajax_url, data, function(response) {
                if (response.data.url) {
                    window.location = response.data.url;
		}
            }).fail( function() {
		alert('Error processing your request. Please make sure to enter a valid BTCPay Server instance URL.')
            });
	}
        else {
            alert('Please enter a valid url including https:// in the BTCPay Server URL input field.')
        }
    });
});

