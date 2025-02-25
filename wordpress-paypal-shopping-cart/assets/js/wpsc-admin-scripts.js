/* global wpsc_ajaxUrl, wp */

document.addEventListener('DOMContentLoaded', function () {
    const {__} = wp.i18n;
    const wpscResendEmailBtn = document.getElementById("wpsc-resend-sale-notification-email-btn");
    wpscResendEmailBtn?.addEventListener('click', async function ( e ){
        e.preventDefault();
        if(!confirm(__('Do you really want to resend sale notification email?', 'wordpress-simple-paypal-shopping-cart'))){
            return;
        }

        const order_id = wpscResendEmailBtn.getAttribute("data-order-id");
        const nonce = wpscResendEmailBtn.getAttribute("data-nonce");

        try {
            const response = await fetch(wpsc_ajaxUrl, {
                method: "post",
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'wpsc_resend_sale_notification_email', 
                    order_id,
                    nonce,
                })
            });

            const result = await response.json();

            if ( ! result.success ){
                throw new Error(result.data.message);
            }

            alert(result.data.message);
        } catch (error) {
            console.error(error);
            alert(error.message);
        }
    })



    const wpscMarkOrderConfirmedBtn = document.getElementById("wpsc-mark-order-confirm-btn");
    wpscMarkOrderConfirmedBtn?.addEventListener('click', async function ( e ){
        e.preventDefault();
        if(!confirm(__("Are you sure you want to mark this order as 'Paid'? This indicates that payment has been received for the order.", "wordpress-simple-paypal-shopping-cart"))){
            return;
        }

        const order_id = wpscMarkOrderConfirmedBtn.getAttribute("data-order-id");
        const nonce = wpscMarkOrderConfirmedBtn.getAttribute("data-nonce");

        try {
            const response = await fetch(wpsc_ajaxUrl, {
                method: "post",
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'wpsc_mark_order_confirm',
                    order_id,
                    nonce,
                })
            });

            const result = await response.json();

            if ( ! result.success){
                throw new Error(result.data.message);
            }

            alert(result.data.message);

            window.location.replace(window.location.href) // Reload current page.
        } catch (error) {
            console.error(error);
            alert(error.message);
        }
    })
})
