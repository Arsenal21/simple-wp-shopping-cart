document.addEventListener('DOMContentLoaded', function () {

	// Handle terms and condition validation for standard checkout form.
	const wspscPaypalCheckoutFormStandard = document.querySelector('.wspsc_checkout_form_standard');
	if(wspscPaypalCheckoutFormStandard === null){
		return;
	}

	const wspscTncCheckbox = document.getElementById('wp_shopping_cart_tnc_input');
	const wspscTncCheckboxErrorDiv = document.querySelector('.wp-shopping-cart-tnc-error');
	
	wspscPaypalCheckoutFormStandard.addEventListener('submit', function (e) {
		if (!wspscTncCheckbox.checked) {
			e.preventDefault();
			wspscTncCheckboxErrorDiv.innerText = wp.i18n.__("You must accept the terms before you can proceed.", "wordpress-simple-paypal-shopping-cart");
		}
	})

	wspscTncCheckbox.addEventListener('change', function (e) {
		if (wspscTncCheckbox.checked) {
			wspscTncCheckboxErrorDiv.innerText = "";
		} else {
			wspscTncCheckboxErrorDiv.innerText = wp.i18n.__("You must accept the terms before you can proceed.", "wordpress-simple-paypal-shopping-cart");
		}
	})
})