# Plugin Tests: what is covered

This file lists what the **Plugin Tests (Simple Cart Main)** GitHub Actions workflow checks, and what isn't covered yet.
Update it in the same commit when you add or change a test.

## How to run

GitHub > Actions > **Plugin Tests (Simple Cart Main)** > Run workflow. Choose the branch and tick the test groups to run (all are ticked by default).
The workflow file is [.github/workflows/plugin-tests-main.yml](../.github/workflows/plugin-tests-main.yml).
Run it before a release and after bigger changes.

A separate, light workflow, [.github/workflows/php-compatibility-test.yml](../.github/workflows/php-compatibility-test.yml), runs on every push and pull request to master
(it activates the plugin and runs a few shortcodes). Keep it fast; full tests go in the Plugin Tests workflow.

| Group | What it is | PHP versions | Files |
|---|---|---|---|
| Lint | PHP syntax check of the plugin and the test files (the Stripe PHP library in `lib/stripe-gateway` is skipped) | 7.4, 8.5 | (in the workflow) |
| Standalone tests | The older test scripts: no database, WordPress and the payment requests are stubbed | 7.4, 8.4 (Node 20) | `tests/*.php`, `tests/*.js` |
| PHPUnit | Tests with the WordPress test library and a real database | 7.4, 8.4 | [phpunit/](phpunit/) |

Which group a new test belongs in:
- Plugin functions and classes that need WordPress and the database: **PHPUnit**.
- The JavaScript in `assets/js` and the inline `ReadForm()` script: a **standalone** Node test (they run the real code in a fake DOM).

PHPUnit converts PHP notices and warnings into test failures, so a notice in the plugin code fails the test that runs it.
The test files must stay PHP 7.4-compatible (the oldest PHP version in the workflow).

The PHPUnit bootstrap sets the currency (USD, `$`) before the plugin loads, because the plugin reads the symbol into the
`WP_CART_CURRENCY_SYMBOL` constant at load time. It then runs the plugin's activation on `init`.

---

## Standalone tests

Each one can also be run by hand from the repository folder.

- **paypal-recipient.php** (`php tests/paypal-recipient.php`): the PayPal payment notification is only accepted when it was paid to the
  PayPal email address in the settings, and the debug messages name the field that didn't match.
- **plugin-check-output.php** (`php tests/plugin-check-output.php /path/to/wordpress`): the product form and the admin form fields survive
  the plugin's KSES allowlists unchanged; `javascript:` links, event handlers and `<script>` are removed. Uses the real WordPress KSES code.
- **product-form-events.js** (`node tests/product-form-events.js`): the selected variation is added to the product name, for a normal and an AJAX add to cart.
- **paypal-render-timing.js** (`node tests/paypal-render-timing.js`): the PayPal buttons render whichever order the PayPal SDK and the cart load in,
  also after an AJAX cart update, with several carts on a page, and when the render fails.
- **paypal-standard-cart-update.js** (`node tests/paypal-standard-cart-update.js`): after an AJAX add to cart, the PayPal Standard checkout form
  gets the new items, quantities, shipping and tax (and loses removed items), without duplicate fields.

## PHPUnit ([phpunit/tests/](phpunit/tests/))

**Plugin smoke test** (test-plugin-smoke.php)
- The plugin loads; the `WP_CART_VERSION` constant matches the `Version:` plugin header.
- Activation saves the default settings (cart title and empty text, buyer and seller emails, Stripe shipping countries) and the private key.
- Activating again (as a plugin update does) keeps the saved settings, the private key and the activation time.
- A return URL that is already set is kept, and no Thank You page is created.
- The front-end shortcodes are registered; the orders post type is registered in wp-admin.
- `[wp_cart_button]` shows the add to cart form (nonce, product name, price, shipping, item number) and the variation dropdown
  (`Large::5` shows "Large +$5"); it shows an error without a name or a price, or with `<`, `>` in the name.
- `[wp_cart_display_product]` shows the name, the description, the formatted price and the form.
- The empty cart: `[wpsc_show_shopping_cart]` shows nothing, `[wpsc_always_show_shopping_cart]` shows the empty cart text, and the compact carts show 0 items.
- `[wpsc_thank_you]` shows an error without a cart ID, and with a wrong nonce.

---

## Known issues (tests skipped until fixed)

A test for a known bug can be written before the fix and skipped, so the workflow stays green. The commit that fixes the bug turns its test on
by removing the `markTestSkipped()` line. Skipped tests show as skipped in the PHPUnit output.

- **The Thank You page is not created on a new install** (`test_activation_creates_the_thank_you_page_on_a_new_install`).
  `wpsc_run_activation()` saves the site URL as `cart_return_from_paypal_url` first, then `wpsc_check_and_create_thank_you_page()` only creates
  the page when that setting is empty, so it never does. Buyers are sent back to the home page after a payment instead of the order summary.
  Since 2024-11 (the `[wpsc_thank_you]` shortcode).

## Decisions (behavior that is kept on purpose)

None yet.

---

## Not covered yet (ideas for new tests)

Phase B (PHPUnit)
- Variation parsing (`wpsc_get_variation_string_parts()`) and the variation price and tamper check (`get_variation_price()`).
- Saving and loading the cart (`save_cart_to_postmeta()`, `get_cart_from_postmeta()`), including the object injection payload fixed in 5.3.4 and the cart ID cookie check.
- Products are only registered from published content by an Editor or Administrator (not drafts or previews).
- Cart totals: subtotal, shipping (base, per item, free shipping threshold), shipping and tax by region, coupons, price formatting.

Phase C (endpoint tests on a test site)
- Add to cart requests (normal and AJAX): a wrong nonce, tampered price, shipping or variation.
- PayPal payment notifications with a fake PayPal verification: the recipient email check, a wrong amount, a repeated notification.
- Stripe checkout with a fake Stripe API.
