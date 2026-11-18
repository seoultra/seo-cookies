# SEO Cookies

A tiny cookie banner with three clear choices: **Accept all**, **Necessary only** or **Reject all**.
It ships as a WordPress plugin and as a Composer package for any PHP app, and the browser script also
works on a plain HTML site.

- **Light:** one 12 KB script (4.7 KB gzipped), loaded with `defer`. No CSS file, fonts, images or jQuery.
- **Private:** no account, no API key, no external requests. The choice is one first-party cookie.
- **Google Consent Mode v2:** denied defaults before any Google tag, and the stored choice applied at once on repeat visits.
- **Holds tracking code** until the visitor allows it.
- **41 languages** built in, right-to-left ready. Bar or box, light or dark, your button colour.

Live demo and download: **https://seoultra.id/plugins/seo-cookies/**

## What each button allows

| Cookies | Accept all | Necessary only | Reject all |
|---|:-:|:-:|:-:|
| Necessary (login, cart, security) | ✓ | ✓ | ✓ |
| Functional (language, theme, preferences) | ✓ | ✓ | — |
| Analytics and ads | ✓ | — | — |

The choice is stored as `seo_cookies=<all|necessary|reject>.<revision>`. Changing the revision asks every visitor again.

## Install

### WordPress

Download the zip from the [plugin page](https://seoultra.id/plugins/seo-cookies/), then Plugins → Add New →
Upload Plugin, and activate it. Settings → SEO Cookies has the options. The `[seo_cookies]` shortcode prints a
"Cookie settings" link.

On Composer-managed WordPress (for example Bedrock), `composer require seoultra/seo-cookies` installs it into the plugins folder.

### Any PHP app

```bash
composer require seoultra/seo-cookies
```

Copy `vendor/seoultra/seo-cookies/assets/seo-cookies.js` to a public folder (or symlink it), then:

```php
use SeoUltra\SeoCookies\SeoCookies;

// Top of <head>: Google Consent Mode v2 (skip it if you use no Google tags)
echo SeoCookies::consentModeScript();

// Just before </body>
echo SeoCookies::footer('/js/seo-cookies.js', [
    'lang'    => 'id',               // any locale: id, pt_BR, zh-TW … (English if unknown)
    'privacy' => '/privacy-policy/',
    'layout'  => 'bar',              // bar | box-start | box-end
    'theme'   => 'light',            // light | dark
    'color'   => '#1a73e8',
]);

// Server side
if (SeoCookies::allows('analytics')) {
    // print the analytics tag
}
```

`footer()` also takes `texts` (your own wording), `rev`, `days`, `gcm`, `reopen` and `auto`. Both methods take a CSP nonce as the last argument.

### Plain HTML

Serve `assets/seo-cookies.js` and add, before `</body>`:

```html
<script>window.seoCookiesConfig = { privacy: "/privacy-policy/" };</script>
<script src="/js/seo-cookies.js" defer></script>
```

Without `t` (texts) in the config the banner is in English. If you use Google tags, put the output of
`SeoCookies::consentModeScript()` (a short inline script) at the top of `<head>`.

## Holding scripts until the visitor allows them

```html
<!-- Runs only after "Accept all" -->
<script type="text/plain" data-seo-cookies="analytics" data-src="https://example.com/tracker.js"></script>

<!-- Also allowed with "Necessary only" -->
<script type="text/plain" data-seo-cookies="functional">/* … */</script>

<!-- Iframes work the same way -->
<iframe data-seo-cookies="marketing" data-src="https://www.youtube-nocookie.com/embed/VIDEO_ID"></iframe>
```

In WordPress you can also paste tracking code into Settings → SEO Cookies → "Load after Accept all".

When a visitor takes consent back, common analytics and ad cookies (`_ga`, `_gid`, `_gcl_*`, `_fbp`, …) are
removed and the page reloads, because code that already ran cannot be un-run.

## JavaScript API

```js
seoCookies.get();               // 'all' | 'necessary' | 'reject' | ''
seoCookies.allows('analytics'); // true / false
seoCookies.open();              // show the banner again
document.addEventListener('seocookies:change', e => console.log(e.detail.choice));
```

Links to `#seo-cookies` and elements with `data-seo-cookies-open` open the banner. Google Tag Manager receives a
`seo_cookies_update` event.

## Development

```bash
composer test                                   # PHP helper tests (PHP 7.4+)
bin/build.sh <dir>                              # WordPress zip + browser assets for one version
python3 tests/wp_e2e.py http://localhost:8080/  # browser test against a scratch WordPress
```

Releasing: bump the version in `seo-cookies.php` (header and `SEO_COOKIES_VERSION`), `readme.txt` (Stable tag),
the first line of `assets/seo-cookies.js` and `SeoCookies::VERSION`; `bin/build.sh` refuses mismatches.

SEO Cookies is a technical tool, not legal advice. You decide which cookies your site uses and how your
privacy policy describes them.

## License

GPL-2.0-or-later. Made by [SEOULTRA](https://seoultra.id).
