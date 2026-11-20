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
- On a new install, activation creates the Thank You page (`[wpsc_thank_you]`) and saves it as the return URL; activating again doesn't create a second one (fixed after 5.3.4).
- A return URL that is already set is kept, and no Thank You page is created.
- The front-end shortcodes are registered; the orders post type is registered in wp-admin.
- `[wp_cart_button]` shows the add to cart form (nonce, product name, price, shipping, item number) and the variation dropdown
  (`Large::5` shows "Large +$5"); it shows an error without a name or a price, or with `<`, `>` in the name.
- `[wp_cart_display_product]` shows the name, the description, the formatted price and the form.
- The empty cart: `[wpsc_show_shopping_cart]` shows nothing, `[wpsc_always_show_shopping_cart]` shows the empty cart text, and the compact carts show 0 items.
- `[wpsc_thank_you]` shows an error without a cart ID, and with a wrong nonce.

**Variations** (test-variations.php)
- How an option is shown (`wpsc_get_variation_string_parts()`): no modifier, `+` and `-` modifiers, spaces in the name and around `::`
  (fixed in 5.3.4), a zero modifier and a modifier that isn't a number show the name only, a decimal modifier (shown as `+$2.5`).
- The separator can be changed with the `wpsc_variation_price_separator` filter.
- The price of the selected option (`get_variation_price()`), with spaces around the price.
- The tamper check stops the request (`wp_die()`): a changed price, the price removed, a trailing space, an option of another group,
  a group the product doesn't have, a product that isn't saved.
- The shortcode saves the variation groups and options of the product.

**Saved products** (test-dynamic-products.php). The add to cart request is checked against the product saved when its page is shown (5.3.3).
- A published page by an Administrator or an Editor saves the product (name, price, shipping, file URL).
- Not saved: a page by an Author or a Contributor, a draft, pending or private page, a preview, a shortcode outside a post.
- A page by an Author can't change a product an Administrator's page saved (for example its file URL).
- The `wpsc_dynamic_product_register_capability` filter changes the required capability.

**Saving and loading the cart** (test-cart-storage.php). Fixed in 5.3.4: PHP object injection (Patchstack report).
- The cart is loaded unchanged: quotes, backslashes, text that looks like serialized data, Unicode.
- The payload from the report stays plain text, and no object of the attacker's class is created.
- An object of another class in a saved cart is loaded as an incomplete class (its `__wakeup()` doesn't run).
- A cart saved by 5.3.3 (without `wp_slash()`) still loads.
- The `simple_cart_id` cookie loads the visitor's cart (32 hex characters, and the older 13 character IDs);
  other values are ignored (SQL, a path, upper case, too long, an array, empty); an unknown ID gives an empty cart.
- A cart whose saved data isn't a cart object is deleted, and the visitor gets an empty cart.

**Cart totals** (test-cart-totals.php, `calculate_cart_totals_and_postage()`)
- Subtotal, item shipping (multiplied by the quantity), base shipping, the formatted totals.
- No shipping when no item has a shipping cost (the base shipping isn't added either, for example for digital products).
- Free shipping when the subtotal is over the threshold (not when it is equal).
- Tax on the subtotal (not on the shipping); tax by region (country, state; an unknown region or no region uses the general rate).
- Shipping by region is added to the shipping (only when an item has a shipping cost); store pickup removes the shipping
  (only when it is turned on in the settings); the `wpsc_custom_postage_cost` filter.
- Price formatting and removing currency symbols from a shortcode price.

**Coupons** (test-coupons.php)
- A coupon takes a percentage off each item (not free items), rounded to the cent, and shows the total discount.
- The code isn't case-sensitive; a coupon that expires today can be used.
- Rejected without changing the prices: unknown, empty, expired, no coupons set up; only one coupon per checkout.
- When the cart changes, the coupon is applied again from the original prices (no double discount); emptying the cart removes the coupon.

---

## Known issues (tests skipped until fixed)

A test for a known bug can be written before the fix and skipped, so the workflow stays green. The commit that fixes the bug turns its test on
by removing the `markTestSkipped()` line. Skipped tests show as skipped in the PHPUnit output.

There are none at the moment. Fixed after 5.3.4: the Thank You page wasn't created on a new install.

## Decisions (behavior that is kept on purpose)

None yet.

---

## Not covered yet (ideas for new tests)

PHPUnit
- The cart output with items (`print_wp_shopping_cart()`): the item rows, totals and the checkout forms.
- The payment processing after checkout (PayPal, Stripe, manual checkout): the order, the emails, the email merge tags.

Phase C (endpoint tests on a test site)
- Add to cart requests (normal and AJAX): a wrong nonce, tampered price, shipping or variation.
- PayPal payment notifications with a fake PayPal verification: the recipient email check, a wrong amount, a repeated notification.
- Stripe checkout with a fake Stripe API.
