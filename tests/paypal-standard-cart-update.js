// Run: node tests/paypal-standard-cart-update.js
// Exercise the real AJAX updater: visible totals and submitted fields must agree.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
function form(values) {
    const result = {
        inputs: [],
        querySelectorAll() { return [...this.inputs]; },
        appendChild(input) { input.owner = this; this.inputs.push(input); }
    };
    for (const [name, value] of Object.entries(values)) {
        const input = {
            name, value: String(value),
            remove() { this.owner.inputs.splice(this.owner.inputs.indexOf(this), 1); },
            cloneNode() { return {...this, owner: undefined}; }
        };
        result.appendChild(input);
    }
    return result;
}
const original = form({item_name_1: 'Tea', amount_1: '10.00', quantity_1: 1, item_number_1: 'TEA', shipping_1: 5, tax_cart: 1, business: 'seller@example.test', custom: 'cart-id', cmd: '_cart', upload: 1});
let incoming;
let bodyReplaced = false;
const newBody = {};
const cart = {querySelector(selector) {
    if (selector === 'table > tbody') return {replaceWith(body) { assert.equal(body, newBody); bodyReplaced = true; }};
    if (selector === '.wpsc_checkout_form_standard') return original;
    return null;
}};
const context = {document: {
    addEventListener() {}, querySelectorAll() { return []; },
    createElement() { return {content: {querySelector(selector) {
        return selector === 'table > tbody' ? newBody : incoming;
    }}}; }
}};
vm.createContext(context);
vm.runInContext(fs.readFileSync(path.join(__dirname, '../wordpress-paypal-shopping-cart/assets/js/wpsc-product-shortcode.js'), 'utf8') + '\nthis.updateCart = WPSCAddToCartForm.prototype.updateCart;', context);
function update(values) {
    incoming = form(values);
    context.updateCart(cart, '<div>updated cart</div>');
    assert.ok(bodyReplaced);
    const inputs = original.inputs;
    assert.equal(new Set(inputs.map(input => input.name)).size, inputs.length, 'No duplicate submitted fields');
    const fields = Object.fromEntries(inputs.map(input => [input.name, input.value]));
    assert.equal(fields.business, 'seller@example.test');
    assert.equal(fields.custom, 'cart-id');
    assert.equal(fields.cmd, '_cart');
    assert.equal(fields.upload, '1');
    return fields;
}
const next = {item_name_1: 'Tea', amount_1: '10.00', quantity_1: 2, item_number_1: 'TEA', item_name_2: 'Biscuits', amount_2: '7.50', quantity_2: 3, item_number_2: 'BISCUITS', shipping_1: 6, tax_cart: 2};
let fields = update(next);
assert.equal(Number(fields.amount_1) * Number(fields.quantity_1) + Number(fields.amount_2) * Number(fields.quantity_2) + Number(fields.shipping_1) + Number(fields.tax_cart), 50.5);
assert.equal(fields.item_name_2, 'Biscuits');
assert.equal(fields.item_number_2, 'BISCUITS');
update(next); // Repeated AJAX updates must not accumulate fields.
fields = update({item_name_1: 'Biscuits', amount_1: '7.50', quantity_1: 1, item_number_1: 'BISCUITS', shipping_1: 0, no_shipping: 1});
assert.equal(fields.amount_2, undefined, 'Removed items must not remain payable');
assert.equal(fields.tax_cart, undefined, 'Removed tax must not remain payable');
assert.equal(fields.shipping_1, '0');
assert.equal(fields.no_shipping, '1');
console.log('PayPal Standard AJAX item, quantity, shipping, tax, removal, and duplicate-field checks passed.');
