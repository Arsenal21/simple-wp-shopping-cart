// Run: node tests/paypal-render-timing.js
// Execute the actual PHP-generated scripts with controlled SDK, DOM, and AJAX timing.
const assert = require('node:assert/strict');
const {execFileSync} = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const scripts = new Map();
function script(cart) {
    if (!scripts.has(cart)) {
        const html = execFileSync('php', [path.join(__dirname, 'fixtures/paypal-render.php'), String(cart)], {encoding: 'utf8'});
        scripts.set(cart, html.match(/<script type="text\/javascript">([\s\S]*?)<\/script>/)[1]);
    }
    return scripts.get(cart);
}
function environment({dom = true, sdk = true, cartReady = true} = {}) {
    const listeners = new Map();
    const nodes = new Map();
    const renders = [];
    const errors = [];
    const requests = [];
    const document = {
        readyState: dom ? 'complete' : 'loading',
        getElementById: id => nodes.get(id) || null,
        querySelectorAll: () => [],
        addEventListener(name, callback) {
            if (!listeners.has(name)) listeners.set(name, new Set());
            listeners.get(name).add(callback);
        },
        removeEventListener(name, callback) { listeners.get(name)?.delete(callback); },
        dispatchEvent(event) { [...(listeners.get(event.type) || [])].forEach(callback => callback(event)); }
    };
    const context = {
        document, Event: class {constructor(type) {this.type = type;}},
        console: {log() {}, error(...args) { errors.push(args); }},
        wpscCartReady: cartReady,
        wpsc_validateTnc: () => true,
        wpsc_validateShippingRegion: () => true,
        wpsc_validateTaxRegion: () => true,
        fetch: async (url, request) => {
            requests.push(request);
            return {json: async () => ({order_id: 'test-order'})};
        },
        alert(message) {throw new Error(message);}
    };
    context.window = context;
    function loadSDK() {
        context.wpsc_paypal = {Buttons(options) {
            if (context.throwOnButtons) throw context.throwOnButtons;
            return {render(node) {
                options.onInit({}, {enable() {}, disable() {}});
                return new Promise((resolve, reject) => renders.push({node, options, resolve, reject}));
            }};
        }};
    }
    if (sdk) loadSDK();
    vm.createContext(context);
    return {
        context, document, renders, errors, requests, loadSDK,
        emit: type => document.dispatchEvent({type}),
        run: cart => vm.runInContext(script(cart), context),
        mount(cart) {
            const id = 'wpsc_paypal_button_' + cart;
            if (nodes.has(id)) nodes.get(id).isConnected = false;
            const node = {id, isConnected: true};
            nodes.set(id, node);
            return node;
        }
    };
}
const flush = () => new Promise(resolve => setImmediate(resolve));
(async () => {
    // Run the PHP-generated SDK loader, not just a preloaded Buttons mock.
    const sdkMarkup = execFileSync('php', [path.join(__dirname, 'fixtures/paypal-render.php'), '1', 'sdk'], {encoding: 'utf8'});
    const sdkScript = sdkMarkup.match(/<script type="text\/javascript">([\s\S]*?)<\/script>/)[1];
    for (const dom of [false, true]) {
        const page = environment({dom, sdk: false});
        const injected = [];
        let foreignDestroyCount = 0;
        page.context.paypal = {__internal_destroy__() { foreignDestroyCount++; }};
        page.document.createElement = () => ({
            attributes: {},
            setAttribute(name, value) { this.attributes[name] = value; },
            remove() { this.removed = true; }
        });
        page.document.head = {appendChild(element) { injected.push(element); }};
        page.mount(1);
        page.mount(2);
        page.run(1);
        page.run(2);
        // Replayed inline loaders must share a pending request, even before DOMContentLoaded.
        vm.runInContext(sdkScript, page.context);
        vm.runInContext(sdkScript, page.context);
        if (!dom) {
            assert.equal(injected.length, 0);
            page.document.readyState = 'complete';
            page.emit('DOMContentLoaded');
        }
        assert.equal(injected.length, 1, 'Only one SDK request for both carts');
        assert.equal(page.renders.length, 0, "Do not render with another integration's SDK");
        const namespace = injected[0].attributes['data-namespace'];
        assert.equal(namespace, 'wpsc_paypal', 'Simple Cart owns its SDK namespace');
        assert.match(injected[0].src, /client-id=test-client/);
        page.loadSDK();
        injected[0].onload();
        await flush();
        assert.equal(page.renders.length, 2, 'Both carts render with the single loaded SDK');
        vm.runInContext(sdkScript, page.context);
        assert.equal(injected.length, 1, 'Already-loaded SDK must not be re-injected');
        // PayPal tears down the previous instance in the namespace it is loading into.
        page.context.paypal.__internal_destroy__();
        page.context.paypal = {Buttons() { throw new Error('Wrong SDK instance'); }};
        assert.equal(foreignDestroyCount, 1);
        page.renders.forEach(render => render.resolve());
        await flush();
        assert.equal(page.errors.length, 0, 'A different PayPal integration must not destroy Simple Cart buttons');
        assert.equal(page.renders[0].node.wpscPaypalRenderState, 'rendered');
        assert.equal(page.renders[1].node.wpscPaypalRenderState, 'rendered');
    }

    // Every ordering of DOM readiness, cart initialization, and SDK readiness.
    const orderings = [
        ['dom', 'cart', 'sdk'], ['dom', 'sdk', 'cart'], ['cart', 'dom', 'sdk'],
        ['cart', 'sdk', 'dom'], ['sdk', 'dom', 'cart'], ['sdk', 'cart', 'dom']
    ];
    for (const order of orderings) {
        const e = environment({dom: false, sdk: false, cartReady: false});
        e.mount(1);
        e.run(1);
        assert.equal(e.renders.length, 0);
        order.forEach((ready, index) => {
            if (ready === 'dom') {
                e.document.readyState = 'complete';
                e.emit('DOMContentLoaded');
            } else if (ready === 'cart') {
                e.context.wpscCartReady = true;
                e.emit('wpsc_cart_ready');
            } else {
                e.loadSDK();
                e.emit('wpsc_paypal_sdk_loaded');
            }
            assert.equal(e.renders.length, index === 2 ? 1 : 0, order.join(' -> '));
        });
    }
    // Cached SDK/late inline script, duplicate events and repeated script evaluation.
    const e = environment();
    const first = e.mount(1);
    e.run(1);
    e.run(1);
    e.emit('wpsc_paypal_sdk_loaded');
    e.emit('wpsc_after_cart_shortcode_script_eval');
    assert.equal(e.renders.length, 1);
    assert.equal(first.wpscPaypalRenderState, 'rendering');
    e.renders[0].resolve();
    await flush();
    assert.equal(first.wpscPaypalRenderState, 'rendered');
    e.emit('wpsc_after_cart_shortcode_script_eval');
    assert.equal(e.renders.length, 1);

    // Replacement element with the same ID must render; old closures must not claim it.
    const replacement = e.mount(1);
    e.emit('wpsc_after_cart_shortcode_script_eval');
    assert.equal(e.renders.length, 1);
    e.run(1);
    assert.equal(e.renders.length, 2);
    assert.equal(e.renders[1].node, replacement);

    // Distinct carts retain their own createOrder IDs and nonces.
    e.mount(2);
    e.run(2);
    await e.renders[1].options.createOrder();
    await e.renders[2].options.createOrder();
    assert.match(e.requests[0].body, /"cart_id":"cart-1"/);
    assert.match(e.requests[0].body, /nonce-wpsc_paypal_button_1/);
    assert.match(e.requests[1].body, /"cart_id":"cart-2"/);
    assert.match(e.requests[1].body, /nonce-wpsc_paypal_button_2/);

    // Rejections retain the underlying error and do not permanently block a retry.
    const failure = new Error('SDK render failure');
    e.renders[1].reject(failure);
    await flush();
    assert.equal(replacement.wpscPaypalRenderState, undefined);
    assert.equal(e.errors[0][1], failure);
    e.emit('wpsc_after_cart_shortcode_script_eval');
    assert.equal(e.renders.length, 4);

    // Synchronous SDK errors are handled as well as promise rejections.
    const sync = environment();
    const syncNode = sync.mount(1);
    sync.context.throwOnButtons = failure;
    sync.run(1);
    assert.equal(syncNode.wpscPaypalRenderState, undefined);
    assert.equal(sync.errors[0][1], failure);
    delete sync.context.throwOnButtons;
    sync.emit('wpsc_paypal_sdk_loaded');
    assert.equal(sync.renders.length, 1);

    // Deferred script before its markup: wait until the container exists.
    const absent = environment({dom: false});
    absent.run(1);
    assert.equal(absent.renders.length, 0);
    absent.mount(1);
    absent.document.readyState = 'complete';
    absent.emit('DOMContentLoaded');
    assert.equal(absent.renders.length, 1);

    // Real cart script initializes metadata and announces readiness, even if loaded late.
    const cartSource = fs.readFileSync(path.join(__dirname, '../wordpress-paypal-shopping-cart/assets/js/wpsc-cart-script.js'), 'utf8');
    for (const dom of [false, true]) {
        const cart = environment({dom, cartReady: false});
        Object.assign(cart.context, {
            wp: {i18n: {__: text => text}},
            wpscIsTncEnabled: false, wpscIsShippingRegionEnabled: false,
            wpscIsTaxRegionEnabled: false, wpscIsStorePickupEnabled: false
        });
        cart.mount(1);
        cart.run(1);
        vm.runInContext(cartSource, cart.context);
        if (!dom) {
            assert.equal(cart.renders.length, 0);
            cart.document.readyState = 'complete';
            cart.emit('DOMContentLoaded');
        }
        assert.equal(cart.renders.length, 1);
    }
    console.log('PayPal readiness permutations, duplicate events, AJAX replacement, multiple carts, and render failure checks passed.');
})().catch(error => { console.error(error); process.exitCode = 1; });
