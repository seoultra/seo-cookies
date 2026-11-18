<?php
/**
 * Plugin Name:       SEO Cookies
 * Plugin URI:        https://seoultra.id/plugins/seo-cookies/
 * Description:       A tiny cookie banner with three clear choices: Accept all, Necessary only or Reject all. Google Consent Mode v2, WP Consent API and script blocking built in. No tracking, no external requests.
 * Version:           1.1.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            SEOULTRA
 * Author URI:        https://seoultra.id/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       seo-cookies
 */

defined( 'ABSPATH' ) || exit;

define( 'SEO_COOKIES_VERSION', '1.1.0' );
define( 'SEO_COOKIES_COOKIE', 'seo_cookies' );

if ( ! class_exists( 'SeoUltra\\SeoCookies\\SeoCookies' ) ) {
	require_once __DIR__ . '/src/SeoCookies.php';
}
use SeoUltra\SeoCookies\SeoCookies;

/** Default settings. One option row, `seo_cookies`, holds all of them. */
function seo_cookies_defaults() {
	return array(
		'enabled'   => 1,
		'layout'    => 'bar',      // bar | box-start | box-end
		'theme'     => 'light',    // light | dark
		'color'     => '#1a73e8',
		'gcm'       => 1,          // Google Consent Mode v2
		'reopen'    => 1,          // small cookie button to change the choice later
		'days'      => 180,
		'privacy'   => '',         // empty = the WordPress privacy policy page
		'title'     => '',         // empty = built-in text in the site language
		'text'      => '',
		'all'       => '',
		'necessary' => '',
		'reject'    => '',
		'scripts'   => '',         // HTML that runs only after "Accept all"
		'rev'       => 1,          // bump to ask every visitor again
	);
}

function seo_cookies_options() {
	$saved = get_option( 'seo_cookies', array() );
	return array_merge( seo_cookies_defaults(), is_array( $saved ) ? $saved : array() );
}

/** Banner text in the site language, with any custom text from the settings on top. */
function seo_cookies_texts( $opts = null ) {
	$opts   = $opts ? $opts : seo_cookies_options();
	$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
	$code   = SeoCookies::languageKey( $locale );
	$texts  = SeoCookies::texts( $locale );
	foreach ( array( 'title', 'text', 'all', 'necessary', 'reject' ) as $k ) {
		if ( '' !== trim( (string) $opts[ $k ] ) ) {
			$texts[ $k ] = $opts[ $k ];
		}
	}
	/** Filter the banner text. */
	return apply_filters( 'seo_cookies_texts', $texts, $code );
}

function seo_cookies_privacy_url( $opts ) {
	if ( '' !== $opts['privacy'] ) {
		return $opts['privacy'];
	}
	return function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '';
}

/* ------------------------------------------------------------------------ */
/* Front end                                                                 */
/* ------------------------------------------------------------------------ */

/**
 * Google Consent Mode v2 defaults. Printed first in <head>, before any Google tag,
 * and switched to the visitor's stored choice right away on repeat visits.
 */
function seo_cookies_head() {
	$o = seo_cookies_options();
	if ( ! $o['enabled'] || ! $o['gcm'] ) {
		return;
	}
	$tag = SeoCookies::consentModeScript( (int) $o['rev'] );
	if ( function_exists( 'wp_print_inline_script_tag' ) ) {
		// Re-print through WordPress so CSP nonces and script-tag filters apply.
		$js = preg_replace( '~^<script[^>]*>|</script>\s*$~', '', $tag );
		wp_print_inline_script_tag( $js, array( 'id' => 'seo-cookies-consent-mode' ) );
	} else {
		echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static code; only an int is interpolated.
	}
}
add_action( 'wp_head', 'seo_cookies_head', 1 );

