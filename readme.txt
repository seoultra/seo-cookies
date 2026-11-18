=== SEO Cookies ===
Contributors: seoultra
Tags: cookie banner, cookie consent, gdpr, consent mode, cookies
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A tiny cookie banner with three clear choices: Accept all, Necessary only or Reject all. Google Consent Mode v2 built in.

== Description ==

SEO Cookies shows a small banner the first time someone visits your site. The visitor picks one of three buttons and the banner goes away:

* **Accept all** – everything, including analytics and ads.
* **Necessary only** – cookies the site needs, plus functional ones such as language or theme. No analytics, no ads.
* **Reject all** – nothing optional at all.

The choice is stored in one first-party cookie (`seo_cookies`) in the visitor's browser. A small cookie button lets them change their mind at any time.

= Light by design =

* One small script (12 KB, 4.7 KB gzipped), loaded with `defer` at the end of the page. No CSS file, no fonts, no images, no jQuery.
* No account, no API key, no external requests, no tracking.
* One settings screen, one database option.

= What it does for you =

* **Google Consent Mode v2.** Consent defaults are sent before any Google tag (Site Kit, GA4, Google Ads, Tag Manager) and updated the moment the visitor chooses. Repeat visitors are updated straight away.
* **Holds tracking code until "Accept all".** Paste your analytics or pixel code into the settings and it only runs after consent. In theme code you can hold any script with `type="text/plain" data-seo-cookies="analytics"`.
* **WP Consent API.** If the WP Consent API plugin is active, other plugins (for example WooCommerce or Site Kit) learn the visitor's choice too.
* **Clean change of mind.** When a visitor withdraws consent, common analytics and ad cookies (`_ga`, `_gid`, `_fbp`, `_gcl_*`, …) are removed and the page reloads so tags stop.
* **Speaks your site's language.** Built-in banner text in 41 languages, picked from the WordPress site language. Right-to-left sites are supported.
* **Bar or box, light or dark,** in your button colour.

SEO Cookies is a technical tool, not legal advice. You decide which cookies your site uses and how your privacy policy describes them.

== Installation ==

1. Upload the plugin from Plugins → Add New → Upload Plugin, or unzip it into `wp-content/plugins/`.
2. Activate it. The banner appears right away with safe defaults.
3. Optional: open Settings → SEO Cookies to change the layout, colour, text or the code that loads after "Accept all".
4. Optional: add the `[seo_cookies]` shortcode to your footer or privacy page for a "Cookie settings" link.

== Frequently Asked Questions ==

= What is the difference between "Necessary only" and "Reject all"? =

"Necessary only" keeps functional cookies, such as a remembered language or theme, while analytics and ads stay off. "Reject all" turns off everything optional, including those functional cookies.

= Does it block Google Analytics? =

With Google Consent Mode v2 on (the default), Google tags wait for consent and send cookieless signals until the visitor accepts. To keep a tag from loading at all, put its code into "Load after Accept all" instead of your theme.

= How do I hold a script in my theme? =

Change `<script src="…">` to `<script type="text/plain" data-seo-cookies="analytics" data-src="…">`. Use `data-seo-cookies="functional"` for scripts that may also run with "Necessary only". Iframes work the same way with `data-src`.

= Can I react to the choice in my own code? =

Yes. Listen for `document.addEventListener('seocookies:change', e => e.detail.choice)` or call `seoCookies.get()`, `seoCookies.open()` and `seoCookies.allows('analytics')`. Google Tag Manager receives a `seo_cookies_update` event.

= Can I install it with Composer? =

Yes: `composer require seoultra/seo-cookies`. On Composer-managed WordPress (for example Bedrock) it installs as a plugin. In other PHP apps the `SeoUltra\SeoCookies\SeoCookies` class prints the same banner.

= Does it work with page caching? =

Yes. The banner is drawn in the browser, so cached pages work for every visitor.

== External services ==

This plugin does not connect to any external service. If you paste third-party code into "Load after Accept all", that code runs only after the visitor accepts, and that third party's own terms apply.

== Changelog ==

= 1.1.0 =
* Available on Packagist as seoultra/seo-cookies (installs as a plugin on Composer-managed WordPress).
* New plain-PHP helper class, shared with the plugin.

= 1.0.0 =
* First release.
