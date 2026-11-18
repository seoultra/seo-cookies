#!/usr/bin/env python3
"""Browser test of SEO Cookies on a running scratch WordPress.

Setup: plugin active; tests/mu-plugin fixture (held analytics + functional scripts,
a marketing iframe, the [seo_cookies] shortcode) in wp-content/mu-plugins; option
'scripts' = '<script>window.__tpl=(window.__tpl||0)+1;</script><div id="tpl-div">x</div>'.

Usage: python3 wp_e2e.py http://127.0.0.1:8793/
"""
import sys
from playwright.sync_api import sync_playwright

ok = True


def check(name, cond, info=''):
    global ok
    ok = ok and bool(cond)
    print(('  ok   ' if cond else '  FAIL ') + name + ('' if cond else '  ' + repr(info)))


FLAGS = "() => [window.__an || 0, window.__fn || 0, window.__tpl || 0, !!document.getElementById('tpl-div'), document.getElementById('tframe').getAttribute('src')]"
CONSENT = "() => (window.dataLayer || []).filter(a => a && a[0] === 'consent').map(a => [a[1], a[2].analytics_storage, a[2].functionality_storage, a[2].ad_storage])"


def cookie(ctx, name='seo_cookies'):
    return next((c['value'] for c in ctx.cookies() if c['name'] == name), None)


def main(url):
    with sync_playwright() as p:
        b = p.chromium.launch()
        ctx = b.new_context(viewport={'width': 1280, 'height': 800})
        pg = ctx.new_page()
        errs = []
        pg.on('pageerror', lambda e: errs.append(str(e)))
        pg.on('console', lambda m: m.type == 'error' and errs.append(m.text))

        pg.goto(url, wait_until='load')
        pg.wait_for_function('window.seoCookies')
        check('banner shows on first visit', pg.locator('.sck').is_visible())
        labels = pg.locator('.sck .sck-btn').all_inner_texts()
        check('three buttons in order', labels == ['Accept all', 'Necessary only', 'Reject all'], labels)
        check('banner is first in tab order', pg.evaluate("() => document.body.firstElementChild.classList.contains('sck')"))
        check('privacy link = WP privacy page', '/?page_id=3' in (pg.locator('.sck a').get_attribute('href') or ''))
        check('nothing held ran before a choice', pg.evaluate(FLAGS) == [0, 0, 0, False, None], pg.evaluate(FLAGS))
        check('consent default = all denied, no update', pg.evaluate(CONSENT) == [['default', 'denied', 'denied', 'denied']], pg.evaluate(CONSENT))
        check('no reopen button yet', pg.locator('.sck-r').count() == 0)
        pg.screenshot(path=sys.argv[2] + '_1_first.png')

        # Necessary only
        pg.click('.sck [data-c=necessary]')
        check('banner gone', pg.locator('.sck').count() == 0)
        check('cookie necessary.1', cookie(ctx) == 'necessary.1', cookie(ctx))
        check('functional ran, analytics/template/iframe did not', pg.evaluate(FLAGS) == [0, 1, 0, False, None], pg.evaluate(FLAGS))
        check('consent update functional granted, analytics denied', pg.evaluate(CONSENT)[-1] == ['update', 'denied', 'granted', 'denied'], pg.evaluate(CONSENT))
        check('reopen button visible', pg.locator('.sck-r').is_visible())

        # Repeat visit
        pg.reload(wait_until='load')
        pg.wait_for_function('window.seoCookies')
        check('no banner on repeat visit', pg.locator('.sck').count() == 0)
        check('head snippet updates consent before tags', pg.evaluate(CONSENT) == [['default', 'denied', 'denied', 'denied'], ['update', 'denied', 'granted', 'denied']], pg.evaluate(CONSENT))
        check('functional runs on repeat visit', pg.evaluate(FLAGS)[1] == 1, pg.evaluate(FLAGS))
        check('seoCookies.get()', pg.evaluate('seoCookies.get()') == 'necessary')

        # Reopen, see the current choice, close with X and Escape
        pg.click('.sck-r')
        check('reopen shows banner, focus on first button', pg.evaluate("() => document.activeElement && document.activeElement.dataset.c") == 'all')
        check('current choice marked', pg.locator('.sck [aria-current=true]').get_attribute('data-c') == 'necessary')
        check('reopen button hidden while open', not pg.locator('.sck-r').is_visible())
        pg.screenshot(path=sys.argv[2] + '_2_reopen.png')
        pg.click('.sck-x')
        check('X closes, keeps choice', pg.locator('.sck').count() == 0 and cookie(ctx) == 'necessary.1')
        pg.click('.sck-r'); pg.keyboard.press('Escape')
        check('Escape closes when a choice exists', pg.locator('.sck').count() == 0)

        # Upgrade to Accept all — no reload needed
        ctx.add_cookies([{'name': '_ga', 'value': 'GA1.1.1', 'url': url}, {'name': 'keepme', 'value': '1', 'url': url}])
        pg.evaluate('window.__marker = 1')
        pg.click('a.seo-cookies-link')
        check('shortcode link opens banner', pg.locator('.sck').is_visible())
        pg.click('.sck [data-c=all]')
        check('cookie all.1', cookie(ctx) == 'all.1', cookie(ctx))
        check('analytics, template and iframe ran; functional not twice', pg.evaluate(FLAGS) == [1, 1, 1, True, 'about:blank#ok'], pg.evaluate(FLAGS))
        check('no reload on upgrade', pg.evaluate('window.__marker') == 1)
        check('consent update all granted', pg.evaluate(CONSENT)[-1] == ['update', 'granted', 'granted', 'granted'], pg.evaluate(CONSENT))
        check('GTM event pushed', pg.evaluate("() => dataLayer.some(e => e && e.event === 'seo_cookies_update' && e.seo_cookies === 'all')"))

        # Downgrade to Reject all — tracker cookies wiped, page reloads
        pg.click('.sck-r')
        with pg.expect_navigation(wait_until='load'):
            pg.click('.sck [data-c=reject]')
        pg.wait_for_function('window.seoCookies')
        names = [c['name'] for c in ctx.cookies()]
        check('cookie reject.1', cookie(ctx) == 'reject.1', cookie(ctx))
        check('_ga wiped, unrelated cookie kept', '_ga' not in names and 'keepme' in names, names)
        check('after reload nothing optional runs', pg.evaluate(FLAGS) == [0, 0, 0, False, None], pg.evaluate(FLAGS))
        check('consent update all denied incl. functional', pg.evaluate(CONSENT)[-1] == ['update', 'denied', 'denied', 'denied'], pg.evaluate(CONSENT))

        # Mobile layout
        m = b.new_context(viewport={'width': 360, 'height': 740}, is_mobile=True)
        mp = m.new_page()
        mp.goto(url, wait_until='load'); mp.wait_for_function('window.seoCookies')
        box = mp.locator('.sck').bounding_box()
        check('mobile banner fits the screen', box and box['x'] >= 0 and box['x'] + box['width'] <= 360, box)
        check('no horizontal scroll on mobile', mp.evaluate('document.documentElement.scrollWidth') <= 360)
        mp.screenshot(path=sys.argv[2] + '_3_mobile.png')
        check('no JS errors', not errs, errs)
        b.close()
    print('PASS' if ok else 'FAILED')
    sys.exit(0 if ok else 1)


if __name__ == '__main__':
    main(sys.argv[1])
