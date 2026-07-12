<?php
/**
 * Plugin Name: PlanForge
 * Plugin URI: https://github.com/WordPress/blueprints/tree/trunk/blueprints/planforge
 * Description: An editorial calendar for your posts. See your publishing month at a glance, quick-add draft ideas on any day, and drag posts to reschedule.
 * Version: 1.0.0
 * Author: Muryam Sultana
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: planforge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PlanForge {

	const PAGE_SLUG = 'calendar';
	const NONCE     = 'planforge_action';

	private static $statuses = array( 'publish', 'future', 'draft', 'pending' );

	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render_app' ) );
		add_action( 'wp_ajax_pf_add', array( __CLASS__, 'ajax_add' ) );
		add_action( 'wp_ajax_pf_move', array( __CLASS__, 'ajax_move' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		register_activation_hook( __FILE__, array( __CLASS__, 'activate' ) );
	}

	public static function activate() {
		if ( ! get_page_by_path( self::PAGE_SLUG ) ) {
			wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => __( 'Calendar', 'planforge' ),
					'post_name'    => self::PAGE_SLUG,
					'post_content' => '',
				)
			);
		}
		flush_rewrite_rules();
	}

	public static function admin_menu() {
		add_menu_page(
			__( 'Calendar', 'planforge' ),
			__( 'Calendar', 'planforge' ),
			'edit_posts',
			'planforge',
			function () {
				wp_safe_redirect( home_url( '/' . self::PAGE_SLUG . '/' ) );
				exit;
			},
			'dashicons-calendar-alt',
			32
		);
	}

	// ---------------------------------------------------------------- ajax --

	private static function check_request() {
		if ( ! current_user_can( 'edit_posts' ) || ! check_ajax_referer( self::NONCE, 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Not allowed' ), 403 );
		}
	}

	private static function valid_date( $date ) {
		return (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date );
	}

	public static function ajax_add() {
		self::check_request();
		$title = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
		$date  = sanitize_text_field( wp_unslash( $_POST['date'] ?? '' ) );
		if ( '' === $title || ! self::valid_date( $date ) ) {
			wp_send_json_error( array( 'message' => 'Title and date required' ), 400 );
		}
		$id = wp_insert_post(
			array(
				'post_type'   => 'post',
				'post_status' => 'draft',
				'post_title'  => $title,
				'post_date'   => $date . ' 09:00:00',
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			wp_send_json_error( array( 'message' => $id->get_error_message() ), 500 );
		}
		wp_send_json_success( array( 'id' => $id ) );
	}

	public static function ajax_move() {
		self::check_request();
		$post_id = (int) ( $_POST['post'] ?? 0 );
		$date    = sanitize_text_field( wp_unslash( $_POST['date'] ?? '' ) );
		$post    = get_post( $post_id );

		if ( ! $post || 'post' !== $post->post_type || ! self::valid_date( $date ) || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => 'Bad request' ), 400 );
		}

		$time   = gmdate( 'H:i:s', strtotime( $post->post_date ) );
		$result = wp_update_post(
			array(
				'ID'            => $post_id,
				'post_date'     => $date . ' ' . $time,
				'post_date_gmt' => get_gmt_from_date( $date . ' ' . $time ),
				'edit_date'     => true,
			),
			true
		);
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 500 );
		}
		wp_send_json_success( array( 'status' => get_post_status( $post_id ) ) );
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

	private static function month_posts( $year, $month ) {
		$posts = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => self::$statuses,
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'ASC',
				'date_query'     => array(
					array(
						'year'  => $year,
						'month' => $month,
					),
				),
			)
		);
		$by_day = array();
		foreach ( $posts as $post ) {
			$day              = gmdate( 'Y-m-d', strtotime( $post->post_date ) );
			$by_day[ $day ][] = $post;
		}
		return $by_day;
	}

	private static function render_app() {
		$today = current_time( 'Y-m-d' );
		// Note: WP reserves the `m` query var for date archives, so use `pf_month`.
		$m     = isset( $_GET['pf_month'] ) && preg_match( '/^\d{4}-(0[1-9]|1[0-2])$/', $_GET['pf_month'] ) ? sanitize_text_field( wp_unslash( $_GET['pf_month'] ) ) : substr( $today, 0, 7 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		list( $year, $month ) = array_map( 'intval', explode( '-', $m ) );

		$first_ts   = mktime( 0, 0, 0, $month, 1, $year );
		$days_total = (int) gmdate( 't', $first_ts );
		$start_dow  = (int) gmdate( 'w', $first_ts ); // 0 = Sunday
		$prev       = gmdate( 'Y-m', mktime( 0, 0, 0, $month - 1, 1, $year ) );
		$next       = gmdate( 'Y-m', mktime( 0, 0, 0, $month + 1, 1, $year ) );
		$by_day     = self::month_posts( $year, $month );
		$nonce      = wp_create_nonce( self::NONCE );
		$app_url    = home_url( '/' . self::PAGE_SLUG . '/' );

		$counts = array( 'publish' => 0, 'future' => 0, 'draft' => 0, 'pending' => 0 );
		foreach ( $by_day as $day_posts ) {
			foreach ( $day_posts as $p ) {
				if ( isset( $counts[ $p->post_status ] ) ) {
					$counts[ $p->post_status ]++;
				}
			}
		}

		$labels = array(
			'publish' => __( 'Published', 'planforge' ),
			'future'  => __( 'Scheduled', 'planforge' ),
			'draft'   => __( 'Draft', 'planforge' ),
			'pending' => __( 'Pending', 'planforge' ),
		);

		header( 'Content-Type: text/html; charset=utf-8' );
		?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php esc_html_e( 'PlanForge', 'planforge' ); ?></title>
<style>
:root {
	--bg: #f6f7f9; --card: #ffffff; --text: #1a202c; --muted: #64748b;
	--line: #e2e8f0; --accent: #0ea5e9;
	--publish: #22c55e; --future: #3b82f6; --draft: #f59e0b; --pending: #a855f7;
}
@media (prefers-color-scheme: dark) {
	:root { --bg: #0d1521; --card: #172131; --text: #e7edf5; --muted: #8da2ba; --line: #263448; }
}
* { box-sizing: border-box; }
body { margin: 0; background: var(--bg); color: var(--text); font: 15px/1.45 -apple-system, "Segoe UI", Roboto, sans-serif; }
.pf-wrap { max-width: 1060px; margin: 0 auto; padding: 22px 16px 60px; }
.pf-top { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; }
.pf-top h1 { font-size: 24px; margin: 0; }
.pf-top h1 span { color: var(--accent); }
.pf-nav { display: flex; align-items: center; gap: 10px; }
.pf-nav a { text-decoration: none; color: var(--text); background: var(--card); border: 1px solid var(--line); border-radius: 9px; padding: 6px 13px; font-weight: 600; }
.pf-nav a:hover { border-color: var(--accent); color: var(--accent); }
.pf-month { font-size: 18px; font-weight: 700; min-width: 170px; text-align: center; }
.pf-legend { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 14px; font-size: 12.5px; color: var(--muted); }
.pf-legend i { display: inline-block; width: 10px; height: 10px; border-radius: 3px; margin-right: 5px; }
.pf-legend b { color: var(--text); }
.pf-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; }
.pf-dow { text-align: center; font-size: 12px; color: var(--muted); font-weight: 600; padding: 4px 0; }
.pf-day { background: var(--card); border: 1px solid var(--line); border-radius: 10px; min-height: 104px; padding: 6px; position: relative; display: flex; flex-direction: column; gap: 4px; }
.pf-day.pf-off { opacity: .35; background: transparent; }
.pf-day.pf-today { border-color: var(--accent); box-shadow: 0 0 0 1px var(--accent); }
.pf-num { font-size: 12px; color: var(--muted); font-weight: 600; }
.pf-today .pf-num { color: var(--accent); }
.pf-day.pf-over { outline: 2px dashed var(--accent); }
.pf-chip { border-radius: 7px; padding: 3px 7px; font-size: 12px; line-height: 1.3; cursor: grab; color: #fff; display: flex; align-items: center; gap: 5px; overflow: hidden; }
.pf-chip span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1; }
.pf-chip a { color: #fff; opacity: .75; text-decoration: none; flex: none; font-size: 11px; }
.pf-chip a:hover { opacity: 1; }
.pf-chip.s-publish { background: var(--publish); }
.pf-chip.s-future { background: var(--future); }
.pf-chip.s-draft { background: var(--draft); }
.pf-chip.s-pending { background: var(--pending); }
.pf-addbtn { position: absolute; top: 4px; right: 5px; border: 0; background: none; color: var(--muted); font-size: 15px; cursor: pointer; opacity: 0; padding: 0 3px; }
.pf-day:hover .pf-addbtn { opacity: .8; }
.pf-addbtn:hover { color: var(--accent); }
.pf-quick { width: 100%; border: 1px solid var(--accent); border-radius: 7px; padding: 3px 6px; font-size: 12px; background: var(--bg); color: var(--text); }
.pf-hint { margin-top: 14px; color: var(--muted); font-size: 12.5px; }
@media (max-width: 700px) { .pf-grid { grid-template-columns: repeat(7, minmax(44px, 1fr)); } .pf-day { min-height: 64px; } }
</style>
</head>
<body>
<div class="pf-wrap">
	<div class="pf-top">
		<h1>🗓️ Plan<span>Forge</span></h1>
		<div class="pf-nav">
			<a href="<?php echo esc_url( add_query_arg( 'pf_month', $prev, $app_url ) ); ?>">←</a>
			<span class="pf-month"><?php echo esc_html( wp_date( 'F Y', $first_ts ) ); ?></span>
			<a href="<?php echo esc_url( add_query_arg( 'pf_month', $next, $app_url ) ); ?>">→</a>
		</div>
	</div>

	<div class="pf-legend">
		<?php foreach ( $labels as $status => $label ) : ?>
		<span><i style="background:var(--<?php echo esc_attr( $status ); ?>)"></i><?php echo esc_html( $label ); ?>: <b><?php echo (int) $counts[ $status ]; ?></b></span>
		<?php endforeach; ?>
	</div>

	<div class="pf-grid">
		<?php
		$dow_names = array( __( 'Sun', 'planforge' ), __( 'Mon', 'planforge' ), __( 'Tue', 'planforge' ), __( 'Wed', 'planforge' ), __( 'Thu', 'planforge' ), __( 'Fri', 'planforge' ), __( 'Sat', 'planforge' ) );
		foreach ( $dow_names as $dow ) {
			echo '<div class="pf-dow">' . esc_html( $dow ) . '</div>';
		}
		$cells = (int) ceil( ( $start_dow + $days_total ) / 7 ) * 7;
		for ( $i = 0; $i < $cells; $i++ ) {
			$day_num = $i - $start_dow + 1;
			if ( $day_num < 1 || $day_num > $days_total ) {
				echo '<div class="pf-day pf-off"></div>';
				continue;
			}
			$date = sprintf( '%04d-%02d-%02d', $year, $month, $day_num );
			printf(
				'<div class="pf-day%s" data-date="%s"><span class="pf-num">%d</span><button class="pf-addbtn" title="%s">＋</button>',
				$date === $today ? ' pf-today' : '',
				esc_attr( $date ),
				(int) $day_num,
				esc_attr__( 'Quick-add a draft on this day', 'planforge' )
			);
			foreach ( $by_day[ $date ] ?? array() as $p ) {
				printf(
					'<div class="pf-chip s-%s" draggable="true" data-post="%d" title="%s · %s"><span>%s</span><a href="%s" target="_blank" draggable="false" title="%s">✎</a></div>',
					esc_attr( $p->post_status ),
					(int) $p->ID,
					esc_attr( $labels[ $p->post_status ] ?? $p->post_status ),
					esc_attr( gmdate( 'H:i', strtotime( $p->post_date ) ) ),
					esc_html( $p->post_title ? $p->post_title : __( '(no title)', 'planforge' ) ),
					esc_url( admin_url( 'post.php?post=' . $p->ID . '&action=edit' ) ),
					esc_attr__( 'Edit post', 'planforge' )
				);
			}
			echo '</div>';
		}
		?>
	</div>

	<p class="pf-hint"><?php esc_html_e( 'Hover a day and click ＋ to quick-add a draft · drag a post to another day to move it · ✎ opens the editor.', 'planforge' ); ?></p>
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

	// Quick-add: ＋ swaps to an inline input; Enter saves, Esc cancels.
	document.addEventListener('click', function (e) {
		if (!e.target.classList.contains('pf-addbtn')) return;
		var day = e.target.closest('.pf-day');
		if (day.querySelector('.pf-quick')) return;
		var input = document.createElement('input');
		input.className = 'pf-quick';
		input.placeholder = '<?php echo esc_js( __( 'Post title…', 'planforge' ) ); ?>';
		day.appendChild(input);
		input.focus();
		input.addEventListener('keydown', function (ev) {
			if (ev.key === 'Escape') { input.remove(); }
			if (ev.key === 'Enter' && input.value.trim()) {
				post('pf_add', { title: input.value.trim(), date: day.dataset.date })
					.then(function () { location.reload(); });
			}
		});
		input.addEventListener('blur', function () { setTimeout(function () { input.remove(); }, 150); });
	});

	// Drag posts between days to reschedule.
	var dragged = null;
	document.addEventListener('dragstart', function (e) {
		var chip = e.target.closest && e.target.closest('.pf-chip');
		if (chip) { dragged = chip; e.dataTransfer.effectAllowed = 'move'; }
	});
	document.addEventListener('dragover', function (e) {
		var day = e.target.closest && e.target.closest('.pf-day:not(.pf-off)');
		if (day && dragged) { e.preventDefault(); day.classList.add('pf-over'); }
	});
	document.addEventListener('dragleave', function (e) {
		var day = e.target.closest && e.target.closest('.pf-day');
		if (day) day.classList.remove('pf-over');
	});
	document.addEventListener('drop', function (e) {
		var day = e.target.closest && e.target.closest('.pf-day:not(.pf-off)');
		if (!day || !dragged) return;
		e.preventDefault();
		day.classList.remove('pf-over');
		if (dragged.classList.contains('s-publish') &&
			!confirm('<?php echo esc_js( __( 'This post is already published — moving it changes its publish date. Continue?', 'planforge' ) ); ?>')) {
			dragged = null;
			return;
		}
		post('pf_move', { post: dragged.dataset.post, date: day.dataset.date })
			.then(function () { location.reload(); });
		dragged = null;
	});
})();
</script>
</body>
</html>
		<?php
	}
}

PlanForge::init();
