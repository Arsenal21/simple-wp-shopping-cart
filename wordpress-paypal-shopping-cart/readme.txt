=== WordPress Simple PayPal Shopping Cart ===
Contributors: Tips and Tricks HQ, Ruhul Amin, wptipsntricks, mbrsolution, mra13
Donate link: https://www.tipsandtricks-hq.com
Tags: cart, shopping cart, WordPress shopping cart, Paypal shopping cart, sell, selling, sell products, online shop, shop, e-commerce, wordpress ecommerce, wordpress store, store, PayPal cart widget, sell digital products, sell service, digital downloads, paypal, paypal cart, e-shop, compact cart, coupon, discount
Requires at least: 4.7
Tested up to: 5.2
Stable tag: 4.4.7
License: GPLv2 or later

Very easy to use Simple WordPress PayPal Shopping Cart Plugin. Great for selling products online in one click from your WordPress site.

== Description ==

WordPress Simple PayPal Shopping Cart allows you to add an 'Add to Cart' button for your product on any posts or pages. This simple shopping cart plugin lets you sell products and services directly from your own wordpress site and turns your WP blog into an ecommerce site.

It also allows you to add/display the shopping cart on any post or page or sidebar easily. The shopping cart shows the user what they currently have in the cart and allows them to change quantity or remove the items. 

https://www.youtube.com/watch?v=dJgGdD-tZW4

You will be able to create products by using shortcodes dynamically.

The shopping cart output will be responsive if you are using it with a responsive theme.

You can sell digital products via this plugin easily too. The following video shows how you can sell your digital media files using the simple cart plugin:

https://www.youtube.com/watch?v=gPvXac_j_lI

This plugin is a lightweight solution (with minimal number of lines of code and minimal options) so it doesn't slow down your site.

The plugin also has an option to use the smart PayPal payment buttons. You can enable the PayPal smart button option in the settings menu of the plugin. The following video shows a checkout demo using PayPal smart button.

https://www.youtube.com/watch?v=m0yDWDmtpKI

It can be integrated with the NextGen Photo Gallery plugin to accommodate the selling of photographs from your gallery.

WP simple PayPal Cart Plugin, interfaces with the PayPal sandbox to allow for testing.

