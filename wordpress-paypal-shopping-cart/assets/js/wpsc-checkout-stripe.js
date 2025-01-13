jQuery(document).ready(function(){
    jQuery(".wspsc-stripe-payment-form").on("submit",function(e){
        e.preventDefault();
    });    
    
    jQuery('.wspsc_stripe_btn').on('click',function(e) {
        e.preventDefault();
        
        let isAnyValidationError = false;
        
        // Validate shipping region.
        if (!wspsc_validateShippingRegion(e.target)) {   
            isAnyValidationError = true       
        }
        // Validate terms and conditions.
        if (!wspsc_validateTnc(e.target)) {   
            isAnyValidationError = true       
        }

        if (isAnyValidationError) {
            // There is a validation error, don't proceed to checkout.
            return;
        }

        var form =jQuery(this).closest('.wspsc-stripe-payment-form');
        var requiredFields = jQuery(this).closest("table").find('.wpspsc_cci_input').filter("[required]:visible");
        var isValid = true;
        if(requiredFields)
        {
            requiredFields.each(function() {
                var field = jQuery(this);
                if (field.val().trim() === '') {
                    isValid = false;                
                }
            });
        }        

        if(!isValid)
        {
            return;
        }

        var spinnerContainer = jQuery(this).closest('.wspsc-stripe-payment-form').find('.wpspsc-spinner-cont');
        
        spinnerContainer.css('display', 'inline-block');
        jQuery(this).hide();
                
        var payload = {
            'action': 'wpsc_stripe_create_checkout_session'
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