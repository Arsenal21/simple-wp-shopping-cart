/**
 * global wpsc_ajaxUrl
 */

document.addEventListener('DOMContentLoaded', function () {

    const wpscResendEmailBtn = document.getElementById("wpsc-resend-sale-notification-email-btn");
    wpscResendEmailBtn?.addEventListener('click', async function ( e ){
        e.preventDefault();
        if(!confirm('Do you really want to Resend Sale Notification Email?')){
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

            if ( ! result.success){
                throw new Error(result.data.message);
            }

            alert(result.data.message);
        } catch (error) {
            console.error(error);
            alert(error.message);
        }
    })
    
})
