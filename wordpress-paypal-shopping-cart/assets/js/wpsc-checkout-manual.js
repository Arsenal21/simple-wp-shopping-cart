/* global wpsc_ajaxUrl, wpscCheckoutManualMsg */

document.addEventListener('DOMContentLoaded', function () {
    const wpscManualCheckoutProceedBtns = document.querySelectorAll('.wpsc-manual-payment-proceed-to-checkout-btn');

    wpscManualCheckoutProceedBtns?.forEach(function (proceedBtn) {

        const paymentFormWrap = wspsc_getClosestElement(proceedBtn, '.wpsc-manual-payment-form-wrap');
        const paymentForm = paymentFormWrap?.querySelector('.wpsc-manual-payment-form');
        const paymentFormSubmitBtn = paymentForm?.querySelector('.wpsc-manual-payment-form-submit');

        // Initiate WpscManualCheckout class.
        const manualCheckout = new WpscManualCheckout(paymentForm);

        // If visitor click the 'Proceed to Manual Checkout' button, then show the manual checkout form.
        proceedBtn.addEventListener('click', () => {
            if (manualCheckout.toggleForm()) {
                proceedBtn.style.display = 'none';
            }
        })

        // If visitor click the 'Cancel' button of checkout form, then reset and hide the form.
        paymentForm?.addEventListener('reset', () => {
            manualCheckout.toggleForm();
            proceedBtn.style.display = 'inline';
        })

        paymentFormSubmitBtn?.addEventListener('click', manualCheckout.onClick);
    })

})

class WpscManualCheckout {

    validationRules = {
        'input.wpsc-manual-payment-form-fname': 'required',
        'input.wpsc-manual-payment-form-email': 'email',
        'input.wpsc-manual-payment-form-street': 'required',
        'input.wpsc-manual-payment-form-city': 'required',
        'input.wpsc-manual-payment-form-state': 'required',
    };

    constructor(paymentForm) {
        this.paymentForm = paymentForm;
    }

    /**
     * Runs when the checkout form submit button is clicked.
     */
    onClick = (e) => {
        // Triggering a custom event. Can be useful for addons.
        document.dispatchEvent( new CustomEvent('wpsc-manual-checkout-submit-button-clicked', {
            detail: {
                paymentForm: this.paymentForm,
                paymentFormButtonEvent: e
            }
        }));
    }

    /**
     * Runs when the checkout form gets submitted.
     */
    onSubmit = async (e) => {
        e.preventDefault();

        const paymentForm = e.currentTarget;
        const formData = new FormData(paymentForm);

        let isValidationSuccess = true;

        // Validate Manual Checkout Form
        if (!this.validateForm(paymentForm)) {
            isValidationSuccess = false;
        }

        // Validate Terms and conditions
        if (!wspsc_validateTnc(paymentForm)) {
            isValidationSuccess = false;
        }

        // Validate Shipping Region
        if (!wspsc_validateShippingRegion(paymentForm)) {
            isValidationSuccess = false;
        }

        // Check if there is any validation error,
        if (!isValidationSuccess){
            return;
        }

        // Get checkout form input values.
        const payload = JSON.stringify({
            nonce: formData.get('wpsc_manual_payment_form_nonce'),
            first_name: formData.get('wpsc_manual_payment_form_fname'),
            last_name: formData.get('wpsc_manual_payment_form_lname'),
            email: formData.get('wpsc_manual_payment_form_email'),
            address: {
                street: formData.get('wpsc_manual_payment_form_street'),
                city: formData.get('wpsc_manual_payment_form_city'),
                country: formData.get('wpsc_manual_payment_form_country'),
                state: formData.get('wpsc_manual_payment_form_state'),
                postal_code: formData.get('wpsc_manual_payment_form_postal_code'),
            },
        })

        const ajaxUrl = wpsc_ajaxUrl + '?action=wpsc_manual_payment_checkout';

        this.toggleLoading(); // Turn on loading animation.

        try {
            const response = await fetch(ajaxUrl, {
                method: 'post',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: payload,
            })

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.data.message);
            }

            this.toggleForm();

            // alert(result.data.message)
            console.log(result.data.message);

            if (result.data.redirect_to) {
                window.location.href = result.data.redirect_to; // Redirect to target url.
            } else {
                window.location.replace(window.location.href); // Reload current page without history.
            }

        } catch (error) {
            console.log(error);
            alert(error.message);
            this.toggleLoading(); // Turn off loading animation.
        }

    }

    toggleForm = () => {
        if (this.paymentForm.style.display !== 'none') {
            this.paymentForm.style.display = 'none';
            this.paymentForm.removeEventListener('submit', this.onSubmit);
            return false;
        }

        this.paymentForm.style.display = 'block';
        this.paymentForm.addEventListener('submit', this.onSubmit);
        return true;
    }

    toggleLoading = () => {
        this.paymentForm.classList.toggle('loading');
    }

    /**
     * Validation checkout form.
     */
    validateForm = (paymentForm) => {
        let isSuccess = true;

        const fields = paymentForm.querySelector('.wpsc-manual-payment-form-fields');

        // Adding 'change' event listener to payment form field.
        // Here, instead of attaching event listeners to every individual input elements, we are utilizing javascript 'event delegation' feature.
        fields.addEventListener('change', (e) => {
            const field = e.target;

            // Validate each of the form fields using specified roles for them.
            for (const [fieldSelector, fieldRule] of Object.entries(this.validationRules)) {
                if (field.matches(fieldSelector) && !this.validateField(field, fieldRule)) {
                    isSuccess = false;
                }
            }
        })

        // Trigger a 'change' event initially after form submission.
        // This helps to validate the form if user directly clicks the submit button without touching any field.
        fields.querySelectorAll('input,select')?.forEach(function (field) {
            field.dispatchEvent(new Event("change", {bubbles: true}));
        })

        return isSuccess;
    }

    /**
     * Validation form fields for specific rule.
     */
    validateField(field, rule) {
        const fieldContainer = field.parentElement;

        // First remove existing error if exists.
        fieldContainer.querySelector('.wpsc-manual-checkout-field-error')?.remove();

        const fieldValue = field.value.trim(); // Make sure input value does not consist only white spaces.

        let isError = false;
        let message = '';

        switch (rule) {
            case 'required':
                isError = fieldValue === '';
                message = wpscCheckoutManualMsg?.requiredError;
                break;
            case 'email':
                const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                isError = !emailRegex.test(fieldValue);
                message = wpscCheckoutManualMsg?.emailError;
                break;
        }

        // If error found, then create new error div element and attach that to target field.
        if (isError) {
            const errorMsg = document.createElement('div');
            errorMsg.className = 'wpsc-manual-checkout-field-error';
            errorMsg.textContent = message;
            fieldContainer.appendChild(errorMsg);

            return false;
        }

        return true;
    }

}