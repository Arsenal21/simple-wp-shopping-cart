// Run: node tests/product-form-events.js
// Exercise the actual ReadForm function and product event listeners without a browser.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const plugin = path.join(__dirname, '../wordpress-paypal-shopping-cart');
const php = fs.readFileSync(path.join(plugin, 'wp_shopping_cart.php'), 'utf8');
const readForm = php.match(/function ReadForm[\s\S]*?(?=\s*<\/script>)/)[0];
const source = fs.readFileSync(path.join(plugin, 'assets/js/wpsc-product-shortcode.js'), 'utf8');

for (const ajax of [false, true]) {
    const listeners = {};
    const variationListeners = {};
    const choice = { value: 'Large:5', getAttribute: key => key === 'data-display-text' ? 'Large' : null };
    const select = {
        type: 'select-one', name: 'variation1', selectedIndex: 0, options: [choice],
        addEventListener: (name, callback) => { variationListeners[name] = callback; }
    };
    const button = { disabled: false };
    const form = {
        length: 1, elements: [select], product_tmp: { value: 'Tea' }, wspsc_product: { value: 'Tea' },
        querySelector: selector => selector === 'input[name="price"]' ? { value: '10' } : button,
        querySelectorAll: () => [select],
        addEventListener: (name, callback) => { (listeners[name] ??= []).push(callback); }
    };
    let submittedName;
    const context = {
        document: { addEventListener() {}, querySelectorAll: () => [] },
        wpsc_vars: { currencySymbol: '$', ajaxAddToCartEnabled: ajax },
        FormData: class {
            constructor(target) { submittedName = target.wspsc_product.value; }
            append() {}
            delete() {}
        },
        // A pending network promise keeps this test entirely before payment/cart I/O.
        fetch: () => new Promise(() => {}),
        product: { querySelector: selector => selector === 'form.wp-cart-button-form' ? form : null }
    };
    vm.createContext(context);
    vm.runInContext(readForm + '\n' + source + '\nnew WPSCProduct(product);', context);
    variationListeners.change();
    assert.equal(form.wspsc_product.value, 'Tea (Large)', 'Variation change updates the product name');
    choice.getAttribute = key => key === 'data-display-text' ? 'Small' : null;
    let prevented = false;
    listeners.submit.forEach(callback => callback({ preventDefault() { prevented = true; } }));
    assert.equal(form.wspsc_product.value, 'Tea (Small)', 'Submit reads the current selection');
    assert.equal(prevented, ajax, 'Normal submits continue; AJAX submits are intercepted');
    if (ajax) {
        assert.equal(submittedName, 'Tea (Small)', 'AJAX serializes the updated name');
    }
}
console.log('Product variation and normal/AJAX submit checks passed.');
