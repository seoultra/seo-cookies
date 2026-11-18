<?php
/**
 * Plain-PHP tests for src/SeoCookies.php (no PHPUnit needed, runs on PHP 7.4+).
 *   php tests/php/run.php        or        composer test
 */
error_reporting(E_ALL);
set_error_handler(static function ($no, $str, $file, $line) {
    throw new ErrorException($str, 0, $no, $file, $line);
});
require dirname(__DIR__, 2) . '/src/SeoCookies.php';

use SeoUltra\SeoCookies\SeoCookies;

$fails = 0;
$n = 0;
function check(string $name, bool $ok, $info = null): void
{
    global $fails, $n;
    $n++;
    if (!$ok) {
        $fails++;
        echo "FAIL $name" . ($info === null ? '' : '  ' . var_export($info, true)) . "\n";
    }
}

// Dictionary
$langs = SeoCookies::languages();
check('41 languages', count($langs) === 41, count($langs));
foreach ($langs as $l) {
    $t = SeoCookies::texts($l);
    check("[$l] 8 non-empty strings", count(array_filter($t, 'strlen')) === 8, $t);
}
check('en labels', SeoCookies::texts('en')['all'] === 'Accept all' && SeoCookies::texts('en')['reject'] === 'Reject all');

// Locale mapping
$map = ['id_ID' => 'id', 'pt_BR' => 'pt', 'zh_CN' => 'zh', 'zh_TW' => 'zh-tw', 'zh-HK' => 'zh-tw', 'zh_Hant' => 'zh-tw', 'yue' => 'zh-tw',
        'nb_NO' => 'nb', 'nn_NO' => 'nb', 'fil' => 'tl', 'bel' => 'be', 'de_DE_formal' => 'de', 'es-MX' => 'es', 'xx_XX' => 'en', '' => 'en', 'EN' => 'en'];
foreach ($map as $in => $want) {
    check("languageKey($in)", SeoCookies::languageKey($in) === $want, SeoCookies::languageKey($in));
}
check('unknown locale falls back to English text', SeoCookies::texts('xx')['all'] === 'Accept all');

// Stored choice
check('no cookie = no choice', SeoCookies::choice(1, []) === '');
check('all.1', SeoCookies::choice(1, ['seo_cookies' => 'all.1']) === 'all');
check('stale revision ignored', SeoCookies::choice(2, ['seo_cookies' => 'all.1']) === '');
check('junk ignored', SeoCookies::choice(1, ['seo_cookies' => 'all.1x']) === '' && SeoCookies::choice(1, ['seo_cookies' => ['x']]) === '');
check('allows: all → analytics', SeoCookies::allows('analytics', 1, ['seo_cookies' => 'all.1']));
check('allows: necessary → functional, not analytics', SeoCookies::allows('functional', 1, ['seo_cookies' => 'necessary.1']) && !SeoCookies::allows('analytics', 1, ['seo_cookies' => 'necessary.1']));
check('allows: reject → nothing', !SeoCookies::allows('functional', 1, ['seo_cookies' => 'reject.1']) && !SeoCookies::allows('preferences', 1, ['seo_cookies' => 'reject.1']));
check('allows: no choice → nothing', !SeoCookies::allows('functional', 1, []));

// Consent Mode snippet
$cm = SeoCookies::consentModeScript(3, 'abc"x');
check('consent snippet: denied defaults', strpos($cm, '"consent","default"') !== false && strpos($cm, 'analytics_storage:D') !== false);
check('consent snippet: revision', strpos($cm, 'm[2]==="3"') !== false);
check('consent snippet: nonce escaped', strpos($cm, 'nonce="abc&quot;x"') !== false);

// Footer
$f = SeoCookies::footer('/js/seo-cookies.js?v=1&x="y"', [
    'lang' => 'id', 'privacy' => 'javascript:alert(1)', 'texts' => ['title' => '</script><b>x', 'bogus' => 'no'],
    'layout' => 'sideways', 'color' => 'red;}', 'rev' => 2, 'auto' => false,
]);
preg_match('/window\.seoCookiesConfig=(\{.*?\});<\/script>/s', $f, $m);
$cfg = json_decode($m[1] ?? 'null', true);
check('footer: valid JSON config', is_array($cfg), $f);
check('footer: Indonesian text', ($cfg['t']['all'] ?? '') === 'Terima semua');
check('footer: </script> cannot break out', strpos($f, '</script><b>') === false && ($cfg['t']['title'] ?? '') === '</script><b>x');
check('footer: unknown text key dropped', !isset($cfg['t']['bogus']));
check('footer: javascript: privacy URL dropped', ($cfg['privacy'] ?? null) === '');
check('footer: bad layout/color fall back', $cfg['layout'] === 'bar' && $cfg['color'] === '#1a73e8');
check('footer: rev and auto passed', $cfg['v'] === 2 && $cfg['auto'] === false);
check('footer: script URL escaped', strpos($f, 'src="/js/seo-cookies.js?v=1&amp;x=&quot;y&quot;" defer') !== false, $f);
$f2 = SeoCookies::footer('/s.js', ['privacy' => '/privacy-policy/']);
check('footer: relative privacy URL kept', strpos($f2, '"privacy":"/privacy-policy/"') !== false, $f2);
check('footer: default English', strpos($f2, '"all":"Accept all"') !== false);

// Package wiring
check('scriptPath exists', is_file(SeoCookies::scriptPath()));
check('script and class versions match', strpos((string) file_get_contents(SeoCookies::scriptPath()), 'SEO Cookies ' . SeoCookies::VERSION . ' ') !== false);

echo $fails ? "$fails of $n checks FAILED\n" : "OK ($n checks)\n";
exit($fails ? 1 : 0);
