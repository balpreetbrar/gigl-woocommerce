=== GIG Delivery for WooCommerce ===
Contributors: gigl
Tags: woocommerce, shipping, delivery, gig logistics, nigeria shipping, cash on delivery
Requires at least: 6.5
Tested up to: 6.9
Requires PHP: 7.2.24
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

GIG Delivery for WooCommerce integrates GIG Logistics shipping services into your WooCommerce store, enabling real-time shipping rates, order scheduling, and cash on delivery.

== Description ==

GIG Delivery for WooCommerce allows online store owners to integrate GIG Logistics (GIGL) shipping services directly into their WooCommerce checkout process.

With this plugin, merchants can calculate real-time shipping rates, schedule shipments automatically after successful payment, and offer cash on delivery services. GIG Logistics operates across Nigeria and internationally, providing reliable and efficient delivery services.

This plugin ensures seamless order fulfillment and shipment tracking directly from your WooCommerce store.

== Features ==

* Seamless integration with WooCommerce shipping zones
* Real-time shipping rate calculation
* Cash on Delivery (COD) support
* Order tracking using Waybill ID
* Automatic shipment scheduling after successful payment
* Live and Test mode switching
* Sender address configuration

== Installation ==

= Automatic Installation =

1. Log in to your WordPress Admin dashboard.
2. Go to Plugins > Add New.
3. Search for "GIG Delivery for WooCommerce".
4. Click Install Now.
5. After installation, click Activate.
6. Go to WooCommerce > Settings > Shipping to configure the plugin.

= Manual Installation =

1. Download the plugin ZIP file.
2. Log in to WordPress Admin.
3. Go to Plugins > Add New.
4. Click Upload Plugin.
5. Choose the downloaded ZIP file and click Install Now.
6. Activate the plugin.
7. Go to WooCommerce > Settings > Shipping to configure.

== Configuration ==

To configure the plugin:

1. Go to WooCommerce > Settings.
2. Click the Shipping tab.
3. Add or edit a Shipping Zone.
4. Add "GIG Delivery" as a shipping method.
5. Click on GIG Delivery to configure the settings.

Available Settings:

* Enable/Disable – Enable the shipping method.
* Mode – Switch between Test and Live environments.
* Test Username – Enter your GIGL test account username.
* Test Password – Enter your GIGL test account password.
* Live Username – Enter your GIGL live account username.
* Live Password – Enter your GIGL live account password.
* Pickup Country – Select pickup country.
* Pickup State – Select pickup state.
* Pickup Postcode – Enter pickup postcode.
* Pickup Address – Enter sender address.
* Sender Name – Enter sender name.
* Sender Phone – Enter sender phone number.

Click "Save Changes" after updating settings.

== WooCommerce Setup ==

1. Go to WooCommerce > Settings > General and set your store location.
2. Go to WooCommerce > Settings > Shipping.
3. Create or edit a Shipping Zone.
4. Click Add Shipping Method.
5. Select "GIG Delivery".
6. Save changes.
7. Test checkout to confirm shipping rates appear.

== Frequently Asked Questions ==

= What do I need to use this plugin? =

1. WordPress with WooCommerce installed and activated.
2. A GIG Logistics merchant account.
3. API credentials from your GIG Logistics account.

Merchant sign-in: https://giglogistics.com/sign-in  
API documentation: https://dev-thirdpartynode.theagilitysystems.com/docs/

== Changelog ==

= 1.0.0 =
* Initial release
* Removed error warnings
* Updated API call handling
* Improved login response validation
* Fixed shipping price calculation issues