For video tutorial, screenshots, detailed documentation, support and updates, please visit:
[WP Simple Cart Details Page](https://www.tipsandtricks-hq.com/wordpress-simple-paypal-shopping-cart-plugin-768)
or
[WP Simple Cart Documentation](https://www.tipsandtricks-hq.com/ecommerce/wp-shopping-cart)

= Features =

* Easily create "add to cart" button with options if needed (price, shipping, options variations). The cart's shortcode can be displayed on posts or pages.
* Use a function to add dynamic "add to cart" button directly in your theme.
* Minimal number of configuration items to keep the plugin lightweight.
* Sell any kind of tangible products from your site.
* Ability to sell services from your your site.
* Sell any type of media file that you upload to your WordPress site. For example: you can sell ebooks (PDF), music files (MP3), audio files, videos, photos, images etc.
* Your customers will automatically get an email with the media file that they paid for.
* Show a nicely formatted product display box on the fly using a simple shortcode.
* You can use PayPal sandbox to do testing if needed (before you go live).
* Option to use the smart payment buttons of PayPal. Allows the customers to checkout in a popup window (using a credit card, paypal or paypal credit).
* Collect special instructions from your customers on the PayPal checkout page.
* The orders menu will show you all the orders that you have received from your site.
* Ability to configure an email that will get sent to your buyers after they purchase your product.
* Ability to configure a sale notification email that gets sent to the site admin when a customer purchase your item(s).
* Ability to configure discount coupons. Offer special discounts on your store/shop.
* You can create coupons and give to your customers. When they use coupons during the checkout they will receive a discount.
* Create discount coupons with an expiry date. The coupon code automatically expires after the date you set.
* Compatible with WordPress Multi-site Installation.
* Ability to specify SKU (item number) for each of your products in the shortcode.
* Ability to customize the add to cart button image and use a custom image for your purchase buttons.
* Ability to customize the add to cart button text via shortcode parameter on a per product basis.
* Track coupons with the order to see which customer used which coupon code.
* Ability to add a compact shopping cart to your site using a shortcode.
* Ability to show shopping cart with product image thumbnails.
* Ability to use a custom checkout page style.
* Ability to open checkout page in a new browser tab/window.
* Ability to use TinyMCE shortcode inserter to add shortcodes to your posts/pages.
* Works nicely with responsive WordPress themes.
* Can be translated into any language.
* and more...

= Shopping Cart Setup Video Tutorials =

There is a series of video tutorials to show you how to setup the shopping cart on your site. 

Check the video tutorials [here](https://www.tipsandtricks-hq.com/wordpress-simple-paypal-shopping-cart-plugin-768).

= Language Translations =

The following language translations are already available:

* English
* German
* Spanish
* French
* Breton
* Italian
* Japanese
* Polish
* Czech
* Hebrew
* Swedish
* Norwegian
* Danish
* Turkish
* Dutch
* Brazilian Portuguese

You can translate the plugin using [this documentation](https://www.tipsandtricks-hq.com/ecommerce/translating-the-wp-simple-shopping-cart-plugin-2627).

= Developers =
* If you are a developer and you need some extra hooks or filters for this plugin please let us know.
* Github repository - https://github.com/Arsenal21/simple-wp-shopping-cart

= Note =

There are a few exact duplicate copies of this plugin that other people made. We have a few users who are getting confused as to which one is the original simple shopping cart plugin. This is the original simple PayPal shopping cart and you can verify it with the following information:

* Check the stats tab of the plugin and you will be able to see a history of when this plugin was first added to WordPress.
* Check the number of downloads on the sidebar. The original plugin always gets more downloads than the copycats.
* Check the number of ratings. The original plugin should have more votes.
* Check the developer's site.

== Usage ==
1. To add an 'Add to Cart' button for a product, simply add the shortcode [wp_cart_button name="PRODUCT-NAME" price="PRODUCT-PRICE"] to a post or page next to the product. Replace PRODUCT-NAME and PRODUCT-PRICE with the actual name and price.

2. To add the 'Add to Cart' button on the sidebar or from other template files use the following function:
<?php echo print_wp_cart_button_for_product('PRODUCT-NAME', PRODUCT-PRICE); ?>
Replace PRODUCT-NAME and PRODUCT-PRICE with the actual name and price.

3. To add the shopping cart to a post or page (eg. checkout page) simply add the shortcode [show_wp_shopping_cart] to a post or page or use the sidebar widget to add the shopping cart to the sidebar. The shopping cart will only be visible in a post or page when a customer adds a product.

= Using Product Display Box =

Here is an exmaple shortcode that shows you how to use a product display box.

[wp_cart_display_product name="My Awesome Product" price="25.00" thumbnail="http://www.example.com/images/product-image.jpg" description="This is a short description of the product"]

Simply replace the values with your product specific data

= Using a compact shopping cart =

Add the following shortcode where you want to show the compact shopping cart:

[wp_compact_cart]

= Using Shipping =

1. To use shipping cost for your product, use the "shipping" parameter. Here is an example shortcode usage:
[wp_cart_button name="Test Product" price="19.95" shipping="4.99"]

or use the following php function from your wordpress template files
<?php echo print_wp_cart_button_for_product('product name',price,shipping cost); ?>

= Using Variation Control =

1. To use variation control use the variation parameter in the shortcode:
[wp_cart_button name="Test Product" price="25.95" var1="VARIATION-NAME|VARIATION1|VARIATION2|VARIATION3"]

example usage: [wp_cart_button name="Test Product" price="29.95" var1="Size|small|medium|large"]

2. To use multiple variation for a product (2nd or 3rd variation), use the following:

[wp_cart_button name="Test Product" price="29.95" var1="Size|small|medium|large" var2="Color|red|green|blue"]

[wp_cart_button name="Test Product" price="29.95" var1="Size|small|medium|large" var2="Color|red|green|blue" var3="Sleeve|short|full"]

== Installation ==

1. Unzip and Upload the folder 'wordpress-paypal-shopping-cart' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings of this plugin and configure the options (for example: your email, Shopping Cart name, Return URL etc.)
4. Use the shortcode to add a product to a post or page where you want it to appear.

== Frequently Asked Questions ==
= Can this plugin be used to accept paypal payment for a service or a product? =
Yes
= Does this plugin have shopping cart =
Yes
= Can the shopping cart be added to a checkout page? =
Yes
= Does this plugin has multiple currency support? =
Yes
= Is the 'Add to Cart' button customizable? =
Yes
= Does this plugin use a return URL to redirect customers to a specified page after PayPal has processed the payment? =
Yes
= How can I add a buy button on the sidebar widget of my site? =
Check the documentation on [how to add buy buttons to the sidebar](https://www.tipsandtricks-hq.com/ecommerce/wordpress-shopping-cart-additional-resources-322#add_button_in_sidebar)
= Can I use this plugin to sell digital downloads? =
Yes. See the [digital download usage documentation](https://www.tipsandtricks-hq.com/ecommerce/wp-simple-cart-sell-digital-downloads-2468)
= Can I configure discount coupon with this shopping cart plugin? =
Yes. you can setup discount coupons from the "Coupon/Discount" interface of the plugin.
= Can I configure product sale notification so I get notified when a sale is made? =
Yes. You can configure sale notification from the "Email Settings" interface of the plugin.
= Can I modify the product box thumbnail image? =
Yes.
= Can I customize the format of the price display? =
Yes.
= Can the customers be sent to a cancel URL when they click "cancel" from the PayPal checkout page? =
Yes.

== Screenshots ==
Visit the plugin site at https://www.tipsandtricks-hq.com/wordpress-simple-paypal-shopping-cart-plugin-768 for screenshots.

== Upgrade Notice ==

None

== Changelog ==

= 4.4.7 =
- Fixed the "Order ID does not exist in IPN Notification" issue with smart paypal checkout option for some sites.

= 4.4.6 =
- Changed the quantity input field to be a "number" type field. Customers will be able to change the number value easily.
- The session for the shopping cart is only started on the front-end.

= 4.4.5 =
- Fixed issues with custom fields when using Collect Customer Input addon.

= 4.4.4 =
- More texts are now translatable. POT file updated.
- Added more filter hooks so the customer Input addon fields are now exported to CSV as well.

= 4.4.3 =
- Added button customization options (in the advanced settings) for the smart paypal checkout button.

= 4.4.2 =
- Added PayPal smart button configuration documentation
  https://www.tipsandtricks-hq.com/ecommerce/enabling-smart-button-checkout-setup-and-configuration-4568

= 4.4.1 =
- Added a new checkout option that uses the smart PayPal payment button. You can enable it from the advanced settings menu.
- Added Breton language files. Thanks to Florian for submitting the language files.

= 4.4.0 =
- The order date is now included in the exported CSV file.
- Updated the German language file. Thanks to Oliver Juwig.
- Updated some CSS code to not show border in the cart.
- Updated the checkout button image.

= 4.3.9 =
- The note to seller field has been removed as it is no longer supported by PayPal.

= 4.3.8 =
- The settings menu has been moved to a new menu called "Simple Cart" in the admin dashboard.
- The coupons tab has been moved to a separate menu item under the "Simple Cart" admin menu.
- Added a new email merge tag for the sale notification emails. The new tag is {order_id}
- Added github repository link in the readme file.
- Added couple of filter hooks in the shopping cart display function.
- The delete coupon link color has been changed to red.

= 4.3.7 =
- Added Russian Ruble currency to the currency dropdown option.
- Added CSS class to the quantity input field in the cart.
- Copied the nextgen gallery template to the root folder.
- The email merge tags can now be used in the sale notification email subject.
- Added a new parameter (button_text) for the add to cart button shortcode. This parameter can be used to specify a custom button text for the add to cart button. Usage instructions at the following page:
https://www.tipsandtricks-hq.com/ecommerce/simple-shopping-cart-customize-the-add-to-cart-button-text-via-shortcode-4383

= 4.3.6 =
- There is now a basic shortcode inserter for this plugin in the wp post/page editor.
- The cart orders search functionality can now search records using customer's email and name.
- Added CSS classes to the variation drop-downs.
- CSS optimization in the settings interface of the plugin.

= 4.3.5 =
- The deprecated page styles field has been replaced with an image URL field in the settings. 
- The Image URL field can be used to specify an image/logo URL that will be displayed in the paypal checkout page.

= 4.3.4 =
- All the paypal supported currency codes are shown as a dropdown option in the plugin settings.
- Currency code value in the settings is automatically converted to uppercase string if the user mistakenly enters a lowercase string.

= 4.3.3 =
- Fix for paypal adding "+" character between words in the item name parameter.

= 4.3.2 = 
- Bugfix for the new custom field change.

= 4.3.1 =
- Custom field values will now be urlencoded.

= 4.3.0 =
- Added Brazilian Portuguese Language translation to the plugin. The translation file was submitted by Fabio Goncalves.
- If the total shipping cost in the cart is 0 then the plugin will send a flag to paypal to not prompt for shipping address during checkout.

= 4.2.9 =
- Added a new option to export all the orders data to a CSV file. This new option can be found under the Simple Cart Settings -> Tools menu.
- Added a new filter (wspsc_paypal_ipn_notify_url) to allow overriding of the PayPal IPN notify URL.
- Added a new compact cart shortcode that uses a different style. Read the following page to find out how it works:
  https://www.tipsandtricks-hq.com/ecommerce/simple-cart-showing-a-compact-shopping-cart-2925

= 4.2.8 =
- Fixed an issue with the {payer_email} tag not working in the buyer notification email.

= 4.2.7 =
- Added a new filter for the cart icon image (wspsc_cart_icon_image_src). It can be used to customize the cart icon image.
- Added escaping for the cart link parameter.

= 4.2.6 =
- Added a new email merge tag for phone number (when available). The new email tag is {phone}. Note that the phone number is an optional field on PayPal checkout page. So it may not be present if the customer doesn't enter a phone number during the checkout.
- Added a new shortcode parameter (thumb_alt) for the product box shortcode. It can be used to specify an alt tag for the product thumbnail image.
- Added url_decode in the cart link parameter. So the link works even when the URL contains foreign characters.

= 4.2.5 =
- Minor update for backwards compatibility with an old shortcode using variation.

= 4.2.4 =
- Added a new filter for the checkout button image. It can be used to specify a custom button image for the checkout button.
  Example code: https://www.tipsandtricks-hq.com/ecommerce/customize-the-paypal-checkout-button-image-4026
- Incomplete old cart orders will now be automatically cleaned by the plugin.
- Made some improvements to the PayPal IPN validation code. It is fully compatible with the upcoming PayPal IPN changes.

= 4.2.2 =
- Minor update for backwards compatibility with an old shortcode.
- WordPress 4.5 compatibility.

= 4.2.1 =
- Added backwards compatibility for the old shortcodes. So the old add to cart button shortcodes will continue to work as usual.

= 4.2.0 =
- Added an option in the settings to disable nonce check for the add to cart button. 
This is useful for some sites that are using caching. Otherwise 48 hour old cached pages will have stale nonce and the nonce security check will fail.
If you are using a caching solution on your site and having issue with nonce security check failing, then enable this option from the settings.

= 4.1.9 =
- Added more sanitization and validation on POST/GET/REQUEST data.

= 4.1.8 =
- Added a new filter in the cart (wspsc_cart_extra_paypal_fields) that will allow you to add extra hidden fields for the PayPal cart checkout.
- Deleted the local copy of the Spanish language file so the plugin loads the language file from translate.wordpress.org.
- Deleted the local copy of the Italian language file so the plugin loads the language file from translate.wordpress.org.
- Deleted the local copy of the Swedish language file so the plugin loads the language file from translate.wordpress.org.
- Deleted the local copy of the Turkish language file so the plugin loads the language file from translate.wordpress.org.
- Improved the add to cart price validation code against potential vulnerability.

= 4.1.7 =
- Added a new text field in the settings - Cancel URL.
- Debug log file has been renamed to "ipn_handle_debug.txt".
- Added CSS class to the plain text add to cart button so it can be customized via custom CSS code.

= 4.1.6 =
- Fixed an issue where post payment price validation would fail for a transaction with a discount coupon.

= 4.1.5 =
- Added CSS classes to all the "tr" elements in the cart.
- Added alt tag to all the images and icons in the cart.
- Added Dutch Language translation to the plugin. The Turkish translation file was submitted by Boye Dorenbos.
- Added a new email tag {address} that can be used in the notification email to include the buyers address.

= 4.1.4 =
- Our plugin is being imported to translate.wordpress.org. Changed the plugin text domain so it can be imported into translate.wordpress.org.
- Fixed an issue with an email shortcode not working in the buyer email body.

= 4.1.3 =
- Updated the WP_Widget initialization to use PHP5 style constructor.
- There is a bug in WordPress 4.3 for the widgets which prevents the sidebar widget from working. WordPress 4.3.1 will fix this.
In the meantime you can use the following to show the shopping cart widget to the sidebar:
https://www.tipsandtricks-hq.com/ecommerce/adding-shopping-cart-to-the-sidebar-of-wordpress-site-3073

= 4.1.2 =
- Added a class to the cart header image element.
- Removed an extra <br> tag from the cart output.
- Plugin is now compatible with WordPress 4.3.
- Added another extra check to the price validation code.

= 4.1.1 =
- Updated constructor methods in classes with PHP 5 style constructors.

= 4.1.0 =
- Removed "v" from the version number.
- Added robust price validation checks.

= 4.0.9 =
- Changed the input slug of "product" name to be more specific.

= 4.0.8 =
- Added Turkish Language translation to the plugin. The Turkish translation file was submitted by Vural Pamir.
- WordPress 4.2 compatibility

= 4.0.7 =
- The cart stylesheet file now uses the 'wp_enqueue_scripts' hook
- Added a new shortcode parameter (thumb_target) which can be used to specify a target URL for the product thumbnail image
- Coupon codes are now case-insensitive.
- Updated the Italian language file.

= 4.0.6 =
- Added an email tag to include the coupon code used in the notification email.
- Added an extra check to prevent a debug notice message from showing when the cart is reset.
- WordPress 4.1 compatibility.

= 4.0.5 = 
- Added two new filters to allow dynamic modification of the buyer and seller notification email body (just before the email is sent).
- Added a new filter so the orders menu viewing permission can be overridden by an addon.
- Added Danish Language translation to the plugin. The Danish translation file was submitted by Steve Jorgensen.
- Added a function to strip special characters from price parameter in the shortcode.

= 4.0.4 =
- Added some new email tags to show Transaction ID, Purchase Amount and Purchase Date (check your email settings field for details).
- Made some improvements to the PayPal IPN validation code.

= 4.0.3 =
- Fixed a few notices in the settings menu when run in debug mode.
- Fixed a warning notice on the front end when run in debug mode.

= 4.0.2 =
- Added a new option so you can store your custom language file for this plugin in a folder outside the plugin's directory.
- Added the following two new filters to allow customization of the add to cart button:
  wspsc_add_cart_button_form_attr
  wspsc_add_cart_submit_button_value
- Added Text Domain and Domain Path values to the plugin header.
- Added Norwegian language translation to the plugin. The Swedish translation file was submitted by Reidar F. Sivertsen.
- Added some security checks a) to make sure that the payment is deposited to the email specified in the settings  b) to block multiple payment notifications for the same transaction ID
- Buyer's contact phone number is now also saved with each order (given you have enabled it).
- Added the following new filter to allow customization of the product box shortcode:
  wspsc_product_box_thumbnail_code

= 4.0.1 =
- Added a new filter to format the price in the shopping cart. Example usage: 
  https://www.tipsandtricks-hq.com/ecommerce/customizing-price-amount-display-currency-formatting-3247
- WordPress 4.0 compatibility.

= 4.0.0 =
- Changed the permission on the orders menu so it is only available to admin users from the backend.
- Made some enhancement around the PHP session_start function call.
- Added an extra check to prevent direct access to the cart file.
- Added expiry date field in the discount coupon. You can now create discount coupons with an expiry.

= 3.9.9 =
- Added a new feature that allows you to show the product thumbnail image in the shopping cart. Use "show_thumbnail" parameter in the shopping cart shortcode for this.
- Added Swedish language translation to the plugin. The Swedish translation file was submitted by Felicia.
- Fixed a minor bug with the checkout page style feature.
- Added a new filter for the item name field in the shopping cart.
- Made some minor CSS improvements for the cart output.
- The {product_details} email shortcode will now show the full amount of the item (instead of the individual item amount).

= 3.9.8 =
- Added Hebrew Language translation to the plugin. The Hebrew translation file was submitted by Sagi Cooper.
- Added extra condition to address the "Invalid argument supplied" error that a few users were getting.

= 3.9.7 =
- Added a new feature to open the checkout page in a new tab/window when user clicks the checkout button.
- Updated the Cart Orders menu icon to use a slightly better looking dashicon.
- Added a new filter to allow modification of the custom field value. Filter name is wpspc_cart_custom_field_value
- Added a new action hook after the PayPal IPN is processed. This will allow you to do extra post payment processing task for your orders. Hook name wpspc_paypal_ipn_processed
- Made some improvements to some of the shopping cart icons (cart and delete item icons have been updated). 
- Cart output will work with a responsive theme.

= 3.9.6 =
- Added Czech Language translation to the plugin. The Czech translation file was submitted by Tomas Sykora.
- Added a new option/feature to specify a custom paypal checkout page style name. The plugin will use the custom checkout page style if you specify one.
- Each order now also shows the shipping amount in the order managment interface.

= 3.9.5 =
- Added a new feature that lets you (the site admin) configure a sale notification email for the admin. When your customer purchase a product, you get a notification email. Activate this feature from the "Email Settings" interface of the plugin.
- Added Polish language translation to the plugin. The Polish langage translation file was submitted by Gregor Konrad.
- Fixed a minor issue with custom button images that uses HTTPS URL.
- Added more CSS classes in the shopping cart so you can apply CSS tweaks easily.

= 3.9.4 =
- Fixed a minor bug in the new compact cart shortcode [wp_compact_cart]

= 3.9.3 =
- Added a new feature to show a compact shopping cart. You can show the compact shopping cart anywhere on your site (example: sidebar, header etc).
- Language translation strings updated. Translation instruction here - http://www.tipsandtricks-hq.com/ecommerce/translating-the-wp-simple-shopping-cart-plugin-2627
- Added a new function for getting the total cart item quantity (wpspc_get_total_cart_qty).
- Added a new function to get the sub total amount of the cart (wpspc_get_total_cart_sub_total).

= 3.9.2 =
- Added an option to specify a custom button image for the add to cart buttons. You can use the "button_image" parameter in the shortcode to customize the add to cart button image.
- Coupon code that is used in a transaciton will be saved with the order so you can see it in the back end.

= 3.9.1 =
- WP 3.8 compatibility

= 3.9.0 and 3.8.9 =
- WP Super Cache workaround - http://www.tipsandtricks-hq.com/ecommerce/wp-shopping-cart-and-wp-super-cache-workaround-334
- Added a new shortcode argument to specify a SKU number for your product.
- Fixed a few debug warnings/notices
- Added Italian language file

= 3.8.8 =
- Added a discount coupon feature to the shopping cart. You can now configure discount coupon via the Simple cart settings -> Coupon/Discount menu
- View link now shows the order details
- fixed a bug where the shipping price wasn't properly showing for more than $1000
- WordPress 3.7 compatibility

= 3.8.7 =
- Changed a few function names and made them unique to reduce the chance of a function name conflict with another plugin.
- Added a new option in the plugin so the purchased items of a transaction will be shown under orders menu
- Payment notification will only be processed when the status is completed.

= 3.8.6 =
- Updated the broken settings menu link
- Updated the NextGen gallery integration to return $arg1 rather than $arg2

= 3.8.5 =
- Added an email settings menu where the site admin can customize the buyer email that gets sent after a transaction
- Also, added the following dynamic email tags for the email body field:

{first_name} First name of the buyer
{last_name} Last name of the buyer
{product_details} The item details of the purchased product (this will include the download link for digital items).

= 3.8.4 =
- Fixing an issue that resulted from doing a commit when wordpress.org plugin repository was undergoing maintenance

= 3.8.3 =
- Improved the settings menu interface with the new shortcode usage instruction.

Full changelog for all versions can be found at the following URL:
http://www.tipsandtricks-hq.com/ecommerce/?p=319
