# Changelog

## 1.1.0 — 2026-11-18
- Composer package `seoultra/seo-cookies` (type `wordpress-plugin`, so Bedrock-style sites install it as a plugin).
- `SeoUltra\SeoCookies\SeoCookies`: the same banner for any PHP app — `consentModeScript()`, `footer()`, `texts()`, `choice()`, `allows()`, `scriptPath()`.
- The WordPress plugin now uses that class, so the Consent Mode code and the language picking live in one place.
- The browser script falls back to English text when a config brings none (plain HTML sites).

## 1.0.0 — 2026-11-18
- First release: Accept all / Necessary only / Reject all banner, Google Consent Mode v2, WP Consent API, held scripts, 41 languages.
