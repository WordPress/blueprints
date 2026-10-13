<?php
/**
 * Plugin Name: HabitForge
 * Plugin URI: https://github.com/WordPress/blueprints/tree/trunk/blueprints/habitforge
 * Description: A private habit tracker. Daily check-ins, streaks, and heatmaps — all stored in your own WordPress.
 * Version: 1.0.0
 * Author: Muryam Sultana
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: habitforge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HabitForge {

	const CPT       = 'hf_habit';
	const PAGE_SLUG = 'habits';
	const NONCE     = 'habitforge_action';

	private static $colors = array(
		'#22c55e', // green
		'#3b82f6', // blue
		'#f59e0b', // amber
		'#ef4444', // red
		'#a855f7', // purple
		'#14b8a6', // teal
		'#ec4899', // pink
		'#f97316', // orange
	);

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_cpt' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render_app' ) );
		add_action( 'wp_ajax_hf_toggle', array( __CLASS__, 'ajax_toggle' ) );
		add_action( 'wp_ajax_hf_add', array( __CLASS__, 'ajax_add' ) );
		add_action( 'wp_ajax_hf_delete', array( __CLASS__, 'ajax_delete' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		register_activation_hook( __FILE__, array( __CLASS__, 'activate' ) );
	}

	public static function register_cpt() {
		register_post_type(
			self::CPT,
			array(
				'labels'   => array(
					'name'          => __( 'Habits', 'habitforge' ),
					'singular_name' => __( 'Habit', 'habitforge' ),
				),
				'public'   => false,
				'show_ui'  => false,
				'supports' => array( 'title' ),
			)
		);
	}

	public static function activate() {
		self::register_cpt();
		if ( ! get_page_by_path( self::PAGE_SLUG ) ) {
			wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => __( 'Habits', 'habitforge' ),
					'post_name'    => self::PAGE_SLUG,
					'post_content' => '',
				)
			);
		}
		// Starter habits so the app isn't empty on first run.
		if ( ! get_posts( array( 'post_type' => self::CPT, 'posts_per_page' => 1, 'post_status' => 'publish', 'fields' => 'ids' ) ) ) {
			self::create_habit( __( 'Drink water', 'habitforge' ), '💧', self::$colors[1] );
			self::create_habit( __( 'Read 20 minutes', 'habitforge' ), '📖', self::$colors[0] );
			self::create_habit( __( 'Go for a walk', 'habitforge' ), '🚶', self::$colors[2] );
		}
		flush_rewrite_rules();
	}

	private static function create_habit( $name, $emoji, $color ) {
		$id = wp_insert_post(
			array(
				'post_type'   => self::CPT,
				'post_status' => 'publish',
				'post_title'  => sanitize_text_field( $name ),
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_hf_emoji', sanitize_text_field( $emoji ) );
			update_post_meta( $id, '_hf_color', sanitize_hex_color( $color ) );
			update_post_meta( $id, '_hf_checkins', array() );
		}
		return $id;
	}

	public static function admin_menu() {
		add_menu_page(
			__( 'Habits', 'habitforge' ),
			__( 'Habits', 'habitforge' ),
			'edit_posts',
			'habitforge',
			function () {
				wp_safe_redirect( home_url( '/' . self::PAGE_SLUG . '/' ) );
				exit;
			},
			'dashicons-yes-alt',
			30
		);
	}

	// ---------------------------------------------------------------- data --

	private static function get_habits() {
		return get_posts(
			array(
				'post_type'      => self::CPT,
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'order'          => 'ASC',
			)
		);
	}

	private static function get_checkins( $habit_id ) {
		$checkins = get_post_meta( $habit_id, '_hf_checkins', true );
		return is_array( $checkins ) ? $checkins : array();
	}

	private static function today() {
		return current_time( 'Y-m-d' );
	}

	private static function streaks( array $checkins ) {
		$current = 0;
		$day     = self::today();
		if ( empty( $checkins[ $day ] ) ) {
			// Streak survives an unchecked "today"; start counting from yesterday.
			$day = gmdate( 'Y-m-d', strtotime( $day . ' -1 day' ) );
		}
		while ( ! empty( $checkins[ $day ] ) ) {
			$current++;
			$day = gmdate( 'Y-m-d', strtotime( $day . ' -1 day' ) );
		}

		$best  = 0;
		$run   = 0;
		$prev  = null;
		$dates = array_keys( $checkins );
		sort( $dates );
		foreach ( $dates as $date ) {
			if ( null !== $prev && gmdate( 'Y-m-d', strtotime( $prev . ' +1 day' ) ) === $date ) {
				$run++;
			} else {
				$run = 1;
			}
			$best = max( $best, $run );
			$prev = $date;
		}

		return array(
			'current' => $current,
			'best'    => $best,
			'total'   => count( $checkins ),
		);
	}

	// ---------------------------------------------------------------- ajax --

	private static function check_request() {
		if ( ! current_user_can( 'edit_posts' ) || ! check_ajax_referer( self::NONCE, 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Not allowed' ), 403 );
		}
	}

	public static function ajax_toggle() {
		self::check_request();
		$habit_id = (int) ( $_POST['habit'] ?? 0 );
		$date     = sanitize_text_field( wp_unslash( $_POST['date'] ?? '' ) );

		if ( get_post_type( $habit_id ) !== self::CPT || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) || $date > self::today() ) {
			wp_send_json_error( array( 'message' => 'Bad request' ), 400 );
		}

		$checkins = self::get_checkins( $habit_id );
		$checked  = empty( $checkins[ $date ] );
		if ( $checked ) {
			$checkins[ $date ] = 1;
		} else {
			unset( $checkins[ $date ] );
		}
		update_post_meta( $habit_id, '_hf_checkins', $checkins );

		wp_send_json_success( array_merge( array( 'checked' => $checked ), self::streaks( $checkins ) ) );
	}

	public static function ajax_add() {
		self::check_request();
		$name  = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$emoji = sanitize_text_field( wp_unslash( $_POST['emoji'] ?? '' ) );
		$color = sanitize_hex_color( wp_unslash( $_POST['color'] ?? '' ) );
		if ( '' === $name ) {
			wp_send_json_error( array( 'message' => 'Name required' ), 400 );
		}
		if ( '' === $emoji ) {
			$emoji = '⭐';
		}
		if ( ! $color ) {
			$color = self::$colors[ array_rand( self::$colors ) ];
		}
		$id = self::create_habit( $name, $emoji, $color );
		wp_send_json_success( array( 'id' => $id ) );
	}

	public static function ajax_delete() {
		self::check_request();
		$habit_id = (int) ( $_POST['habit'] ?? 0 );
		if ( get_post_type( $habit_id ) !== self::CPT ) {
			wp_send_json_error( array( 'message' => 'Bad request' ), 400 );
		}
		wp_trash_post( $habit_id );
		wp_send_json_success();
	}

	// -------------------------------------------------------------- render --

	public static function maybe_render_app() {
		if ( ! is_page( self::PAGE_SLUG ) ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}
		self::render_app();
		exit;
	}

	private static function heatmap( $habit_id, array $checkins, $color, $weeks = 22 ) {
		$today = self::today();
		// End the grid on the current week's Saturday so today is in the last column.
		$end   = strtotime( 'saturday this week', strtotime( $today ) );
		$start = strtotime( '-' . ( $weeks * 7 - 1 ) . ' days', $end );

		$html   = '<div class="hf-heatmap" role="img" aria-label="' . esc_attr__( 'Check-in history', 'habitforge' ) . '">';
		$months = '<div class="hf-months">';
		$grid   = '<div class="hf-grid">';
		$prev_month = '';
		for ( $w = 0; $w < $weeks; $w++ ) {
			$col        = '<div class="hf-col">';
			$week_start = strtotime( '+' . ( $w * 7 ) . ' days', $start );
			$month      = gmdate( 'M', $week_start );
			$months    .= '<span>' . ( $month !== $prev_month ? esc_html( $month ) : '' ) . '</span>';
			$prev_month = $month;
			for ( $d = 0; $d < 7; $d++ ) {
				$ts   = strtotime( '+' . $d . ' days', $week_start );
				$date = gmdate( 'Y-m-d', $ts );
				if ( $date > $today ) {
					$col .= '<i class="hf-cell hf-future"></i>';
					continue;
				}
				$on   = ! empty( $checkins[ $date ] );
				$col .= sprintf(
					'<i class="hf-cell%s" data-habit="%d" data-date="%s" title="%s"%s tabindex="0" role="button"></i>',
					$on ? ' hf-on' : '',
					(int) $habit_id,
					esc_attr( $date ),
					esc_attr( $date ),
					$on ? ' style="background:' . esc_attr( $color ) . '"' : ''
				);
			}
			$grid .= $col . '</div>';
		}
		return $html . $months . '</div>' . $grid . '</div></div>';
	}

	private static function render_app() {
		$habits = self::get_habits();
		$today  = self::today();
		$nonce  = wp_create_nonce( self::NONCE );

		$done_today = 0;
		$best_all   = 0;
		foreach ( $habits as $habit ) {
			$checkins = self::get_checkins( $habit->ID );
			if ( ! empty( $checkins[ $today ] ) ) {
				$done_today++;
			}
			$s        = self::streaks( $checkins );
			$best_all = max( $best_all, $s['best'] );
		}

		header( 'Content-Type: text/html; charset=utf-8' );
		?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php esc_html_e( 'HabitForge', 'habitforge' ); ?></title>
<style>
:root {
	--bg: #f6f7f9; --card: #ffffff; --text: #1a202c; --muted: #64748b;
	--line: #e2e8f0; --cell: #e8ecf1; --accent: #22c55e;
}
@media (prefers-color-scheme: dark) {
	:root { --bg: #0f1420; --card: #1a2130; --text: #e7ecf3; --muted: #8fa0b5; --line: #2a3446; --cell: #242e40; }
}
* { box-sizing: border-box; }
body { margin: 0; background: var(--bg); color: var(--text); font: 16px/1.5 -apple-system, "Segoe UI", Roboto, sans-serif; }
.hf-wrap { max-width: 860px; margin: 0 auto; padding: 24px 16px 64px; }
.hf-top { display: flex; align-items: baseline; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
.hf-top h1 { font-size: 26px; margin: 0; }
.hf-top h1 span { color: var(--accent); }
.hf-date { color: var(--muted); font-size: 14px; }
.hf-stats { display: flex; gap: 12px; margin: 18px 0 22px; flex-wrap: wrap; }
.hf-stat { background: var(--card); border: 1px solid var(--line); border-radius: 12px; padding: 10px 16px; min-width: 120px; }
.hf-stat b { display: block; font-size: 22px; }
.hf-stat span { color: var(--muted); font-size: 12.5px; }
.hf-card { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 16px 18px; margin-bottom: 14px; }
.hf-row { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.hf-check { width: 30px; height: 30px; border-radius: 9px; border: 2px solid var(--line); background: transparent; cursor: pointer; font-size: 16px; line-height: 1; color: transparent; flex: none; transition: all .15s; }
.hf-check.hf-done { color: #fff; border-color: transparent; }
.hf-name { font-weight: 600; font-size: 17px; margin-right: auto; }
.hf-name small { display: block; font-weight: 400; color: var(--muted); font-size: 12.5px; }
.hf-badges { display: flex; gap: 14px; font-size: 13px; color: var(--muted); }
.hf-badges b { color: var(--text); }
.hf-del { border: 0; background: none; color: var(--muted); cursor: pointer; font-size: 15px; opacity: .5; }
.hf-del:hover { opacity: 1; color: #ef4444; }
.hf-heatmap { margin-top: 14px; overflow-x: auto; }
.hf-months { display: flex; gap: 3px; margin-bottom: 3px; }
.hf-months span { width: 13px; flex: none; font-size: 9.5px; color: var(--muted); overflow: visible; white-space: nowrap; }
.hf-grid { display: flex; gap: 3px; }
.hf-col { display: flex; flex-direction: column; gap: 3px; }
.hf-cell { width: 13px; height: 13px; border-radius: 3px; background: var(--cell); cursor: pointer; }
.hf-cell.hf-future { visibility: hidden; cursor: default; }
.hf-cell:not(.hf-future):hover { outline: 2px solid var(--muted); }
.hf-add { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.hf-add input[type=text] { flex: 1 1 200px; padding: 9px 12px; border: 1px solid var(--line); border-radius: 9px; background: var(--bg); color: var(--text); font-size: 15px; }
.hf-add input.hf-emoji { flex: 0 0 58px; text-align: center; }
.hf-swatches { display: flex; gap: 6px; }
.hf-swatch { width: 24px; height: 24px; border-radius: 50%; border: 2px solid transparent; cursor: pointer; padding: 0; }
.hf-swatch.hf-sel { border-color: var(--text); }
.hf-add button.hf-submit { padding: 9px 18px; border: 0; border-radius: 9px; background: var(--accent); color: #fff; font-weight: 600; font-size: 15px; cursor: pointer; }
.hf-empty { text-align: center; color: var(--muted); padding: 30px 0; }
</style>
</head>
<body>
<div class="hf-wrap">
	<div class="hf-top">
		<h1>🔥 Habit<span>Forge</span></h1>
		<div class="hf-date"><?php echo esc_html( wp_date( get_option( 'date_format' ) ) ); ?></div>
	</div>

	<div class="hf-stats">
		<div class="hf-stat"><b id="hf-done"><?php echo (int) $done_today; ?> / <?php echo count( $habits ); ?></b><span><?php esc_html_e( 'done today', 'habitforge' ); ?></span></div>
		<div class="hf-stat"><b id="hf-best"><?php echo (int) $best_all; ?></b><span><?php esc_html_e( 'longest streak (days)', 'habitforge' ); ?></span></div>
		<div class="hf-stat"><b><?php echo count( $habits ); ?></b><span><?php esc_html_e( 'habits tracked', 'habitforge' ); ?></span></div>
	</div>

	<?php if ( ! $habits ) : ?>
		<div class="hf-card hf-empty"><?php esc_html_e( 'No habits yet — add your first one below.', 'habitforge' ); ?></div>
	<?php endif; ?>

	<?php
	foreach ( $habits as $habit ) :
		$checkins = self::get_checkins( $habit->ID );
		$color    = get_post_meta( $habit->ID, '_hf_color', true );
		$color    = $color ? $color : self::$colors[0];
		$emoji    = get_post_meta( $habit->ID, '_hf_emoji', true );
		$s        = self::streaks( $checkins );
		$done     = ! empty( $checkins[ $today ] );
		?>
	<div class="hf-card" data-card="<?php echo (int) $habit->ID; ?>" data-color="<?php echo esc_attr( $color ); ?>">
		<div class="hf-row">
			<button class="hf-check<?php echo $done ? ' hf-done' : ''; ?>" data-habit="<?php echo (int) $habit->ID; ?>" data-date="<?php echo esc_attr( $today ); ?>" <?php echo $done ? 'style="background:' . esc_attr( $color ) . '"' : ''; ?> aria-label="<?php esc_attr_e( 'Toggle today', 'habitforge' ); ?>">✓</button>
			<div class="hf-name"><?php echo esc_html( $emoji . ' ' . $habit->post_title ); ?>
				<small class="hf-streak-line">
					🔥 <b class="hf-cur"><?php echo (int) $s['current']; ?></b> <?php esc_html_e( 'day streak', 'habitforge' ); ?>
					&nbsp;·&nbsp; <?php esc_html_e( 'best', 'habitforge' ); ?> <b class="hf-bst"><?php echo (int) $s['best']; ?></b>
					&nbsp;·&nbsp; <b class="hf-tot"><?php echo (int) $s['total']; ?></b> <?php esc_html_e( 'total', 'habitforge' ); ?>
				</small>
			</div>
			<button class="hf-del" data-habit="<?php echo (int) $habit->ID; ?>" title="<?php esc_attr_e( 'Delete habit', 'habitforge' ); ?>">✕</button>
		</div>
		<?php echo self::heatmap( $habit->ID, $checkins, $color ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php endforeach; ?>

	<div class="hf-card">
		<form class="hf-add" id="hf-add">
			<input type="text" class="hf-emoji" name="emoji" value="⭐" maxlength="4" aria-label="<?php esc_attr_e( 'Emoji', 'habitforge' ); ?>">
			<input type="text" name="name" placeholder="<?php esc_attr_e( 'New habit, e.g. Meditate 10 minutes', 'habitforge' ); ?>" required>
			<div class="hf-swatches">
				<?php foreach ( self::$colors as $i => $c ) : ?>
				<button type="button" class="hf-swatch<?php echo 0 === $i ? ' hf-sel' : ''; ?>" data-color="<?php echo esc_attr( $c ); ?>" style="background:<?php echo esc_attr( $c ); ?>" aria-label="<?php echo esc_attr( $c ); ?>"></button>
				<?php endforeach; ?>
			</div>
			<button type="submit" class="hf-submit"><?php esc_html_e( 'Add habit', 'habitforge' ); ?></button>
		</form>
	</div>
</div>
<script>
(function () {
	var ajax = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
	var nonce = <?php echo wp_json_encode( $nonce ); ?>;

	function post(action, data) {
		var body = new URLSearchParams(data);
		body.set('action', action);
		body.set('nonce', nonce);
		return fetch(ajax, { method: 'POST', body: body, credentials: 'same-origin' })
			.then(function (r) { return r.json(); });
	}

	function updateCard(card, stats) {
		card.querySelector('.hf-cur').textContent = stats.current;
		card.querySelector('.hf-bst').textContent = stats.best;
		card.querySelector('.hf-tot').textContent = stats.total;
	}

	document.addEventListener('click', function (e) {
		var el = e.target;

		if (el.classList.contains('hf-swatch')) {
			el.closest('.hf-swatches').querySelectorAll('.hf-swatch').forEach(function (s) { s.classList.remove('hf-sel'); });
			el.classList.add('hf-sel');
			return;
		}

		if (el.classList.contains('hf-del')) {
			if (!confirm('<?php echo esc_js( __( 'Delete this habit and its history?', 'habitforge' ) ); ?>')) return;
			post('hf_delete', { habit: el.dataset.habit }).then(function () { location.reload(); });
			return;
		}

		var isCheck = el.classList.contains('hf-check');
		var isCell = el.classList.contains('hf-cell') && !el.classList.contains('hf-future');
		if (!isCheck && !isCell) return;

		post('hf_toggle', { habit: el.dataset.habit, date: el.dataset.date }).then(function (res) {
			if (!res.success) return;
			var card = el.closest('.hf-card');
			updateCard(card, res.data);
			// Sync the today-button and the matching heatmap cell.
			var pair = card.querySelectorAll('[data-date="' + el.dataset.date + '"]');
			pair.forEach(function (p) {
				var on = res.data.checked;
				if (p.classList.contains('hf-check')) {
					p.classList.toggle('hf-done', on);
					p.style.background = on ? getHabitColor(card) : '';
				} else {
					p.classList.toggle('hf-on', on);
					p.style.background = on ? getHabitColor(card) : '';
				}
			});
			refreshDoneToday();
		});
	});

	function getHabitColor(card) {
		return card.dataset.color || '<?php echo esc_js( self::$colors[0] ); ?>';
	}

	function refreshDoneToday() {
		var total = document.querySelectorAll('.hf-check').length;
		var done = document.querySelectorAll('.hf-check.hf-done').length;
		document.getElementById('hf-done').textContent = done + ' / ' + total;
	}

	document.getElementById('hf-add').addEventListener('submit', function (e) {
		e.preventDefault();
		var f = e.target;
		post('hf_add', {
			name: f.name.value,
			emoji: f.emoji.value,
			color: f.querySelector('.hf-swatch.hf-sel').dataset.color
		}).then(function () { location.reload(); });
	});
})();
</script>
</body>
</html>
		<?php
	}
}

HabitForge::init();