function seo_cookies_enqueue() {
	$o = seo_cookies_options();
	if ( ! $o['enabled'] ) {
		return;
	}
	$config = array(
		'name'    => SEO_COOKIES_COOKIE,
		'v'       => (int) $o['rev'],
		'days'    => (int) $o['days'],
		'layout'  => $o['layout'],
		'theme'   => $o['theme'],
		'color'   => $o['color'],
		'gcm'     => (bool) $o['gcm'],
		'reopen'  => (bool) $o['reopen'],
		'privacy' => esc_url_raw( seo_cookies_privacy_url( $o ) ),
		't'       => seo_cookies_texts( $o ),
	);
	/** Filter the browser config (for example to set 'auto' => false). */
	$config = apply_filters( 'seo_cookies_config', $config );

	wp_enqueue_script( 'seo-cookies', plugins_url( 'assets/seo-cookies.js', __FILE__ ), array(), SEO_COOKIES_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_add_inline_script( 'seo-cookies', 'window.seoCookiesConfig=' . wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ';', 'before' );
}
add_action( 'wp_enqueue_scripts', 'seo_cookies_enqueue' );

/** The "load after Accept all" snippet, kept inert in a <template> until the visitor agrees. */
function seo_cookies_footer() {
	$o = seo_cookies_options();
	if ( ! $o['enabled'] || '' === trim( $o['scripts'] ) ) {
		return;
	}
	// Only users with unfiltered_html can save this field (see seo_cookies_sanitize).
	echo '<template id="seo-cookies-scripts">' . $o['scripts'] . "</template>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_footer', 'seo_cookies_footer', 5 );

/** [seo_cookies] — a "Cookie settings" link for the footer or privacy page. */
function seo_cookies_shortcode( $atts ) {
	$t     = seo_cookies_texts();
	$atts  = shortcode_atts( array( 'label' => $t['settings'] ), $atts, 'seo_cookies' );
	return '<a href="#seo-cookies" class="seo-cookies-link" data-seo-cookies-open>' . esc_html( $atts['label'] ) . '</a>';
}
add_shortcode( 'seo_cookies', 'seo_cookies_shortcode' );

/* WP Consent API: tell it this site asks before tracking. */
add_filter( 'wp_get_consent_type', function () { return 'optin'; } );
add_filter( 'wp_consent_api_registered_' . plugin_basename( __FILE__ ), '__return_true' );

/* ------------------------------------------------------------------------ */
/* Settings → SEO Cookies                                                    */
/* ------------------------------------------------------------------------ */

function seo_cookies_sanitize( $in ) {
	$old = seo_cookies_options();
	$def = seo_cookies_defaults();
	$in  = is_array( $in ) ? $in : array();
	$out = array();

	foreach ( array( 'enabled', 'gcm', 'reopen' ) as $k ) {
		$out[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
	}
	$out['layout']  = isset( $in['layout'] ) && in_array( $in['layout'], array( 'bar', 'box-start', 'box-end' ), true ) ? $in['layout'] : 'bar';
	$out['theme']   = isset( $in['theme'] ) && 'dark' === $in['theme'] ? 'dark' : 'light';
	$color          = isset( $in['color'] ) ? sanitize_hex_color( $in['color'] ) : '';
	$out['color']   = $color ? $color : $def['color'];
	$out['days']    = max( 1, min( 395, absint( isset( $in['days'] ) ? $in['days'] : $def['days'] ) ) );
	$out['privacy'] = isset( $in['privacy'] ) ? esc_url_raw( trim( $in['privacy'] ) ) : '';
	foreach ( array( 'title', 'all', 'necessary', 'reject' ) as $k ) {
		$out[ $k ] = isset( $in[ $k ] ) ? sanitize_text_field( $in[ $k ] ) : '';
	}
	$out['text'] = isset( $in['text'] ) ? sanitize_textarea_field( $in['text'] ) : '';

	// Raw HTML only from users who may post it anyway; everyone else keeps the saved value.
	$out['scripts'] = current_user_can( 'unfiltered_html' ) && isset( $in['scripts'] ) ? trim( (string) $in['scripts'] ) : $old['scripts'];

	// The hidden field carries the current revision, so a double sanitize pass never bumps twice.
	$rev        = max( 1, absint( isset( $in['rev'] ) ? $in['rev'] : $old['rev'] ) );
	$out['rev'] = empty( $in['ask_again'] ) ? $rev : $rev + 1;

	return $out;
}

function seo_cookies_admin_init() {
	register_setting( 'seo_cookies', 'seo_cookies', array( 'sanitize_callback' => 'seo_cookies_sanitize', 'default' => seo_cookies_defaults() ) );
}
add_action( 'admin_init', 'seo_cookies_admin_init' );

function seo_cookies_admin_menu() {
	add_options_page( 'SEO Cookies', 'SEO Cookies', 'manage_options', 'seo-cookies', 'seo_cookies_settings_page' );
}
add_action( 'admin_menu', 'seo_cookies_admin_menu' );

add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	function ( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=seo-cookies' ) ) . '">' . esc_html__( 'Settings', 'seo-cookies' ) . '</a>' );
		return $links;
	}
);

function seo_cookies_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o    = seo_cookies_options();
	$t    = seo_cookies_texts( array_merge( $o, array( 'title' => '', 'text' => '', 'all' => '', 'necessary' => '', 'reject' => '' ) ) );
	$name = static function ( $k ) { return 'seo_cookies[' . $k . ']'; };
	?>
	<div class="wrap">
		<h1>SEO Cookies</h1>
		<p><?php esc_html_e( 'A small banner on the first visit with three choices: Accept all, Necessary only or Reject all. The choice is saved in the visitor\'s browser for the number of days below.', 'seo-cookies' ); ?>
			<a href="<?php echo esc_url( home_url( '/#seo-cookies' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Preview on your site', 'seo-cookies' ); ?></a></p>
		<form method="post" action="options.php">
			<?php settings_fields( 'seo_cookies' ); ?>
			<input type="hidden" name="<?php echo esc_attr( $name( 'rev' ) ); ?>" value="<?php echo (int) $o['rev']; ?>">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Banner', 'seo-cookies' ); ?></th>
					<td><label><input type="checkbox" name="<?php echo esc_attr( $name( 'enabled' ) ); ?>" value="1" <?php checked( $o['enabled'] ); ?>> <?php esc_html_e( 'Show the cookie banner', 'seo-cookies' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><label for="sck-layout"><?php esc_html_e( 'Layout', 'seo-cookies' ); ?></label></th>
					<td>
						<select id="sck-layout" name="<?php echo esc_attr( $name( 'layout' ) ); ?>">
							<option value="bar" <?php selected( $o['layout'], 'bar' ); ?>><?php esc_html_e( 'Bar at the bottom', 'seo-cookies' ); ?></option>
							<option value="box-start" <?php selected( $o['layout'], 'box-start' ); ?>><?php esc_html_e( 'Box, bottom left', 'seo-cookies' ); ?></option>
							<option value="box-end" <?php selected( $o['layout'], 'box-end' ); ?>><?php esc_html_e( 'Box, bottom right', 'seo-cookies' ); ?></option>
						</select>
						<select name="<?php echo esc_attr( $name( 'theme' ) ); ?>" aria-label="<?php esc_attr_e( 'Theme', 'seo-cookies' ); ?>">
							<option value="light" <?php selected( $o['theme'], 'light' ); ?>><?php esc_html_e( 'Light', 'seo-cookies' ); ?></option>
							<option value="dark" <?php selected( $o['theme'], 'dark' ); ?>><?php esc_html_e( 'Dark', 'seo-cookies' ); ?></option>
						</select>
						<input type="color" name="<?php echo esc_attr( $name( 'color' ) ); ?>" value="<?php echo esc_attr( $o['color'] ); ?>" aria-label="<?php esc_attr_e( 'Button color', 'seo-cookies' ); ?>">
						<p class="description"><?php esc_html_e( 'On right-to-left sites the box sides swap.', 'seo-cookies' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Banner text', 'seo-cookies' ); ?></th>
					<td>
						<p class="description" style="margin-top:0"><?php esc_html_e( 'Leave empty to use the built-in text in your site language (40+ languages).', 'seo-cookies' ); ?></p>
						<p><input type="text" class="regular-text" name="<?php echo esc_attr( $name( 'title' ) ); ?>" value="<?php echo esc_attr( $o['title'] ); ?>" placeholder="<?php echo esc_attr( $t['title'] ); ?>" aria-label="<?php esc_attr_e( 'Title', 'seo-cookies' ); ?>"></p>
						<p><textarea class="large-text" rows="2" name="<?php echo esc_attr( $name( 'text' ) ); ?>" placeholder="<?php echo esc_attr( $t['text'] ); ?>" aria-label="<?php esc_attr_e( 'Message', 'seo-cookies' ); ?>"><?php echo esc_textarea( $o['text'] ); ?></textarea></p>
						<p>
							<input type="text" name="<?php echo esc_attr( $name( 'all' ) ); ?>" value="<?php echo esc_attr( $o['all'] ); ?>" placeholder="<?php echo esc_attr( $t['all'] ); ?>" aria-label="<?php esc_attr_e( 'Accept all button', 'seo-cookies' ); ?>">
							<input type="text" name="<?php echo esc_attr( $name( 'necessary' ) ); ?>" value="<?php echo esc_attr( $o['necessary'] ); ?>" placeholder="<?php echo esc_attr( $t['necessary'] ); ?>" aria-label="<?php esc_attr_e( 'Necessary only button', 'seo-cookies' ); ?>">
							<input type="text" name="<?php echo esc_attr( $name( 'reject' ) ); ?>" value="<?php echo esc_attr( $o['reject'] ); ?>" placeholder="<?php echo esc_attr( $t['reject'] ); ?>" aria-label="<?php esc_attr_e( 'Reject all button', 'seo-cookies' ); ?>">
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="sck-privacy"><?php esc_html_e( 'Privacy policy link', 'seo-cookies' ); ?></label></th>
					<td><input id="sck-privacy" type="url" class="regular-text" name="<?php echo esc_attr( $name( 'privacy' ) ); ?>" value="<?php echo esc_attr( $o['privacy'] ); ?>" placeholder="<?php echo esc_attr( function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '' ); ?>">
						<p class="description"><?php esc_html_e( 'Empty = the page set under Settings → Privacy.', 'seo-cookies' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Google Consent Mode v2', 'seo-cookies' ); ?></th>
					<td><label><input type="checkbox" name="<?php echo esc_attr( $name( 'gcm' ) ); ?>" value="1" <?php checked( $o['gcm'] ); ?>> <?php esc_html_e( 'Send consent signals to Google tags (Site Kit, GA4, Ads, Tag Manager)', 'seo-cookies' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><label for="sck-scripts"><?php esc_html_e( 'Load after "Accept all"', 'seo-cookies' ); ?></label></th>
					<td>
						<textarea id="sck-scripts" class="large-text code" rows="5" name="<?php echo esc_attr( $name( 'scripts' ) ); ?>" <?php disabled( ! current_user_can( 'unfiltered_html' ) ); ?> placeholder="&lt;script async src=&quot;https://www.googletagmanager.com/gtag/js?id=G-XXXX&quot;&gt;&lt;/script&gt;"><?php echo esc_textarea( $o['scripts'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Paste tracking code here (analytics, pixels). It only runs for visitors who click Accept all.', 'seo-cookies' ); ?>
							<?php esc_html_e( 'In theme code, write type="text/plain" data-seo-cookies="analytics" on a script tag to hold it until Accept all, or data-seo-cookies="functional" to also allow it with Necessary only.', 'seo-cookies' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Change of mind', 'seo-cookies' ); ?></th>
					<td>
						<label><input type="checkbox" name="<?php echo esc_attr( $name( 'reopen' ) ); ?>" value="1" <?php checked( $o['reopen'] ); ?>> <?php esc_html_e( 'Show a small cookie button so visitors can change their choice', 'seo-cookies' ); ?></label>
						<p class="description"><?php esc_html_e( 'You can also put the [seo_cookies] shortcode in a footer or on your privacy page.', 'seo-cookies' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="sck-days"><?php esc_html_e( 'Remember the choice for', 'seo-cookies' ); ?></label></th>
					<td><input id="sck-days" type="number" min="1" max="395" class="small-text" name="<?php echo esc_attr( $name( 'days' ) ); ?>" value="<?php echo (int) $o['days']; ?>"> <?php esc_html_e( 'days', 'seo-cookies' ); ?>
						<p><label><input type="checkbox" name="<?php echo esc_attr( $name( 'ask_again' ) ); ?>" value="1"> <?php esc_html_e( 'Ask every visitor again (use after you add new cookies)', 'seo-cookies' ); ?></label></p></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
