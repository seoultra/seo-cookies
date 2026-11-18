/*! SEO Cookies 1.1.0 | GPL-2.0-or-later | https://seoultra.id/plugins/seo-cookies/ */
(function (w, d) {
  'use strict';
  var C = w.seoCookiesConfig;
  if (!C || w.seoCookies) return;

  var NAME = C.name || 'seo_cookies';
  var REV = String(C.v || 1);
  // English text unless the config brings its own (the WordPress plugin and PHP helper always do).
  var T = { title: 'We use cookies', text: 'Some cookies keep this site working. With your OK, we also use cookies to measure traffic and show relevant ads.',
    all: 'Accept all', necessary: 'Necessary only', reject: 'Reject all', policy: 'Privacy policy', settings: 'Cookie settings', close: 'Close' };
  for (var k in (C.t || {})) if (C.t[k]) T[k] = C.t[k];
  var CHOICES = { all: 1, necessary: 1, reject: 1 };
  // Cookies set by common analytics / ad tags. Wiped when a visitor takes consent back.
  var TRACKERS = /^(_ga|_gid|_gat|_gcl_|_fbp|_fbc|_hj|_clck|_clsk|_uet|_ttp|__utm)/;
  var ICON = '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 3a9 9 0 1 0 9 9 3.5 3.5 0 0 1-4.5-4.5A3.5 3.5 0 0 1 12 3z"/><path d="M8.5 9.5h.01M15 15.5h.01M11 13h.01M8 15.5h.01M16 11h.01"/></svg>';
  var CSS =
    '.sck,.sck-r{--sck-bg:#fff;--sck-fg:#1f2328;--sck-mu:#4b5563;--sck-bd:rgba(31,35,40,.14);font:14px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans",sans-serif}' +
    '.sck-dark{--sck-bg:#1c2128;--sck-fg:#f0f3f6;--sck-mu:#b6bec8;--sck-bd:rgba(240,246,252,.16)}' +
    '.sck{position:fixed;z-index:2147483000;bottom:12px;inset-inline:12px;margin:0 auto;max-width:1080px;box-sizing:border-box;padding:14px 16px;background:var(--sck-bg);color:var(--sck-fg);border:1px solid var(--sck-bd);border-radius:14px;box-shadow:0 12px 40px rgba(0,0,0,.2);text-align:start;animation:sck-up .25s ease-out}' +
    '.sck *{box-sizing:border-box}' +
    '.sck-in{display:flex;flex-wrap:wrap;align-items:center;gap:12px 20px}' +
    '.sck .sck-t{flex:1 1 340px;margin:0;padding:0;color:var(--sck-mu);font-size:14px}' +
    '.sck .sck-t strong{display:block;margin:0 0 2px;color:var(--sck-fg);font-size:15px;font-weight:700}' +
    '.sck a{color:inherit;text-decoration:underline;text-underline-offset:2px;font-weight:600}' +
    '.sck .sck-b{display:flex;flex-wrap:wrap;gap:8px;flex:0 1 auto;margin:0}' +
    '.sck .sck-btn,.sck .sck-x,.sck-r{all:unset;box-sizing:border-box;cursor:pointer;-webkit-tap-highlight-color:transparent}' +
    '.sck .sck-btn{flex:1 1 auto;min-height:40px;padding:9px 16px;border-radius:10px;border:1.5px solid var(--sck);color:var(--sck-fg);background:transparent;font-weight:600;font-size:14px;line-height:1.2;text-align:center;white-space:nowrap}' +
    '.sck .sck-p{background:var(--sck);color:#fff}' +
    '.sck .sck-btn:hover{box-shadow:inset 0 0 0 99px rgba(127,127,127,.12)}' +
    '.sck .sck-on:after{content:" \\2713"}' +
    '.sck .sck-x{position:absolute;top:6px;inset-inline-end:6px;width:30px;height:30px;border-radius:8px;display:grid;place-items:center;color:var(--sck-mu);font-size:22px;line-height:1}' +
    '.sck .sck-x:hover{color:var(--sck-fg);background:rgba(127,127,127,.12)}' +
    '.sck.sck-has-x{padding-inline-end:40px}' +
    '.sck .sck-btn:focus-visible,.sck .sck-x:focus-visible,.sck a:focus-visible,.sck-r:focus-visible{outline:3px solid var(--sck);outline-offset:2px}' +
    '.sck-box{max-width:400px;margin:0}' +
    '.sck-box.sck-start{inset-inline-end:auto}' +
    '.sck-box.sck-end{inset-inline-start:auto}' +
    '.sck-box .sck-b{flex:1 1 100%}' +
    '.sck-box .sck-p{flex-basis:100%}' +
    '.sck-r{position:fixed;z-index:2147482999;bottom:14px;inset-inline-start:14px;width:44px;height:44px;border-radius:50%;display:grid;place-items:center;background:var(--sck-bg);color:var(--sck);border:1px solid var(--sck-bd);box-shadow:0 4px 16px rgba(0,0,0,.18)}' +
    '.sck-r.sck-end{inset-inline-start:auto;inset-inline-end:14px}' +
    '.sck-r svg{display:block;width:24px;height:24px}' +
    '.sck-r[hidden]{display:none}' +
    '@keyframes sck-up{from{transform:translateY(16px);opacity:0}}' +
    '@media (max-width:600px){.sck,.sck-box{inset-inline:8px;bottom:8px;max-width:none;padding:12px}.sck .sck-b{flex:1 1 100%}.sck .sck-p{flex-basis:100%}}' +
    '@media (prefers-reduced-motion:reduce){.sck{animation:none}}';

  var el = null, btn = null, current = '';

  function read() {
    var m = d.cookie.match(new RegExp('(?:^|;\\s*)' + NAME + '=(all|necessary|reject)\\.(\\d+)'));
    return m && m[2] === REV ? m[1] : '';
  }

  function write(c) {
    d.cookie = NAME + '=' + c + '.' + REV + ';max-age=' + Math.round((C.days || 180) * 86400) +
      ';path=/;samesite=lax' + (location.protocol === 'https:' ? ';secure' : '');
  }

  // "Accept all" allows everything. "Necessary only" keeps functional cookies
  // (language, theme, saved preferences). "Reject all" allows nothing optional.
  function allows(cat, c) {
    return c === 'all' || (c === 'necessary' && (cat === 'functional' || cat === 'preferences'));
  }

  function gcm(c) {
    var G = 'granted', D = 'denied', a = c === 'all' ? G : D, f = c === 'reject' ? D : G;
    return { ad_storage: a, ad_user_data: a, ad_personalization: a, analytics_storage: a,
      functionality_storage: f, personalization_storage: f, security_storage: G };
  }

  function google(c) {
    if (!C.gcm) return;
    w.dataLayer = w.dataLayer || [];
    if (typeof w.gtag !== 'function') w.gtag = function () { w.dataLayer.push(arguments); };
    w.gtag('consent', 'update', gcm(c));
    w.dataLayer.push({ event: 'seo_cookies_update', seo_cookies: c });
  }

  // WP Consent API (wordpress.org/plugins/wp-consent-api): only writes what changed.
  function consentApi(c) {
    if (typeof w.wp_set_consent !== 'function') return;
    var map = { functional: 'allow', preferences: c === 'reject' ? 'deny' : 'allow',
      statistics: c === 'all' ? 'allow' : 'deny', 'statistics-anonymous': c === 'all' ? 'allow' : 'deny',
      marketing: c === 'all' ? 'allow' : 'deny' };
    for (var k in map) {
      if (typeof w.wp_has_consent !== 'function' || w.wp_has_consent(k) !== (map[k] === 'allow')) w.wp_set_consent(k, map[k]);
    }
  }

  function clone(s) {
    var n = d.createElement('script'), i, a, src = s.getAttribute('data-src');
    for (i = 0; i < s.attributes.length; i++) {
      a = s.attributes[i];
      if (a.name !== 'type' && a.name !== 'data-seo-cookies' && a.name !== 'data-src') n.setAttribute(a.name, a.value);
    }
    if (s.getAttribute('data-type')) n.type = s.getAttribute('data-type');
    if (src) n.src = src;
    if (n.src) { if (!s.hasAttribute('async')) n.async = false; } else n.text = s.text || s.textContent;
    return n;
  }

  // Wakes up what the choice allows: <script type="text/plain" data-seo-cookies="analytics">,
  // <iframe data-seo-cookies="marketing" data-src="…">, and the "load after Accept all" snippet.
  function run(c) {
    var list = d.querySelectorAll('[data-seo-cookies]'), i, s, f, ss, tpl;
    for (i = 0; i < list.length; i++) {
      s = list[i];
      if (!allows(s.getAttribute('data-seo-cookies'), c)) continue;
      if (s.tagName === 'SCRIPT') {
        if (s.type === 'text/plain') s.parentNode.replaceChild(clone(s), s);
      } else if (s.getAttribute('data-src') && !s.getAttribute('src')) {
        s.setAttribute('src', s.getAttribute('data-src'));
      }
    }
    tpl = d.getElementById('seo-cookies-scripts');
    if (c === 'all' && tpl && tpl.content) {
      f = d.importNode(tpl.content, true);
      ss = f.querySelectorAll('script');
      for (i = 0; i < ss.length; i++) ss[i].parentNode.replaceChild(clone(ss[i]), ss[i]);
      tpl.parentNode.removeChild(tpl);
      d.body.appendChild(f);
    }
  }

  function wipe() {
    var parts = location.hostname.split('.'), doms = [''], i;
    for (i = 0; i < parts.length - 1; i++) doms.push(';domain=.' + parts.slice(i).join('.'));
    d.cookie.split(';').forEach(function (p) {
      var k = p.split('=')[0].replace(/^\s+|\s+$/g, '');
      if (TRACKERS.test(k)) doms.forEach(function (x) { d.cookie = k + '=;max-age=0;path=/' + x; });
    });
  }

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (ch) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
    });
  }

  function style() {
    if (d.getElementById('sck-css')) return;
    var s = d.createElement('style');
    s.id = 'sck-css';
    s.textContent = CSS;
    (d.head || d.documentElement).appendChild(s);
  }

  function side() { return C.layout === 'box-end' ? 'end' : 'start'; }

  function hide() {
    if (el && el.parentNode) el.parentNode.removeChild(el);
    el = null;
    if (btn) btn.hidden = false;
  }

  function show(focus) {
    style();
    if (el && el.parentNode) el.parentNode.removeChild(el);
    var box = C.layout === 'box-start' || C.layout === 'box-end';
    var b = function (c, primary) {
      return '<button type="button" class="sck-btn' + (primary ? ' sck-p' : '') + (current === c ? ' sck-on' : '') +
        '" data-c="' + c + '"' + (current === c ? ' aria-current="true"' : '') + '>' + esc(T[c]) + '</button>';
    };
    el = d.createElement('div');
    el.className = 'sck ' + (box ? 'sck-box sck-' + side() : 'sck-bar') + (C.theme === 'dark' ? ' sck-dark' : '') + (current ? ' sck-has-x' : '');
    el.setAttribute('role', 'dialog');
    el.setAttribute('aria-labelledby', 'sck-title');
    el.setAttribute('aria-describedby', 'sck-text');
    el.style.setProperty('--sck', C.color || '#1a73e8');
    el.innerHTML = '<div class="sck-in"><p class="sck-t"><strong id="sck-title">' + esc(T.title) + '</strong> <span id="sck-text">' +
      esc(T.text) + '</span>' + (C.privacy ? ' <a href="' + esc(C.privacy) + '">' + esc(T.policy) + '</a>' : '') +
      '</p><div class="sck-b">' + b('all', 1) + b('necessary') + b('reject') + '</div></div>' +
      (current ? '<button type="button" class="sck-x" aria-label="' + esc(T.close) + '">&times;</button>' : '');
    el.addEventListener('click', function (e) {
      var t = e.target.closest ? e.target.closest('button') : null;
      if (!t) return;
      if (t.className === 'sck-x') hide(); else choose(t.getAttribute('data-c'));
    });
    el.addEventListener('keydown', function (e) { if (current && (e.key === 'Escape' || e.key === 'Esc')) hide(); });
    d.body.insertBefore(el, d.body.firstChild);
    if (btn) btn.hidden = true;
    if (focus) el.querySelector('.sck-btn').focus();
  }

  function reopen() {
    if (!C.reopen) return;
    if (btn) { btn.hidden = !!el; return; }
    style();
    btn = d.createElement('button');
    btn.type = 'button';
    btn.className = 'sck-r sck-' + side() + (C.theme === 'dark' ? ' sck-dark' : '');
    btn.setAttribute('aria-label', T.settings || 'Cookie settings');
    btn.title = T.settings || 'Cookie settings';
    btn.style.setProperty('--sck', C.color || '#1a73e8');
    btn.innerHTML = ICON;
    btn.addEventListener('click', function () { show(true); });
    btn.hidden = !!el;
    d.body.appendChild(btn);
  }

  function choose(c) {
    if (!CHOICES[c]) return;
    var prev = current;
    current = c;
    write(c);
    hide();
    google(c);
    consentApi(c);
    run(c);
    reopen();
    try { d.dispatchEvent(new CustomEvent('seocookies:change', { detail: { choice: c, previous: prev } })); } catch (e) { /* old browsers */ }
    // Code that already ran cannot be un-run: drop tracker cookies and reload so it stops.
    if (prev && prev !== c && (prev === 'all' || c === 'reject')) {
      wipe();
      if (C.reload !== false) location.reload();
    }
  }

  w.seoCookies = {
    open: function () { show(true); },
    close: hide,
    get: function () { return current; },
    set: choose,
    allows: function (cat) { return allows(cat, current); }
  };

  function init() {
    current = read();
    if (current) {
      run(current);
      consentApi(current);
      reopen();
    } else if (C.auto !== false) {
      show(false);
    }
    if (location.hash === '#seo-cookies') show(true);
    d.addEventListener('click', function (e) {
      var a = e.target.closest ? e.target.closest('a[href$="#seo-cookies"],[data-seo-cookies-open]') : null;
      if (a) { e.preventDefault(); show(true); }
    });
  }

  if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', init); else init();
})(window, document);
