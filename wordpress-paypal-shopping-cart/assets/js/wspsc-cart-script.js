var wspscPaypalCheckoutFormStandard = document.querySelector('.wspsc_checkout_form_standard');
var wspscTncCheckbox = document.getElementById('wp_shopping_cart_tnc_input');
var wspscTncCheckboxErrorDiv = document.querySelector('.wp-shopping-cart-tnc-error');

document.addEventListener('DOMContentLoaded', function () {

	if (wspscPaypalCheckoutFormStandard !== null) {	
		// Handle terms and condition validation for standard checkout form on submit.
		wspscPaypalCheckoutFormStandard.addEventListener('submit', function (e) {
			if(wspscTncCheckbox !== null){
				if (!wspscValidateTnc()) {
					e.preventDefault();
				}
			}
		})
	}
	
	if(wspscTncCheckbox !== null){
		wspscTncCheckbox.addEventListener('change', handleTncErrorMsg);
	}
})

/**
 * Check whether the terms and conditions is checked or not.
 * 
 * @param {boolean} showErrorMsg Whether to populate error messages or not.
 * @return {boolean}
 */
function wspscValidateTnc(showErrorMsg = true) {
	if(wspscTncCheckbox !== null){
		if(showErrorMsg){
			handleTncErrorMsg();
		}
		
		return wspscTncCheckbox.checked ? true : false;
	}

	// Return true so that if the terms and condition is not enabled, validation doesn't fail.
	// Code won't reach here if terms and cond enabled.
	return true;
}

/**
 * Populate terms and conditions validation error message.
 */
function handleTncErrorMsg() {
	if (wspscTncCheckbox.checked) {
		wspscTncCheckboxErrorDiv.innerText = "";
	} else {
		wspscTncCheckboxErrorDiv.innerText = wp.i18n.__("You must accept the terms before you can proceed.", "wordpress-simple-paypal-shopping-cart");
	}
}
