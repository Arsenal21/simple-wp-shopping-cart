/**
 * NOTE: The wspscIsTncEnabled variable will be added by a wp_add_inline_script if terms and conditions is enabled.
 * 
 * @var {boolean} wspscIsTncEnabled If terms and condition is enabled or not.
 */

var wspscPaypalStandardCheckoutForms = document.querySelectorAll('.wspsc_checkout_form_standard');
var wspscTncCheckboxes = document.querySelectorAll('.wp_shopping_cart_tnc_input');

var wspscTncContainerSelector = '.wp-shopping-cart-tnc-container';
var wspscTncInputSelector = '.wp_shopping_cart_tnc_input';
var wspscTncErrorDivSelector = '.wp-shopping-cart-tnc-error';

document.addEventListener('DOMContentLoaded', function () {

	// Check if standard paypal checkout form is enabled.
	if (wspscPaypalStandardCheckoutForms !== null) {
		wspscPaypalStandardCheckoutForms.forEach(form => {
			form.addEventListener('submit', function (e) {
				// Check if terms and condition enabled, if so then validate.
				if (wspscIsTncEnabled) {
					if (!wspsc_validateTnc(form)) {
						e.preventDefault();
					}
				}
			})
		})
	}

	// Check if terms and conditions in enabled and validate that when the input changes.
	if (wspscIsTncEnabled) {
		wspscTncCheckboxes.forEach(checkbox => {
			checkbox.addEventListener('change', function () {
				const tncContainer = wspsc_getClosestElement(checkbox, wspscTncContainerSelector);
				wspsc_handleTncErrorMsg(tncContainer);
			});
		})
	}
})

/**
 * Check whether the terms and conditions is checked or not.
 * 
 * @param {HTMLFormElement|string} context The element/selector to use to validate the terms and conditions within/closest.
 * @param {boolean} showErrorMsg Whether to populate error messages or not.
 * 
 * @return {boolean}
 */
function wspsc_validateTnc(context, showErrorMsg = true) {
	if (wspscIsTncEnabled) {
		const tncContainer = wspsc_getClosestElement(context, wspscTncContainerSelector)

		if (showErrorMsg) {
			wspsc_handleTncErrorMsg(tncContainer);
		}

		const tncCheckbox = tncContainer.querySelector(wspscTncInputSelector);

		return tncCheckbox.checked ? true : false;
	}

	// Return true so that if the terms and condition is not enabled, validation doesn't fail.
	// Code won't reach here if terms and cond enabled.
	return true;
}

/**
 * Populate terms and conditions validation error message.
 * 
 * @param {HTMLElement} tncContainer The container element of the target tnc to display errors.
 */
function wspsc_handleTncErrorMsg(tncContainer) {
	const tncCheckbox = tncContainer.querySelector(wspscTncInputSelector);
	const tncErrorDiv = tncContainer.querySelector(wspscTncErrorDivSelector);

	if (tncCheckbox.checked) {
		tncErrorDiv.innerText = "";
	} else {
		tncErrorDiv.innerText = wp.i18n.__("You must accept the terms before you can proceed.", "wordpress-simple-paypal-shopping-cart");
	}
}

/**
 * Get the closest DOM element of a reference element.
 * 
 * @param {HTMLElement|string} reference The reference element to get the closest.
 * @param {string} target CSS selector of the target element to get.
 * @param {string} context CSS selector of the container element to search in.
 * 
 * @returns {HTMLElement}
 */
function wspsc_getClosestElement(reference, target, context = '.wpspsc_checkout_form') {
	let ref = null;
	if (typeof reference == 'string') {
		ref = document.querySelector(reference);
	} else {
		ref = reference;
	}

	if (ref) {
		const closestCheckoutSection = ref.closest(context);
		const targetNode = closestCheckoutSection.querySelector(target);

		return targetNode;
	}

	return null;
}