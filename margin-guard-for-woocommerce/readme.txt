=== Margin Guard for WooCommerce ===
Contributors: coleyp118
Tags: woocommerce, coupons, margin, profit, discounts
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Protect WooCommerce gross margin by capping coupon discounts before they push priced products below your configured floor.

== Description ==

Margin Guard for WooCommerce gives store owners a simple profitability guardrail for coupon discounts.

1. Enter a per-unit Cost of goods value on products.
2. Set a global minimum gross margin under WooCommerce > Margin Guard.
3. Margin Guard caps coupon discounts when needed so the discounted line does not fall below that minimum margin.

The free plugin is fully functional and does not expire.

= How the calculation works =

Gross margin is calculated as (revenue - cost) / revenue. If your product costs $80 and your minimum margin is 20%, Margin Guard will not allow coupons to reduce revenue below $100 for that unit.

= Pro version =

Margin Guard Pro is available for stores that need product-specific floors, category-specific floors, under-margin cart blocking, and a margin-risk dashboard.

== Installation ==

1. Install and activate WooCommerce.
2. Upload and activate Margin Guard for WooCommerce.
3. Go to WooCommerce > Margin Guard and set your minimum gross margin.
4. Edit products and enter Cost of goods in the pricing section.

== Frequently Asked Questions ==

= Does this change my product prices? =

No. The free version only caps WooCommerce coupon discounts when a product has a Cost of goods value.

= What happens if a product has no cost entered? =

Margin Guard leaves that product's coupon discount unchanged.

= Does the free version expire? =

No. It is a complete free plugin and does not expire.

= Does it work with HPOS? =

Yes. Margin Guard does not depend on legacy order storage and declares HPOS compatibility.

== Changelog ==

= 1.0.2 =
* Updated WordPress compatibility declaration for 7.1.

= 1.0.1 =
* Initial public release.
