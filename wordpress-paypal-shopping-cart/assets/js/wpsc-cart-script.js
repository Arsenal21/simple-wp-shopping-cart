/* global wp, wspscIsTncEnabled, wspscIsShippingRegionEnabled, wpscIsTaxRegionEnabled, wpscShippingRegionOptions, wpscCheckoutCartMsg*/

/**
 * NOTE: The following variables will be added by a wp_add_inline_script when certain conditions are met.
 * 
 * External variables:
 * @var {boolean} wspscIsTncEnabled If terms and condition is enabled or not.
 * @var {boolean} wspscIsShippingRegionEnabled If shipping region is enabled or not.
 * @var {array} wpscShippingRegionOptions Available shipping region options if shipping region is enabled or empty array.
 *
 * @var {boolean} wpscIsTaxRegionEnabled If tax region enabled or not.
 * @var {array} wpscTaxRegionOptions Available tax region options if tax region enabled or empty array.
 */

const { __ } = wp.i18n;

var wpscPaypalStandardCheckoutFormSelector = '.wspsc_checkout_form_standard';

var wpscTncCheckboxSelector = '.wp_shopping_cart_tnc_input';

var wspscTncContainerSelector = '.wp-shopping-cart-tnc-container';
var wspscTncInputSelector = '.wp_shopping_cart_tnc_input';
var wspscTncErrorDivSelector = '.wp-shopping-cart-tnc-error';

var wpscShippingRegionContainerSelector = '.wpsc-shipping-region-container';
var wpscShippingRegionInputSelector = '.wpsc-shipping-region-input';
var wpscShippingRegionErrorDivSelector = '.wpsc-shipping-region-error';
var wpscShippingRegionInputs = document.querySelectorAll(wpscShippingRegionInputSelector);
var wpscShippingRegionInputElementsMeta = {};

var wpscTaxRegionContainerSelector = '.wpsc-tax-region-container';
var wpscTaxRegionInputSelector = '.wpsc-tax-region-input';
var wpscTaxRegionErrorDivSelector = '.wpsc-tax-region-error';
var wpscTaxRegionInputs = document.querySelectorAll(wpscTaxRegionInputSelector);
var wpscTaxRegionInputElementsMeta = {};

