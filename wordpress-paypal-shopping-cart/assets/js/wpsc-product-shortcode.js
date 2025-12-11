/* global wpsc_vars */

class WPSCProduct {
    constructor(productOutput) {
        const addToCartForm = productOutput.querySelector('form.wp-cart-button-form');
        if (!addToCartForm) {
            return;
        }

        this.addToCartForm = addToCartForm;

        const basePriceEl = this.addToCartForm?.querySelector('input[name="price"]');
        this.basePrice = basePriceEl?.value;
        this.variationInputs = this.addToCartForm?.querySelectorAll('.wp_cart_variation1_select, .wp_cart_variation2_select, .wp_cart_variation3_select');

        this.productBox = productOutput.querySelector('.wp_cart_product_display_bottom');

        this.currencySymbol = wpsc_vars.currencySymbol;

        if (this.productBox) {
            // This is a product display box shortcode, need to render the updated price when product variation changes.
            this.showUpdatedPrice(true);
        }

        // check if ajax add to cart enabled.
        if (wpsc_vars.ajaxAddToCartEnabled) {
            new WPSCAddToCartForm(this.addToCartForm);
        }
    }

    showUpdatedPrice(isInitial = false) {
        let updatedPrice = parseFloat(this.basePrice);

        this.variationInputs?.forEach(varInput => {
            const selectedOptionEl = varInput.options[varInput.selectedIndex];

            const varPrice = selectedOptionEl?.getAttribute("data-price");
            if (varPrice) {
                // Nothing to do if no variation price set.
                updatedPrice += parseFloat(varPrice);
            }

            if (isInitial) {
                varInput.addEventListener('change', () => {
                    this.showUpdatedPrice();
                });
            }
        });

        const priceBox = this.productBox?.querySelector('.wp_cart_product_price');

        priceBox.innerText = this.getFormatterPriceStr(updatedPrice);
    }

    getFormatterPriceStr(price) {
        price = parseFloat(price);

        return this.currencySymbol + price.toFixed(2);
    }
}

class WPSCAddToCartForm {

    constructor(form) {
        this.form = form;
        
        this.submitBtn = form.querySelector('input[type="submit"]');
        if (!this.submitBtn) {
            // Its an image type btn.
            this.submitBtn = form.querySelector('input[type="image"].wp_cart_button');
        }

        this.form.addEventListener('submit', this.onSubmit);

        this.checkoutPageURL = wpsc_vars.checkoutPageURL;
    }

    getCarts() {
        return document.querySelectorAll('.wpsc_shopping_cart_container');
    }

    getCompactCarts() {
        return document.querySelectorAll('.wpsps_compact_cart');
    }

    getCompactCarts2() {
        return document.querySelectorAll('.wspsc_compact_cart2');
    }

