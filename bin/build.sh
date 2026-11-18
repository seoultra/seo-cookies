#!/bin/sh
# Builds one version's release files into <dir>:
#   <dir>/download/seo-cookies-<ver>.zip        WordPress plugin zip (folder "seo-cookies/")
#   <dir>/dist/<ver>/seo-cookies.js             browser script
#   <dir>/dist/<ver>/texts.json                 built-in banner text, all languages
# Usage: bin/build.sh <dir>
# Refuses to overwrite a published version, and refuses mismatched version strings.
set -eu
ROOT=$(cd "$(dirname "$0")/.." && pwd)
DEST=${1:?usage: bin/build.sh <dir>}
VER=$(sed -n 's/^ \* Version: *//p' "$ROOT/seo-cookies.php")
CONST=$(sed -n "s/^define( 'SEO_COOKIES_VERSION', '\(.*\)' );/\1/p" "$ROOT/seo-cookies.php")
STABLE=$(sed -n 's/^Stable tag: *//p' "$ROOT/readme.txt")
JSV=$(sed -n '1s/.*SEO Cookies \([0-9.]*\) .*/\1/p' "$ROOT/assets/seo-cookies.js")
LIBV=$(sed -n "s/.*public const VERSION = '\(.*\)';/\1/p" "$ROOT/src/SeoCookies.php")
for v in "$CONST" "$STABLE" "$JSV" "$LIBV"; do
  [ "$v" = "$VER" ] || { echo "version mismatch: header=$VER const=$CONST readme=$STABLE js=$JSV class=$LIBV" >&2; exit 1; }
done
ZIP="$DEST/download/seo-cookies-$VER.zip"
[ -e "$ZIP" ] && { echo "refusing: $ZIP exists (bump the version)" >&2; exit 1; }
for f in "$ROOT"/*.php "$ROOT"/includes/*.php "$ROOT"/src/*.php; do php -l "$f" >/dev/null; done
php "$ROOT/tests/php/run.php" >/dev/null || { echo "tests/php/run.php failed" >&2; exit 1; }

mkdir -p "$DEST/download" "$DEST/dist/$VER"
TMP=$(mktemp -d); trap 'rm -rf "$TMP"' EXIT
mkdir "$TMP/seo-cookies"
cp -r "$ROOT/seo-cookies.php" "$ROOT/uninstall.php" "$ROOT/readme.txt" "$ROOT/LICENSE" "$ROOT/assets" "$ROOT/includes" "$ROOT/src" "$TMP/seo-cookies/"
(cd "$TMP" && find seo-cookies -type d -exec chmod 755 {} + && find seo-cookies -type f -exec chmod 644 {} + && zip -q -r -X "$ZIP" seo-cookies)
cp "$ROOT/assets/seo-cookies.js" "$DEST/dist/$VER/seo-cookies.js"
php -r 'require $argv[1]; $o = []; foreach (SeoUltra\SeoCookies\SeoCookies::languages() as $c) $o[$c] = SeoUltra\SeoCookies\SeoCookies::texts($c);
  file_put_contents($argv[2], json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));' \
  "$ROOT/src/SeoCookies.php" "$DEST/dist/$VER/texts.json"
chmod 644 "$ZIP" "$DEST/dist/$VER/"*
echo "built $VER:"; ls -la "$ZIP" "$DEST/dist/$VER/"