document.addEventListener('DOMContentLoaded', function () {

	const wpscPaypalStandardCheckoutForms = document.querySelectorAll(wpscPaypalStandardCheckoutFormSelector);
	// Check PayPal standard checkout form submission.
	wpscPaypalStandardCheckoutForms?.forEach(form => {
			form.addEventListener('submit', function (e) {
				// Check if terms and condition enabled, if so then validate.
				if (wspscIsTncEnabled) {
					if (!wspsc_validateTnc(form)) {
						e.preventDefault();
					}
				}
				
				// Check if shipping region enabled, if so then validate.
				if (wspscIsShippingRegionEnabled) {
					if (!wspsc_validateShippingRegion(form)) {
						e.preventDefault();
					}
				}

				// Check if tax region enabled, if so then validate.
				if (wpscIsTaxRegionEnabled) {
					if (!wpsc_validateTaxRegion(form)) {
						e.preventDefault();
					}
				}
			})
	})

	// Check if terms and conditions in enabled and validate that when the input changes.
	if (wspscIsTncEnabled) {
		const wpscTncCheckboxes = document.querySelectorAll(wpscTncCheckboxSelector);
		wpscTncCheckboxes?.forEach(checkbox => {
			checkbox.addEventListener('change', function () {
				const tncContainer = wspsc_getClosestElement(checkbox, wspscTncContainerSelector);
				wspsc_handleTncErrorMsg(tncContainer);
			});
		})
	}

	if (wspscIsShippingRegionEnabled) {
		const wpscShippingRegionInputElements = document.querySelectorAll('.wpsc-shipping-region-input');
		wpscShippingRegionInputElements.forEach(element => {
			wpscShippingRegionInputElementsMeta[element.id] = {
				id: element.id,
				value: element.value,
			}
		});
	}

	if (wpscIsTaxRegionEnabled) {
		const wpscTaxRegionInputElements = document.querySelectorAll('.wpsc-tax-region-input');
		wpscTaxRegionInputElements.forEach(element => {
			wpscTaxRegionInputElementsMeta[element.id] = {
				id: element.id,
				value: element.value,
			}
		});
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
		tncErrorDiv.innerText = wpscCheckoutCartMsg?.tncError;
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


/**
 * Check whether the shipping by region is checked or not.
 * 
 * @param {HTMLFormElement|string} context The element/selector to use to validate the shipping by region within/closest.
 * @param {boolean} showErrorMsg Whether to populate error messages or not.
 * 
 * @return {boolean}
 */
function wspsc_validateShippingRegion(context, showErrorMsg = true) {
	if (wspscIsShippingRegionEnabled) {
		const shippingRegionContainer = wspsc_getClosestElement(context, wpscShippingRegionContainerSelector)
		if (showErrorMsg) {
			wspsc_handleShippingRegionErrorMsg(shippingRegionContainer);
		}

		const wpscShippingRegionInputElement = shippingRegionContainer.querySelector(wpscShippingRegionInputSelector);
		const wpscShippingRegionInputMeta = wpscShippingRegionInputElementsMeta[wpscShippingRegionInputElement.id]
		if (!wpscShippingRegionOptions.includes(wpscShippingRegionInputMeta.value)) {
			return false;
		}
	}
	// Return true so that if the shipping region is not enabled, validation doesn't fail.
	// Code won't reach here if terms and cond enabled.
	return true;
}

/**
 * Populate shipping region validation error message.
 * 
 * @param {HTMLElement} shippingRegionContainer The container element of the target shippingRegion select input to display errors.
 */
function wspsc_handleShippingRegionErrorMsg(shippingRegionContainer) {
	const shippingRegionErrorDiv = shippingRegionContainer.querySelector(wpscShippingRegionErrorDivSelector);
	const wpscShippingRegionInputElement = shippingRegionContainer.querySelector(wpscShippingRegionInputSelector);

	const wpscShippingRegionInputMeta = wpscShippingRegionInputElementsMeta[wpscShippingRegionInputElement.id]

	if (!wpscShippingRegionOptions.includes(wpscShippingRegionInputMeta.value)) {
		shippingRegionErrorDiv.innerText = wpscCheckoutCartMsg?.shippingRegionError;
	} else {
		shippingRegionErrorDiv.innerText = "";
	}
}

/**
 * Check whether the tax by region is checked or not.
 *
 * @param {HTMLFormElement|string} context The element/selector to use to validate the tax by region within/closest.
 * @param {boolean} showErrorMsg Whether to populate error messages or not.
 *
 * @return {boolean}
 */
function wpsc_validateTaxRegion(context, showErrorMsg = true) {
	if (wpscIsTaxRegionEnabled) {
		const taxRegionContainer = wspsc_getClosestElement(context, wpscTaxRegionContainerSelector)
		if (showErrorMsg) {
			wpsc_handleTaxRegionErrorMsg(taxRegionContainer);
		}

		const wpscTaxRegionInputElement = taxRegionContainer.querySelector(wpscTaxRegionInputSelector);
		const wpscTaxRegionInputMeta = wpscTaxRegionInputElementsMeta[wpscTaxRegionInputElement.id]
		if (!wpscTaxRegionOptions.includes(wpscTaxRegionInputMeta.value)) {
			return false;
		}
	}
	// Return true so that if the shipping region is not enabled, validation doesn't fail.
	// Code won't reach here if terms and cond enabled.
	return true;
}

/**
 * Populate tax region validation error message.
 *
 * @param {HTMLElement} taxRegionContainer The container element of the target taxRegion select input to display errors.
 */
function wpsc_handleTaxRegionErrorMsg(taxRegionContainer) {
	const taxRegionErrorDiv = taxRegionContainer.querySelector(wpscTaxRegionErrorDivSelector);
	const wpscTaxRegionInputElement = taxRegionContainer.querySelector(wpscTaxRegionInputSelector);

	const wpscTaxRegionInputMeta = wpscTaxRegionInputElementsMeta[wpscTaxRegionInputElement.id]

	if (!wpscTaxRegionOptions.includes(wpscTaxRegionInputMeta.value)) {
		taxRegionErrorDiv.innerText = wpscCheckoutCartMsg?.taxRegionError;
	} else {
		taxRegionErrorDiv.innerText = "";
	}
}