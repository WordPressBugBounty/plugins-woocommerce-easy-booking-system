==== Easy Booking – WooCommerce Booking & Reservation Plugin ====

Contributors: @morki
Tags: woocommerce, booking, appointment, reservation, calendar
Requires at least: 5.0
Stable tag: 3.5.2
Tested up to: 7.0.2
WC tested up to: 10.9.4
License: GPLv3

A simple and flexible WooCommerce booking & reservation plugin to manage dates, availability and pricing on your products.

== Description ==

Easy Booking is a powerful yet intuitive WooCommerce booking and rental plugin, fully compatible with simple, variable, grouped, and bundle products. Designed to seamlessly integrate with your existing WooCommerce setup.

- **Flexible booking modes**: Choose between Days or Nights mode, and set custom booking durations and limits to match your business model.
- **Date selection**: offer single or dual-date bookings (e.g., check-in/check-out) for maximum flexibility.
- **Dashboard management**: Easily track and manage processing or upcoming bookings directly from your WordPress admin.
- **Developer-friendly**: Extend functionality with filters and action hooks for custom integrations.

[Official website](https://easy-booking.pro/) | [Demo](https://demo.easy-booking.pro/) | [Documentation](https://easy-booking.pro/documentation/) | [FAQ](https://easy-booking.pro/faq/)

= Why choose Easy Booking? =

- **No complex setup**: Works natively with WooCommerce, no extra product types or complicated configurations.
- **Adaptable to your needs**: Whether you rent equipment, manage event registrations, or offer seasonal services, Easy Booking adjusts to your workflow.
- **Responsive support & clear documentation** : Get help when you need it, with detailed documentaiton and a quick, friendly developer ready to assist you.

Perfect for rentals, event bookings, or any date-based service, Easy Booking gives you the tools to streamline reservations.

= Upgrade to Easy Booking PRO for advanced features =

- **Stock management by date**: Automatic availability management for each date individually, ensuring no overbookings.
- **Disabled dates**: Block specific dates (holidays, closures, etc.) to match your business schedule.
- **Advanced pricing**: Set prices by date, season, or booking duration for maximum flexibility.
- **Date selection on shop page**: Let customers choose dates directly from the product listing, with real-time filtering of available products.
- **Manual booking import**: Add reservations manually without creating orders, ideal for phone bookings or external systems.

Unlock the full potential of your booking system with [Easy Booking PRO](https://easy-booking.pro/pro/).

= Looking for time-based bookings instead? =

Easy Booking focuses on rentals, accommodations, and date-based bookings without time selection.

For appointments, services, classes, and bookings with time slots, take a look at [Easy Scheduling](https://wordpress.org/plugins/noushka-easy-scheduling/).

= Demo =

See Easy Booking in action: Check out the [demo](http://demo.easy-booking.pro/) and explore all the features.

== Installation ==

= Requirements =

WordPress 5.0 or greater
WooCommerce 4.0 or greater

Make sure WooCommerce is installed and activated before starting.

= Installation =

You can install the plugin automatically or manually. If you are not familiar with plugin installation, please refer to [this page](https://wordpress.org/support/article/managing-plugins/#installing-plugins).

= Settings =

Learn how to configure the plugin and your products in the [documentation](https://easy-booking.pro/documentation/).

== Frequently Asked Questions ==

Check the FAQ [here](https://easy-booking.pro/faq/).

== Screenshots ==

1. Date selection on product page.
2. Easy Booking is compatible with simple, variable, grouped and bundle products.
3. Easy Booking flexible settings.
4. Easy Booking PRO features: stock management by date and advanced pricing.
5. Manage bookings from your dashboard with list and calendar views.

== Changelog ==

= 3.5.2 - 2026-08-01 =

* Fix - Variation booking settings not being saved.

= 3.5.1 - 2026-07-13 =

* Fix - "Please choose valid dates" error message when booking duration was not 1 and product has booking min/booking max set.

= 3.5.0 - 2026-07-09 =

* Fix - Check minimum and maximum booking duration before adding to cart.
* Fix - Delete associated bookings when trashing an order.
* Fix - Output not escaped and misc security fixes.
* Tweak - Changed hook to enable bookable option on all products.

= 3.4.9 - 2026-03-05 =

* Fix - Fatal error with some themes with outdated WooCommerce templates.

= 3.4.8 - 2026-03-03 =

* Tweak - Added global variable to datepicker calendar CSS.
* Tweak - Improved plugin settings, plugin intall and DB update manager
* Tweak - Added tr.js file for Turkish translation.

= 3.4.7 - 2025-12-01 =

* Add - Compatibility with Easy Booking PRO new dates filter block.
* Add - "set_min_booking_duration" jquery event to allow dynamically modifying minimum booking duration depending on selected date.
* Fix - First available date can no longer be in the past.
* Fix - Issue where "Number of dates to select" was not saved as "Same as global settings".
* Fix - Issue with bundle products retruned price after selecting dates.
* Tweak - Improved wceb_is_valid_date() function.
* Tweak - Added wceb_create_sql_placeholders() to generate placeholders for arrays in SQL requests.
* Tweak - Added es.js file for Spanish translation of the calendar.
* Tweak - Removed unnecesary code.

= 3.4.6 - 2025-10-27 =

* Fix - Issue with Product Bundles.
* Fix - Issue with Product Add-Ons.
* Fix - Updated deprecated script handles.

= 3.4.5 - 2025-10-23 =

* Fix - Updated JS file version to force browsers to load the latest file and avoid cache issues.

= 3.4.4 - 2025-10-20 =

* Fix - Cache issues on date selection. The plugin should now be fully compatible with cache plugins without having to disable caching on product pages.
* Add - "Last available date" option at product and variation level.

= 3.4.3 - 2025-09-09 =

* Fix - Price not updated correctly on grouped product.
* Tweak - Removed deprecated jquery events.

= 3.4.2 - 2025-07-01 =

* Fix - Date not available when week starts on Sunday.

== Upgrade Notice ==
