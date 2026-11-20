# Plugin Tests: what is covered

This file lists what the **Plugin Tests (WPSC Main)** GitHub Actions workflow checks, and what isn't covered yet.
Update it in the same commit when you add or change a test.

## How to run

GitHub > Actions > **Plugin Tests (WPSC Main)** > Run workflow. Choose the branch and tick the test groups to run (all are ticked by default).
The workflow file is [.github/workflows/plugin-tests-main.yml](../.github/workflows/plugin-tests-main.yml).
Run it before a release and after bigger changes.

A separate, light workflow, [.github/workflows/php-compatibility-test.yml](../.github/workflows/php-compatibility-test.yml), runs on every push and pull request to master
(it activates the plugin and runs a few shortcodes). Keep it fast; full tests go in the Plugin Tests workflow.

| Group | What it is | PHP versions | Files |
|---|---|---|---|
| Lint | PHP syntax check of the plugin and the test files (the Stripe PHP library in `lib/stripe-gateway` is skipped) | 7.4, 8.5 | (in the workflow) |
| Standalone tests | The older test scripts: no database, WordPress and the payment requests are stubbed | 7.4, 8.4 (Node 20) | `tests/*.php`, `tests/*.js` |
| PHPUnit | Tests with the WordPress test library and a real database | 7.4, 8.4 | [phpunit/](phpunit/) |
| Endpoint tests | A real WordPress site with the plugin and a fake PayPal verification: sends a shopping session's requests over HTTP and checks the database | 7.4, 8.4 | [e2e/](e2e/) |

Which group a new test belongs in:
- Plugin functions and classes that need WordPress and the database: **PHPUnit**.
- The code runs from an incoming request (add to cart, the cart page forms, the PayPal notification, the checkout AJAX calls),
  sets cookies, or ends with `exit` or `wp_die()`: **Endpoint tests**.
- The JavaScript in `assets/js` and the inline `ReadForm()` script: a **standalone** Node test (they run the real code in a fake DOM).

PHPUnit converts PHP notices and warnings into test failures, so a notice in the plugin code fails the test that runs it.
The test files must stay PHP 7.4-compatible (the oldest PHP version in the workflow).

The PHPUnit bootstrap sets the currency (USD, `$`) before the plugin loads, because the plugin reads the symbol into the
`WP_CART_CURRENCY_SYMBOL` constant at load time. It then runs the plugin's activation on `init`.

---

## Endpoint tests ([e2e/run-endpoint-tests.php](e2e/run-endpoint-tests.php))

The site is set up with WP-CLI; [e2e/setup-site.php](e2e/setup-site.php) saves the settings (PayPal sandbox, base shipping $1, coupons,
manual checkout, the emails) and creates the coupon `E2E10` (10%), the shop page (a shirt for $20 + $2 shipping with the variations
`Small` and `Large::5`, an ebook for $9.99 with a file URL) and the cart page.
[e2e/mu-plugin-e2e-fakes.php](e2e/mu-plugin-e2e-fakes.php) answers the PayPal IPN verification (`VERIFIED` only for `verify_sign=e2e-verified`),
keeps the emails instead of sending them, blocks other outgoing HTTP requests, and has a "gadget" class that records it if an object of it is ever created.
[e2e/get-cart.php](e2e/get-cart.php) reads a saved cart with the plugin's code (`wp eval-file`).
There is no browser: the runner adds the selected variation to the product name like the `ReadForm()` script does.

**Add to cart** (twice: a normal form post, and with "AJAX add to cart" on; the two handlers are separate code)
- The shirt (Large) is added with the variation in its name and price (20 + 5); the cart ID cookie is 32 hex characters; the order is "In Progress".
- An item number with an apostrophe is saved without a backslash (fixed in 5.3.4).
- Adding it again makes the quantity 2; the ebook is a second item, digital, with the file URL of the saved product.
- Rejected, and the cart doesn't change: a wrong nonce, a lower price, a product that wasn't saved, a lower shipping cost, a variation with another price.
- The PHP object injection payload from the Patchstack report (fixed in 5.3.4): no object of another class is created when the cart is loaded,
  and the item number and stamp_pdf values stay plain text.

**Cart page**
- The PayPal Standard form: the sandbox URL, the PayPal email in the settings, USD, the items with their prices, quantities and item numbers,
  the shipping (item shipping + base shipping), the notification URL, the return URL (it resets the cart) and the cart ID in the custom field.
- Changing a quantity, removing an item, applying a coupon (not case-sensitive, 10% off); the PayPal form then has the discounted price and the coupon code.

**PayPal payment notification (IPN)**
- The notification built from the PayPal form: the plugin verifies it with the PayPal sandbox; the order is paid, with the transaction ID,
  the total, the buyer, the shipping, the coupon, the gateway and the items; the buyer and the seller get their emails.
- The same notification again sends no more emails and doesn't change the order.
- The return URL removes the cart ID cookie; the next add to cart starts a new cart; the old cart keeps its items.
- Not marking the order paid: not verified by PayPal, paid to another PayPal account, paid less than the items cost, another currency,
  a payment that isn't completed. A notification for a cart that doesn't exist is ignored. The correct one then marks the order paid.

**Manual checkout**
- Rejected without an order: a wrong nonce, an invalid email, manual checkout turned off.
- The order is placed: pending, with the buyer, the discounted total and the gateway; the buyer gets the payment instructions and the seller the notification;
  the cart ID cookie is removed; the buyer is sent to the Thank You page, which shows the order.

**PHP errors**: a PHP fatal error on the test site fails the tests; the plugin's PHP warnings and notices are listed in the output.

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

A test for a known bug can be written before the fix and skipped, so the workflow stays green. The commit that fixes the bug turns its test on:
remove the `markTestSkipped()` line (PHPUnit), or remove the issue from `KNOWN_ISSUES` in [e2e/run-endpoint-tests.php](e2e/run-endpoint-tests.php) (endpoint tests).
Skipped tests show as skipped in the PHPUnit output, and known issue checks as `KNOWN ISSUE` lines in the endpoint tests output.

Endpoint tests (in `KNOWN_ISSUES`; "suspected" until a run shows the check failing):
- **paid-cart-reused**: the cart is loaded from the `simple_cart_id` cookie without checking that its order is already paid
  (the check in `WPSC_Cart::get_cart_cpt_id()` is commented out). When a buyer pays with PayPal Standard and doesn't come back to the
  return URL, the next add to cart changes the items of the paid order, and a second payment notification for that cart overwrites the order.
- **manual-checkout-coupon-code**: `WPSC_Manual_Checkout::process_payment_data()` reads the applied coupon with the cart ID instead of
  the order post ID, so the coupon code isn't saved on the order.

Fixed after 5.3.4: the Thank You page wasn't created on a new install.

## Decisions (behavior that is kept on purpose)

None yet.

---

## Not covered yet (ideas for new tests)

PHPUnit
- The cart output with items (`print_wp_shopping_cart()`): the item rows, totals and the checkout forms.
- The payment processing after checkout (PayPal, Stripe, manual checkout): the order, the emails, the email merge tags.

Endpoint tests
- Stripe checkout (`wpsc_stripe_create_checkout_session` and the `simple_cart_stripe_ipn` return) with a fake Stripe API, and the PayPal PPCP checkout.
- Shipping and tax by region, store pickup and the terms and conditions on the cart page.
- HTML emails: in `WPSC_Email_Handler::send_manual_checkout_notification_emails()`, the HTML content type replaces the buyer email headers
  (`$headers = ...` instead of `$headers[] = ...`), so the From header is lost.
