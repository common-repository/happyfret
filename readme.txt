=== Happyfret ===
Contributors: advisecommunication, RenaudMG
Donate link: http://www.advise.fr/
Tags: woocommerce, shipping
Requires at least: 5.0
Tested up to: 5.0.3
Stable tag: trunk
Requires PHP: 5.2.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Happyfret : the plugin that makes shipping easier

== Description ==

Whether you manage your shipments yourself or subscribe to the Happyfret logistics service, you can benefit from the brokerage services to benefit from the best transport offers for your shipments.
To learn more about Happyfret services and get your API key, visit https://www.happyfret.com/. If you already have your API key, you can set up your Happyfret plugin without waiting.


== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the HappyFret->Settings screen to configure the plugin and set your API key


== API calls ==

To get and provide your the best shipping rates, the HappyFret plugin communicates with the HappyFret servers through an API. This API is used to :
l. Validate your API key and email
l. Get your HappyFret client profile
l. Get the list of products you might have stored in HappyFret warehouses
l. Create a product in the HappyFret database from your WooCommerce shop
l. Get the best packing option for products within a cart
l. Get shipping rates for a cart
l. Confirm an order to the HappyFret information system

== Frequently Asked Questions ==

No question has been asked yet


== Screenshots ==

1. The HappyFret plugin configuration screen, in WordPress admin panel


== Changelog ==
= 1.2.0 =
* TODO
= 1.1.0 =
* Use of WordPress cURL standard functions
= 1.0.0 =
* Fully working plugin
= 0.5.1 =
* Products synchronization with Happyfret
= 0.5.0 =
* API synchronisation : first attempt with check-api-key
* HappyfretProduct class creation for bidirectionnal products synchronisation 
   between plugin and Happyfret
= 0.4.0 =
* Plugin fully translated in french
= 0.3.1 =
* Code corrections for php 5 compatibility
= 0.3.0 =
* Fully functionnal testing version
= 0.2.1 =
* Bug fixes in plugin install
* Packing orders creation
= 0.2.0 =
* Creation of classes HappyfretShippingMethod ans HappyfretPackingOrder
* Woocommerce hook for woocommerce_shipping_init
* Bug fixes in the admin panel
= 0.1.0 
* Classes creation
* Install and uninstall plugin : database creation, default values and deletion

== Upgrade Notice ==

= 1.2.0 =
First version published on the WordPress plugin directory