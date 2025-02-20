/* global wpsc_ajaxUrl */

document.addEventListener('DOMContentLoaded', function (){
    const wpscManualCheckoutProceedBtns = document.querySelectorAll('.wpsc-manual-payment-proceed-to-checkout-btn');

    wpscManualCheckoutProceedBtns.forEach(function (proceedButton){
        proceedButton.addEventListener('click', function (e){
            const proceedToManualCheckoutBtn = e.target
            const paymentFormWrap = wspsc_getClosestElement( proceedToManualCheckoutBtn , '.wpsc-manual-payment-form-wrap');

            const paymentForm = paymentFormWrap?.querySelector('.wpsc-manual-payment-form');

            const isPaymentFormVisible = wpscToggleManualCheckoutForm(paymentForm);

            if (isPaymentFormVisible){
                paymentForm.addEventListener('submit', wpscManualCheckoutFormSubmitHandler)
                proceedToManualCheckoutBtn.style.display = 'none';
            } else {
                paymentForm.removeEventListener('submit', wpscManualCheckoutFormSubmitHandler);
                proceedToManualCheckoutBtn.style.display = 'inline';
            }
        })
    })


    async function wpscManualCheckoutFormSubmitHandler(e){
        e.preventDefault();
        const paymentForm = e.currentTarget;
        const formData = new FormData(paymentForm);

        let isValidationSuccess = true;

        // Validate Manual Checkout Form
        if(! wpsc_validatePaymentFormFields(paymentForm) ){
            isValidationSuccess = false;
        }

        // Validate Terms and conditions
        if(! wspsc_validateTnc(paymentForm) ){
            isValidationSuccess = false;
        }

        // Validate Shipping Region
        if(! wspsc_validateShippingRegion(paymentForm) ){
            isValidationSuccess = false;
        }

        if ( ! isValidationSuccess ){
            // There is validation errors, don't continue payment processing.
            return;
        }

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

        try {
            const response = await fetch( ajaxUrl, {
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

            alert(result.data.message)

            if (result.data.redirect_to){
                window.location.href = result.data.redirect_to;
            }

        } catch (error) {
            console.log(error);
            alert(error.message);
        }

    }

    function wpscToggleManualCheckoutForm(paymentForm){
        if ( paymentForm.style.display !== 'none' ) {
            paymentForm.style.display = 'none';
            return false;
        }

        paymentForm.style.display = 'block';
        return true;
    }

    function wpsc_validatePaymentFormFields(paymentForm){
        let isValidationSuccess = true;

        const fields = paymentForm.querySelector('.wpsc-manual-payment-form-fields');

        const fieldValidationRules = {
            'input.wpsc-manual-payment-form-fname': 'required',
            'input.wpsc-manual-payment-form-email': 'email',
            'input.wpsc-manual-payment-form-street': 'required',
            'input.wpsc-manual-payment-form-city': 'required',
            'input.wpsc-manual-payment-form-state': 'required',
        }

        // Adding 'change' event listener to payment form field.
        // Here, instead of attaching event listeners to every individual input elements, we are utilizing javascript 'event delegation' feature.
        fields.addEventListener('change', function(e){
            const field = e.target;

            for (const [fieldSelector, fieldRule] of Object.entries(fieldValidationRules)) {
                if (field.matches(fieldSelector) && !wpsc_validate(field, fieldRule)){
                    isValidationSuccess = false;
                }
            }
        })

        // Trigger a 'change' event initially after form submission.
        // This helps to validate the form if user directly clicks the submit button without touching any field.
        fields.querySelectorAll('input,select')?.forEach(function (field) {
            field.dispatchEvent(new Event("change", {bubbles: true} ));
        })

        return isValidationSuccess;
    }

    function wpsc_validate(field, rule){
        const fieldContainer = field.parentElement;

        // first remove existing error div if exists.
        fieldContainer.querySelector('.wpsc-manual-checkout-field-error')?.remove();

        const fieldValue = field.value.trim();
        let isError = false;
        let message = '';

        switch (rule){
            case 'required':
                isError = fieldValue === '';
                message = 'This field is required';
                break;
            case 'email':
                const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                isError = !emailRegex.test(fieldValue);
                message = 'The email address is not valid';
                break;
        }

        if (isError){
            const errorMsg = document.createElement('div');
            errorMsg.className = 'wpsc-manual-checkout-field-error';
            errorMsg.textContent = message;
            fieldContainer.appendChild(errorMsg);
            return false;
        }

        return true;
    }

})