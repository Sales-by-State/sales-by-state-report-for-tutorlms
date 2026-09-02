=== Sales by State Report for Tutor LMS ===
Contributors: BusinessBloomer
Donate link: https://salesbystate.com/
Tags: sales-report, sales-by-state, tutor-lms, analytics, sales-tax
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

See a yearly breakdown of Tutor LMS sales by state / county / province for a given country, filterable by order status.

== Description ==

Sales by State Report for Tutor LMS adds a report showing net and gross sales grouped by state, county or province, for a chosen year and a chosen set of order statuses.

It appears under **Tutor LMS → Sales by State**.

It answers the question sales tax and territory planning actually ask: how much did each state buy in a given year, counting only the orders that matter.

This plugin requires [Tutor LMS](https://wordpress.org/plugins/tutor/) with native ecommerce enabled.

Documentation: [salesbystate.com](https://salesbystate.com/)

= What the report shows =

* Net Sales and Gross Sales for every state in the selected country
* A summary of both figures across all states
* Sortable columns and paginated results
* States with no sales, shown as zero rather than hidden

= Filters =

* **Country** — United States, Canada, and United Kingdom. Defaults to the United States.
* **Year** — a rolling list that starts ten years back and gains a year each January without dropping one. Defaults to the current year.
* **Order status** — a checkbox list of Tutor LMS order statuses. Defaults to Completed.

= How the figures are calculated =

Gross Sales is the order total Tutor LMS stores. Net Sales is that total minus tax. Tutor LMS native ecommerce has no shipping line, so shipping is not deducted.

Refunds are not modelled as separate records. An order that has been fully refunded is controlled by the status filter. A partial refund is not deducted from its order's total.

= Performance =

Sales for a whole year are answered by one indexed query that returns one row per state. The response size does not grow with the number of orders.

Existing Tutor LMS orders are copied into the report table once. After that import, opening or changing the report filters does not rescan the orders table.

= Data and privacy =

The plugin creates one custom database table holding, per order: the order ID, order status, creation and payment dates, billing country and state codes, currency, and the order, tax and net totals. It stores no names, addresses, email addresses or any other personal data.

It does not send data to any other service, includes no third-party analytics, and collects no telemetry.

Deleting the plugin removes the table and its options.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/sales-by-state-report-for-tutorlms`, or install it through the Plugins screen.
2. Activate the plugin. Tutor LMS must already be installed and active.
3. Go to **Tutor LMS → Sales by State**.

On a store that already has orders, those orders are read into the report table once. This starts on its own. If it has not finished when you open the report, a progress bar shows how far along it is.

== Frequently Asked Questions ==

= The report shows zeros but I have orders. =

Your existing orders are still being read into the report table. Open the report and the progress bar will show how far along it is. It continues on its own; you can leave the page.

If only Completed is selected, tick any other statuses that should count.

Tutor LMS native ecommerce must be the monetization engine. WooCommerce or EDD course sales are not included.

To confirm how much of the import has finished, open **Tools → Site Health → Info → Sales by State Report for Tutor LMS**.

= Where does the report appear? =

Under **Tutor LMS → Sales by State**. Administrators who can manage Tutor LMS can open it.

= Which address does it group by? =

The billing address stored on the Tutor LMS order. Native ecommerce does not keep a separate shipping address.

= Are refunds deducted? =

The status filter decides whether an order counts. Partial refunds are not deducted from the order's total.

= Which date does the year filter use? =

The date the order was paid when a payment date is present, otherwise the date the order was created.

= Can I change the default order status? =

Yes, with the `sbstl_default_statuses` filter.

= Where can I get support? =

Use the [WordPress.org support forum](https://wordpress.org/support/plugin/sales-by-state-report-for-tutorlms/) for this plugin.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
