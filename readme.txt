=== Troly for Wordpress ===
Contributors: subscribility
Tags: troly,woocommerce,wine,wine clubs,craft beers
Requires at least: 4.9.0
Tested up to: 5.7
Stable Tag: 2.9.26
PHP version: 7.0 and above
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Let your customers order wine from your website. Keep your products, customers, clubs and orders in sync between your website and Troly.

## Description

This plugin lets you sell your wines right from your website. You will be able to import all your products from your Troly account in just a click.
Process online payments, sign up new customers to your clubs and manage everything from one place.

Key features:

- Keep all your products and customers in sync between Wordpress and Troly.
- Capture sales directly on your website and process them in Troly
- Process payments using the payment gateway configured in Troly
- Sign up new members to your wine club
- Bulk product import / export
- Bulk customers and members import / export
- Import memberships / clubs

**WooCommerce 3.3 is the minimum supported WooCommerce version**
**Please make sure your theme also supports WooCommerce 3.3 minimum**

## Installation
**You must install WooCommerce before installing Troly for Wordpress.**

1. Enable the Wordpress add-on in your Troly account (see screenshot)
2. Download and enable the plugin (or download and upload manually to your plugins folder)
3. Link your website to your Troly account (see [screenshot](https://wordpress.org/plugins/subscribility/screenshots/))

If you want to display a form that lets visitors signup to your clubs, you need to add the shortcode `[wp99234_registration_form]` to a page.

Once that is done, you can run an initial import of all your customers, products and memberships in your Troly account. You can also export your customers and products **from** your website **to** Troly.

## Frequently Asked Questions
### What do I need to use the plugin?
The plugin is only useful if you have a Troly account to manage your operations. [Join us today!](https://troly.io/apply/)

### How can customers pay?
* Using your configured online payment gateway in Troly.
* Using the a payment gateway provided by WooCommerce. This will mark and an order as paid in Troly.
	* Refunding an order must happen in Troly and will mark it as in-progress in Wordpress. You must see to the refunding of monies taken through the payment gateway separately.

### How are shipping prices calculated?
When a customer places an order, the shipping fee will be calculated based on the size of their order and the delivery address. Those prices are calculated based on the settings you have entered in your Troly account.

### Do I need an SSL certificate (https)?
SSL certificates improve the security of your website and are highly recommended; if you do not use SSL, the plugin will still function as normal.

### Is Guest Checkout available?
Yes. To enable it, open WooCommerce > Settings > Checkout and check the _Enable guest checkout_ checkbox and save your changes.

### Where do product images come from?
By default, any image you upload for a product in Troly will be used to display the product on your website.

If you have a higher resolution or special web version of your product image, you can upload directly into WooCommerce and override the image from Troly.

**All images** must come from Troly _**OR**_ from WooCommerce. It is not possible for some images to come from Troly, while others are uploaded via WooCommerce.
To select where images are coming from, go to the Settings page of the plugin, and check or uncheck the "Use WooCommerce product images" checkbox.

== Screenshots ==
1. This screenshot shows how to install the Wordpress add-on in Troly

## Changelog
###Version 2.9.26
- Bug fix with shipping fee calculations

###Version 2.9.25
- Removed Intercom from admin dashboard
- Resolved issue with membership sign up form

###Version 2.9.24
- Adding support for flat rate shipping settings in WooCommerce

###Version 2.9.23
- Resolve various bugs involving customers editing their orders
- Resolve a bug where customers could sign up to variation memberships without a variation

###Version 2.9.22.5
- Payment card validation fixes
- Bug fixes

###Version 2.9.22
- Added Abandoned Cart feature
- Added Gift Orders feature
- Fixed issue with club membership discount amount
- Fixed issue with Intercom loading
- Improved how logs are being stored in the plugin
- Small UI enhancements
- Improved plugin stability and other bugs fixed

###Version 2.9.20
- Get more customers with the Member Referral feature
- Increase sales with the  Birthday Coupon code feature
- Gain new members during checkout  with the Membership Upsell feature
- A new area on the customer profile where members can view their membership details
- Sell members only exclusive products with the members only Product Upsell feature
- Fixed issues with how WordPress notices were displaying on the admin dashboard
- Fixed issues with shipping address not being pushed to Troly
- Fixed issue with Members Only products
- Fixed club signup form label vs. placeholder issue
- Improved plugin stability and other bugs fixed

###Version 2.9.19.5
- Bug fixed with payment processing with external payment gateways.
- Improved plugin stability and other bugs fixed.

###Version 2.9.19.4
- Added plugin action links for Settings and plugin documentation.
- Added a feature to upsell customers for "member's only products".
- Added functionality for admin to set a custom page for Club Membership signup form.
- Added an option to disable data sync between Troly and WordPress.
- Bug fixes with club signup form
- Bug fix with feature images not correctly returned on some pages.
- Improved plugin stability and other bugs fixed.

###Version 2.9.18
- Improved logging system logic and UI for better troubleshooting.
- Added a view password "eye icon" to toggle password visibility in my account page.
- Fixed product listing where, on some occasions, it was not responding in Woocommerce admin.
- Upgraded PHP notices and warnings to support newer versions of PHP
- Improvements in the plugin stability

###Version 2.9.17
- Update label for Credit details in My Account page
- Fixed saving club membership
- Improve processing for pickup order

###Version 2.9.16
- Fixed Customer account details to save against Troly in My Account page

###Version 2.9.15
- Improve synchronization for Wordpress password reset token in Mailchimp

###Version 2.9.14
- Improve validation for CVV - only required when field exists

###Version 2.9.13
- Improve customer support integration

###Version 2.9.12
- Improved guest checkout capability and order handling.
- Plugin now comes with built-in support from our team, right within your Wordpress administration Panel.

###Version 2.9.11
- Show order number instead of order id
- Troly checkout option now shown correctly

###Version 2.9.10
- Fixed DB upgrade
- Make user preference options adaptive to current theme in Club registration
- Added Intercom integration for admin users

###Version 2.9.9
- Fixed featured image not showing in Product page
- Addition Credit card validation

###Version 2.9.8
- Use Custom Customer Tags in Wordpress Sign Up Form

###Version 2.9.7
- Added more validation upon Placing Order to prevent failed transaction to Troly

###Version 2.9.6
- Capture website streams for analytics this include browsing product, abandoned cart, password reset, and successful password change.

###Version 2.9.5
- Make Club box options readable even in inverse background/theme color
- Set as Virtual product if set in Troly

###Version 2.9.4
- Fixed Syncing User's account to Troly after Placing Order
- Change all log messages to say 'troly'
- Synchronize Wordpress password reset token in Mailchimp
- Fixed non-editable order message
- Set current credentials of the customer when importing user

###Version 2.9.3
- Added membership variations to club sign-up
- Fixed shipping broken help link
- Fixed an issue with credit card fields not showing on checkout or account details page when "use existing details" is unchecked
- Fixed the logs folder not deleting around after uninstalling the plugin

###Version 2.9.2
- Added WooCommerce version in rating.php
- Fixed sync customers addresses after saving from User's Account(Frontend)

###Version 2.9.1
- Update release notes

###Version 2.9.0
- In this release, we're proud to announce we have completely revamped the way that we handle open packs! This means that you can now, for club runs only, offer your open packs to be edited. We call it... the Allocator method!
-  Note: You are unable to offer open packs as an item on your storefront. Doing so is not supported.
- Our admin UI was a little confusing, so we asked our new guy to take care of it. He added new tabs and made our log files look much nicer. Seriously, we don't know how the new guy did it, but those logs look amazing!
- We've tidied up the image code that caused some images to quietly disappeared. After speaking with the images, they agreed to make an appearance once more.
- Some hosts don't allow us to use getallheaders(), so we've added a quick pinch of salt to remedy that. Errors relating to this should no longer appear.
- A closed pack sometimes led to a "minimum quantity error" message being shown on checkout. After looking into this, we've determined that counting carts is best left to the professionals (us).
- A club run may have let through orders with less than minimum quantities allowed. Reviewing all pieces led us to a missing piece, which peacefully lead to better compliance by all carts involved.
- For those special DBAs out there, we've fixed an issue, after updating, that caused us not to respect your database prefix.
- Finally, when talking to Troly, WordPress may have sent products across when it shouldn't have. WordPress and Troly now agree that sending unwanted products isn't cool and it won't happen again.

###Version 2.8.7
- Fixed submitting Product rating to Troly
- Fixed when editing content (Posts/Pages) that breaks the admin UI page when Troly shortcode is present in content

###Version 2.8.6
- New Troly logo
- Add Troly credit to Footer (Divi and X themes only)
- Fixed on blank page when creating/edit Post/Pages content
- Fixed on Billing Mobile number not sync to Troly upon creating new User
- Fixed issue in Date of Birth
- Fixed issue in image handling so that woocommerce settings can apply

###Version 2.8.5
- Fixed an issue with plugin loading
- **Important:** this will be the last update to support PHP 5.6. Please contact your provider to move to PHP7 as soon as possible.

###Version 2.8.4
- Improved exporting of users from Wordpress
- Improved syncronisation of customer data to Troly at order placement
- Removed verbose log message appearing at all times, regardless of 'Transactions' settings

###Version 2.8.3
- Improved script handling on club form

###Version 2.8.2
- Improved image handling when using Troly images
- Improved the 'Featured Image' panel when editing a product in WooCommerce
- Fixed an issue with newsletter registrations not rendering on older versions of PHP

###Version 2.8.1
- Fixed display of club memberships did not appear correct under some circumstances
- Fixed "Show Member Benefits" appearing when there are no benefits
- Added a container for disabling the create password

###Version 2.8
- Added the option to display pack pricing for single products
- Added the option to display pack pricing for composite products
- Added the option to display member pricing for single products
- Added the option to display member pricing for composite products
- Added optional fields - mobile and postcode - on newsletter form
- Added Troly Pack prices to WooCommerce product information
- Improved composite product support internally
- Fixed images not showing as a result of WooCommerce 3.3

###Version 2.7.1
- Fix discounts not applying under some circumstances
- Allow child theme template overrides to work

###Version 2.7
- Introduced the ability for Wordpress to be used as the platform for Order Editing from Troly rather than Tasting Experience
	- Troly now has an opt-in option on the Wordpress add-on page to enable this feature

###Version 2.6.1
- Introduced the ability to use templates for product tabs
	- Troly will now allow you to customise your product display inside the product tabs

###Version 2.6.0
- Improved the logging system
	- Errors will now be communicated when importing or exporting clubs, customers and products
	- Log files will now contain more information to help your devs debug
	- Improved styling of the log table when importing
	- Added seconds to the subs_log.csv file for increased granularity of events
- Improved behind-the-scenes functionality of the operations page
- Fixed an issue where the Troly shipping method may disappear at checkout
- Added additional template directories to allow theme overrides
	- /wp99234/ and /troly/ will now be searched from within your theme directory
- Added Troly club pricing to product display within the Wordpress admin area
	- This will allow for the displaying of current membership prices in WooCommerce
	- Member prices must - and can only - be changed in Troly
- Improved exception handling when talking to Troly
	- HTTP-level exceptions are now logged to the daily log files
- Removed PHP notices and warning from a few different places

###Version 2.5.0
- Resolved an issue on club sign up where the Date of Birth setting may be incorrectly enforced
- Improved PHP requirements checking
- Improved shipping cost logic when using local pickups
- Fixed club sign up form selection failing
- Fixed 'View in Troly' link inside WooCommerce order view
- Re-added notices to the administration panel
- Added the option for newsletter or club sign up fields to use labels or placeholders for field names

###Version 2.4.7
- Resolved products appearing when _Visible Online_ is disabled in Troly
- Resolved _Out of Stock_ not applying on some products

###Versions 2.4.1 - 2.4.6
- Resolved various issues with Date of Birth on checkout and club sign up pages
- Resolved an issue that called HTTP content over HTTPS

###Version 2.4.0
- Use the *local pickup* method for pickups. Paid pickups are not currently supported
- Allow date of birth to be captured on the wine club sign up form

###Version 2.3.3
- Improved the processing of payments into Troly
- Improve customer syncronisation with Troly

###Version 2.3.2
- Fixed an error appearing on some pages for a 'PLUGIN_VERSION' constant

###Version 2.3.1
- Fixed Wordpress and Troly connection reporting 'process halted'
- Fixed shipping rates not applying under some circumstances

###Version 2.3.0
- ***New:*** Add the ability to require _and_ capture date of birth on checkout page
- Resolved an issue with tab views showing an error message
- Resolved an issue where the payment gateway may not save settings
- Improved descriptions of Troly fields added in the _Users_ section of Wordpress
- Improved compatibility with latest WooCommerce version

###Version 2.2.0
- Fixed product import failing at random intervals
- Increased the batch size when importing for customer records

###Version 2.1.9
- Added exporting functionality for PayPal

###Version 2.1.8
- Fixed an issue where some products may not add to cart

###Version 2.1.7
- Improved import and export messages when pulling or pushing products

###Version 2.1.6
- Resolve an issue with membership prices not appearing
- Improved performance for importing products from Troly

###Version 2.1.5
- Rebranded the plugin to be under the [Troly](https://troly.io) name

###Version 2.1.4
- Added a filter ('wp99234_rego_form_membership_options') for reordering club memberships on the sign up/registration page.

###Version 2.1.3
- Added support for WooCommerce guest checkout

###Version 2.1.2
- Added support for WooCommerce shipping zones
- All notifications are now enabled by default for all new members

###Version 2.1.0
- IMPORTANT UPDATE: fixes an issue with the product quantities sent to Troly when an order is placed
- Fixes a redirection issue on the membership signup form

###Version 2.0
Our biggest overhaul of the plugin to date.

- Improved interface for a better user experience
- Choose between using images uploaded in Troly, or directly in WooCommerce
- Override the default signup form template by copying into a folder called wp99234 in your child theme's root folder
- Only one phone number is necessary to sign up to your clubs, instead of two
- Translate product variations into a number of bottles (6 packs, 12 packs etc...)
- Several bugfixes and smaller improvements

###Version 1.2.31

- First official release

## Upgrade notice
### 2.0
Version 2 of the plugin uses version 1.1 of the Wordpress add-on in Troly. Support for version 1.0 of the Wordpress add-on is now deprecated.


### 1.2.31 ###
This version makes updating the plugin much easier. It also fixes a number of issues with the member signup process.

## Dependencies
+ WooCommerce Version 3.3.0 (tested up to 3.3.5)
* An active [Troly](https://troly.io/) account
