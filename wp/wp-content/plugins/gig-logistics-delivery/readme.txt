=== GIG Logistics Delivery ===
Contributors: gigl
Tags: woocommerce, shipping, logistics, delivery, order tracking
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.2
Requires Plugins: woocommerce
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integrate GIG Logistics shipping with WooCommerce for real-time rates, shipment scheduling, tracking, and cash on delivery.
== Description ==

GIG Logistics Delivery allows online store owners to integrate GIG Logistics (GIGL) shipping services directly into their WooCommerce checkout process.

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
3. Search for "GIG Logistics Delivery".
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


== External Services ==

This plugin connects to the official GIG Logistics (GIGL) Shipping API to provide shipping rate calculation, shipment scheduling, tracking, and cash on delivery services inside WooCommerce.

**Service Provider:** GIG Logistics  
**Website:** https://giglogistics.com

API Domains Used by This Plugin:

The plugin communicates with the following GIG Logistics API domains:

Test Environment:
https://dev-thirdpartynode.theagilitysystems.com/

Production Environments:
https://thirdpartynode.theagilitysystems.com/

These domains are official GIG Logistics API endpoints operated by GIG Logistics.  
All data transmitted to these domains is governed by GIG Logistics' Terms of Service and Privacy Policy listed below.

What the service is used for:

The API is required to:

- Authenticate merchant accounts
- Calculate real-time shipping rates at checkout
- Schedule shipments after order payment
- Generate and retrieve Waybill IDs
- Enable Cash on Delivery (COD)
- Retrieve shipment tracking information

What data is sent and when:

1. During shipping rate calculation at checkout:
- Pickup address (country, state, postcode, address)
- Delivery address (country, state, postcode, address)
- Cart item details (name, quantity, weight, declared value)
- Shipment details (type, vehicle type, coordinates if available)
- Merchant API credentials (for authentication)

This data is sent when a customer views or updates the checkout page and shipping rates are requested.

2. During shipment scheduling after payment:
- Sender name and phone number
- Receiver name, email address, and phone number
- Delivery address
- Order value
- Shipment item details

This data is sent only after an order is placed and shipment scheduling is triggered.

3. During shipment tracking:
- Waybill ID

This data is sent when tracking information is requested.

Under what conditions data is transmitted:

Data is only transmitted if:
- The shipping method is enabled.
- Valid API credentials are configured.
- Shipping rates are requested at checkout.
- A shipment is scheduled after payment.
- Tracking information is requested.

No data is transmitted if the plugin is disabled or not configured.

Terms of Service:
https://giglogistics.com/terms/

Privacy Policy:
https://giglogistics.com/privacy-policy/

API Documentation:
https://dev-thirdpartynode.theagilitysystems.com/docs/


== Additional External Service ==

This plugin also connects to a third-party geocoding API to convert address text into geographic coordinates (latitude and longitude). These coordinates are used to improve shipping rate calculation and shipment scheduling accuracy.

Service Provider:
LatLng API

Website:
https://latlng.work/

API Endpoint Used:
https://api.latlng.work/api

What the service is used for:

The API is used to convert pickup and delivery addresses into geographic coordinates (latitude and longitude) required by the GIG Logistics shipping API.

What data is sent and when:

The plugin sends the following data when geographic coordinates are required:

- Pickup address (street, city, state, postcode, country)
- Delivery address (street, city, state, postcode, country)

This request is sent only when the plugin needs to retrieve latitude and longitude values for shipping calculations.

Under what conditions data is transmitted:

Data is only transmitted when:

- Shipping rates are calculated at checkout, or
- Shipment scheduling requires geographic coordinates.

If the shipping method is disabled or shipping rates are not requested, no address data is sent to this service.

Terms of Service:
https://www.latlng.work/terms

Privacy Policy:
https://www.latlng.work/privacy

== Frequently Asked Questions ==

= What do I need to use this plugin? =

1. WordPress with WooCommerce installed and activated.
2. A GIG Logistics merchant account.
3. API credentials from your GIG Logistics account.

Merchant sign-in: https://gigagilitysystems.com/Login/ 
API documentation: https://dev-thirdpartynode.theagilitysystems.com/docs/

== Changelog ==

= 1.0.0 =
* Initial release
* Removed error warnings
* Updated API call handling
* Improved login response validation
* Fixed shipping price calculation issues

