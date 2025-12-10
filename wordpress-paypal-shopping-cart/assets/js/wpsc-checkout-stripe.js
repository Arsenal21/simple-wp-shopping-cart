function wpscStripeInit(){
    jQuery(".wpsc-stripe-payment-form").on("submit",function(e){
        e.preventDefault();
    });    
    
    jQuery('.wpsc_stripe_btn').on('click',function(e) {
        e.preventDefault();
        
        let isAnyValidationError = false;
        
        // Validate shipping region.
        if (!wpsc_validateShippingRegion(e.target)) {   
            isAnyValidationError = true       
        }
        // Validate tax region.
        if (!wpsc_validateTaxRegion(e.target)) {
            isAnyValidationError = true
        }
        // Validate terms and conditions.
        if (!wpsc_validateTnc(e.target)) {
            isAnyValidationError = true       
        }

        if (isAnyValidationError) {
            // There is a validation error, don't proceed to checkout.
            return;
        }

        var form =jQuery(this).closest('.wpsc-stripe-payment-form');
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

        var spinnerContainer = jQuery(this).closest('.wpsc-stripe-payment-form').find('.wpsc-spinner-cont');
        
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

        jQuery.post(wpsc_ajax_url, payload).done(function (response) {
                if (!response.error) {
                    
                    spinnerContainer.hide();
                    jQuery(this).show();

                    wpsc_stripe_js_obj.redirectToCheckout({sessionId: response.session_id}).then(function (result) {
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
}

jQuery(document).ready(wpscStripeInit);

/**
 * This is triggered when the cart is created by ajax add to cart.
 */
document.addEventListener('wpsc_after_render_cart_by_ajax', function (e) {
    wpscStripeInit();
});