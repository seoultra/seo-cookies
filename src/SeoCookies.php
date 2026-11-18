<?php
/**
 * SEO Cookies — the same three-button cookie banner as the WordPress plugin, for any PHP app.
 *
 *   echo SeoCookies::consentModeScript();                                  // top of <head>
 *   echo SeoCookies::footer('/js/seo-cookies.js', ['lang' => 'id']);       // before </body>
 *   if (SeoCookies::allows('analytics')) { … }                            // server-side check
 *
 * Serve assets/seo-cookies.js yourself (see scriptPath()). Nothing here talks to the network.
 *
 * @license GPL-2.0-or-later
 */

namespace SeoUltra\SeoCookies;

final class SeoCookies
{
    public const VERSION = '1.1.0';
    public const COOKIE  = 'seo_cookies';
    public const CHOICES = ['all', 'necessary', 'reject'];
    private const KEYS   = ['title', 'text', 'all', 'necessary', 'reject', 'policy', 'settings', 'close'];
    private const ALIAS  = ['zh_tw' => 'zh-tw', 'zh_hk' => 'zh-tw', 'zh_hant' => 'zh-tw', 'yue' => 'zh-tw', 'bel' => 'be', 'fil' => 'tl', 'nn' => 'nb', 'no' => 'nb'];

    /** @var array<string, list<string>>|null */
    private static $dict = null;

    /** Language keys with built-in banner text (en, id, zh, zh-tw, ja, …). */
    public static function languages(): array
    {
        return array_keys(self::dict());
    }

    /**
     * Built-in banner text for a language code or locale ("id", "pt_BR", "zh-TW", "fil"),
     * falling back to English. Returns title, text, all, necessary, reject, policy, settings, close.
     */
    public static function texts(string $locale = 'en'): array
    {
        $dict = self::dict();
        return array_combine(self::KEYS, $dict[self::languageKey($locale)] ?? $dict['en']);
    }

    /** Maps a locale to the dictionary key texts() uses ("pt_BR" → "pt", "zh_TW" → "zh-tw", unknown → "en"). */
    public static function languageKey(string $locale): string
    {
        $l = strtolower(str_replace('-', '_', trim($locale)));
        foreach ([substr($l, 0, 7), substr($l, 0, 5), (string) strtok($l, '_')] as $try) {
            $try = self::ALIAS[$try] ?? $try;
            if (isset(self::dict()[$try])) {
                return $try;
            }
        }
        return 'en';
    }

    /**
     * Google Consent Mode v2: denied defaults before any Google tag, and the stored choice applied
     * straight away on repeat visits. Print it first in <head>. Skip it if you use no Google tags.
     */
    public static function consentModeScript(int $rev = 1, string $nonce = ''): string
    {
        $js = '(function(w,d){w.dataLayer=w.dataLayer||[];if(typeof w.gtag!=="function")w.gtag=function(){w.dataLayer.push(arguments)};'
            . 'var D="denied",G="granted",m=d.cookie.match(/(?:^|;\s*)' . self::COOKIE . '=(all|necessary|reject)\.(\d+)/),c=m&&m[2]==="' . max(1, $rev) . '"?m[1]:"";'
            . 'w.gtag("consent","default",{ad_storage:D,ad_user_data:D,ad_personalization:D,analytics_storage:D,functionality_storage:D,personalization_storage:D,security_storage:G,wait_for_update:500});'
            . 'w.gtag("set","ads_data_redaction",true);'
            . 'if(c){var a=c==="all"?G:D,f=c==="reject"?D:G;w.gtag("consent","update",{ad_storage:a,ad_user_data:a,ad_personalization:a,analytics_storage:a,functionality_storage:f,personalization_storage:f,security_storage:G})}'
            . '})(window,document);';
        return '<script' . self::nonceAttr($nonce) . '>' . $js . "</script>\n";
    }

    /**
     * The banner: config + script tag. Print it just before </body>.
     *
     * @param string $scriptUrl Where you serve assets/seo-cookies.js (copy it from scriptPath()).
     * @param array  $options   lang, texts (overrides), privacy (URL), layout (bar|box-start|box-end),
     *                          theme (light|dark), color (#hex), gcm, reopen, days, rev, auto.
     */
    public static function footer(string $scriptUrl, array $options = [], string $nonce = ''): string
    {
        $layout = $options['layout'] ?? 'bar';
        $color  = (string) ($options['color'] ?? '#1a73e8');
        $config = [
            'name'    => self::COOKIE,
            'v'       => max(1, (int) ($options['rev'] ?? 1)),
            'days'    => max(1, min(395, (int) ($options['days'] ?? 180))),
            'layout'  => in_array($layout, ['bar', 'box-start', 'box-end'], true) ? $layout : 'bar',
            'theme'   => ($options['theme'] ?? 'light') === 'dark' ? 'dark' : 'light',
            'color'   => preg_match('/^#[0-9a-fA-F]{3,8}$/', $color) ? $color : '#1a73e8',
            'gcm'     => (bool) ($options['gcm'] ?? true),
            'reopen'  => (bool) ($options['reopen'] ?? true),
            'privacy' => self::safeUrl((string) ($options['privacy'] ?? '')),
            't'       => array_merge(self::texts((string) ($options['lang'] ?? 'en')), array_intersect_key((array) ($options['texts'] ?? []), array_flip(self::KEYS))),
        ];
        if (array_key_exists('auto', $options)) {
            $config['auto'] = (bool) $options['auto'];
        }
        $json = json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $n = self::nonceAttr($nonce);
        return '<script' . $n . '>window.seoCookiesConfig=' . $json . ";</script>\n"
            . '<script' . $n . ' src="' . htmlspecialchars($scriptUrl, ENT_QUOTES) . '" defer></script>' . "\n";
    }

    /** The visitor's stored choice — 'all', 'necessary', 'reject' — or '' when they have not chosen yet. */
    public static function choice(int $rev = 1, ?array $cookies = null): string
    {
        $raw = ($cookies ?? $_COOKIE)[self::COOKIE] ?? '';
        return is_string($raw) && preg_match('/^(all|necessary|reject)\.(\d+)$/', $raw, $m) && (int) $m[2] === max(1, $rev) ? $m[1] : '';
    }

    /**
     * Whether the stored choice allows a category. "functional" and "preferences" are allowed by
     * Accept all and Necessary only; anything else (analytics, marketing, …) only by Accept all.
     */
    public static function allows(string $category, int $rev = 1, ?array $cookies = null): bool
    {
        $c = self::choice($rev, $cookies);
        return $c === 'all' || ($c === 'necessary' && in_array($category, ['functional', 'preferences'], true));
    }

    /** Absolute path of the browser script in this package. Copy or symlink it into your public folder. */
    public static function scriptPath(): string
    {
        return dirname(__DIR__) . '/assets/seo-cookies.js';
    }

    private static function dict(): array
    {
        if (self::$dict === null) {
            if (!defined('SEO_COOKIES_LIB')) {
                define('SEO_COOKIES_LIB', true);
            }
            self::$dict = require dirname(__DIR__) . '/includes/texts.php';
        }
        return self::$dict;
    }

    private static function safeUrl(string $url): string
    {
        $url = trim($url);
        return $url === '' || preg_match('~^(https?:)?//|^/(?!/)|^\.{0,2}/|^[^:]*$~i', $url) && !preg_match('~^\s*(javascript|data|vbscript):~i', $url) ? $url : '';
    }

    private static function nonceAttr(string $nonce): string
    {
        return $nonce === '' ? '' : ' nonce="' . htmlspecialchars($nonce, ENT_QUOTES) . '"';
    }
}
