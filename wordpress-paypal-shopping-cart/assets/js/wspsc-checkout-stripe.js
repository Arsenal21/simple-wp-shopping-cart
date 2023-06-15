jQuery(document).ready(function(){
    jQuery('.wspsc_stripe_btn').on('click',function(e) {
        e.preventDefault();

        var spinnerContainer = jQuery(this).closest('.wspsc-stripe-payment-form').find('.wpspsc-spinner-cont');
        var form =jQuery(this).closest('.wspsc-stripe-payment-form');
        spinnerContainer.css('display', 'inline-block');
        jQuery(this).hide();
                
        var payload = {
            'action': 'wspsc_stripe_create_checkout_session'
        };

        var custom_field= form.find('input[name="custom"]');					
        if(custom_field)
        {						
            payload["custom"]=custom_field.val();						
        }

        jQuery.post(wspsc_ajax_url, payload).done(function (response) {
                if (!response.error) {
                    
                    spinnerContainer.hide();
                    jQuery(this).show();

                    wspsc_stripe_js_obj.redirectToCheckout({sessionId: response.session_id}).then(function (result) {
                });			
                } else {
                    alert(response.error);		
                    
                    spinnerContainer.hide();
                    jQuery(this).show();

                    return false;
                }


        }).fail(function(e) {

            spinnerContainer.hide();
            jQuery(this).show();

            alert("HTTP error occurred during AJAX request. Error code: "+e.status);						
            return false;
        });
    });
});