    onSubmit = async (e) => {
        e.preventDefault();
        
        this.disableSubmitBtn(true);

        const formData = new FormData(this.form);

        // Add the ajax action query param.
        formData.append('action', 'wpsc_add_to_cart');

        formData.delete('addcart'); // Not required and also causes problem in firefox.

        // To fetch updated cart output if required.
        formData.append('getCart', this.getCarts().length);
        formData.append('getCompactCart', this.getCompactCarts().length);
        formData.append('getCompactCart2', this.getCompactCarts2().length);

        try {
            let response = await fetch(wpsc_vars?.ajaxUrl, {
                method: 'POST',
                body: formData,
            });

            if (!response.ok) {
                alert('Add to cart error!');
                console.log(response);
                return;
            }

            response = await response.json();

            // console.log(response);

            this.showResponse(response, true);

            if (!response.success) {
                throw Error(response.data.message);
            }

            // Redirect to check out page if enabled.
            if (wpsc_vars.autoRedirectToCheckoutPage && this.checkoutPageURL) {
                console.log('Redirecting to checkout page...')
                window.location.href = this.checkoutPageURL;
                return;
            }

            // Update the cart view if cart existing the same page.
            this.showUpdatedCarts(response);

            // Scroll to the cart anchor if available.
            if (wpsc_vars.shoppingCartAnchor) {
                const cartAnchor = document.querySelector('a[name="wpsc_cart_anchor"]');
                if (cartAnchor) {
                    cartAnchor.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            }

        } catch (error) {
            alert(error.message);
            console.log(error.message);
        } finally {
            this.disableSubmitBtn(false);
        }
    }

    disableSubmitBtn( state ){
        if(this.submitBtn){
            this.submitBtn.disabled = Boolean(state);
        }
    }

    showResponse(response, showCartLink = true) {
        if (response.data?.message) {
            const respDiv = this.form.querySelector('.wpsc_add_cart_response_div');
            respDiv.innerHTML = '';

            const isSuccess = response.success;
            const msg = response.data.message;
            const msgFormatted = this.getFormattedMsg(msg, isSuccess);

            respDiv.appendChild(msgFormatted);

            const cartPageUrl = wpsc_vars.checkoutPageURL.trim();
            if (response.success && showCartLink && cartPageUrl) {
                const cartLinkDiv = document.createElement('div');
                const cartLink = document.createElement('a');
                cartLink.innerText = wpsc_vars.addToCart.texts.cartLink.trim();
                cartLink.href = cartPageUrl;

                cartLinkDiv.appendChild(cartLink);
                respDiv.appendChild(cartLinkDiv);
            }

            respDiv.style.marginTop = '10px';
        }
    }

    getFormattedMsg(msg, isSuccess = true) {
        const msgEl = document.createElement('div');
        if (isSuccess) {
            msgEl.classList.add('wpsc-success-message');
        } else {
            msgEl.classList.add('wpsc-error-message');
        }

        msgEl.innerText = String(msg);
        // msgEl.innerHTML = msg;

        return msgEl;
    }

    showUpdatedCarts(response) {
        const response_data = response.data;

        // Update existing or show new cart.
        this.getCarts().forEach((cart, i) => {
            const cartContent = cart.querySelector('.shopping_cart');

            const newCartShortCodeOutput = response_data.cart_shortcode_output[i];
            if (! cartContent) {
                // The cart hasn't rendered yet. (empty cart, or no product was added before)
                this.renderCart(cart, newCartShortCodeOutput);
                return;
            }

            // The cart has rendered already, just update it.
            this.updateCart(cartContent, newCartShortCodeOutput);
        })

        // Update compact cart
        this.getCompactCarts().forEach((cart) => {
            this.updateCompactCart(cart, response_data);
        })

        // Update compact cart 2
        this.getCompactCarts2().forEach((cart) => {
            this.updateCompactCart2(cart, response_data);
        })
    }

    renderCart(cart, newCartShortCodeOutput) {
        cart.innerHTML = newCartShortCodeOutput;

        document.dispatchEvent(new CustomEvent('wpsc_after_render_cart_by_ajax', {
            detail: {
                cart: cart,
            }
        }));

        cart.querySelectorAll("script").forEach(oldScript => {
            const newScript = document.createElement("script");

            // For External script
            // if (oldScript.src) {
            //    newScript.src = oldScript.src;
            // }

            // For Inline script
            newScript.textContent = oldScript.textContent;

            // Executes the script now
            oldScript.replaceWith(newScript);
        });

        document.dispatchEvent(new CustomEvent('wpsc_after_cart_shortcode_script_eval', {
            detail: {
                cart: cart,
            },
            cancelable: true,
        }));

    }

    updateCart(cartContent, cartOutput) {
        const newCartShortCodeOutput = document.createElement('template');
        newCartShortCodeOutput.innerHTML = cartOutput.trim();

        const newCartTableBody = newCartShortCodeOutput.content.querySelector('table > tbody');

        const cartTableBody = cartContent.querySelector('table > tbody');

        const cartActionMsg = cartContent.querySelector('.wpsc_cart_action_msg');
        cartActionMsg?.remove();

        if (cartTableBody) {
            cartTableBody.replaceWith(newCartTableBody);
        }
    }

    updateCompactCart(cart, response_data) {
        const newCartOutput = response_data.compact_cart_shortcode_output;
        if (newCartOutput) {
            const template = document.createElement('template');
            template.innerHTML = newCartOutput;

            cart.replaceWith(template.content);
        }
    }

    updateCompactCart2(cart, response_data) {
        const newCartOutput = response_data.compact_cart2_shortcode_output;
        if (newCartOutput) {
            const template = document.createElement('template');
            template.innerHTML = newCartOutput;

            cart.replaceWith(template.content);
        }
    }
}


document.addEventListener('DOMContentLoaded', function () {
    const products = document.querySelectorAll('.wpsc_product');
    products.forEach((product) => {
        new WPSCProduct(product);
    });
})
