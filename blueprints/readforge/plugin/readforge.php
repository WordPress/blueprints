<?php
/**
 * Plugin Name: ReadForge
 * Plugin URI: https://github.com/WordPress/blueprints/tree/trunk/blueprints/readforge
 * Description: A private reading tracker. Shelves for want-to-read, reading, and finished — with progress, star ratings, and a yearly goal. Your books, your data.
 * Version: 1.0.0
 * Author: Muryam Sultana
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: readforge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReadForge {

	const CPT       = 'rf_book';
	const PAGE_SLUG = 'books';
	const NONCE     = 'readforge_action';

	private static function shelves() {
		return array(
			'want'     => array( 'label' => __( 'Want to Read', 'readforge' ), 'color' => '#3b82f6' ),
			'reading'  => array( 'label' => __( 'Reading', 'readforge' ), 'color' => '#f59e0b' ),
			'finished' => array( 'label' => __( 'Finished', 'readforge' ), 'color' => '#22c55e' ),
		);
	}

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_cpt' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render_app' ) );
		add_action( 'wp_ajax_rf_add', array( __CLASS__, 'ajax_add' ) );
		add_action( 'wp_ajax_rf_move', array( __CLASS__, 'ajax_move' ) );
		add_action( 'wp_ajax_rf_progress', array( __CLASS__, 'ajax_progress' ) );
		add_action( 'wp_ajax_rf_rate', array( __CLASS__, 'ajax_rate' ) );
		add_action( 'wp_ajax_rf_goal', array( __CLASS__, 'ajax_goal' ) );
		add_action( 'wp_ajax_rf_delete', array( __CLASS__, 'ajax_delete' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		register_activation_hook( __FILE__, array( __CLASS__, 'activate' ) );
	}

	public static function register_cpt() {
		register_post_type(
			self::CPT,
			array(
				'labels'   => array(
					'name'          => __( 'Books', 'readforge' ),
					'singular_name' => __( 'Book', 'readforge' ),
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
					'post_title'   => __( 'Books', 'readforge' ),
					'post_name'    => self::PAGE_SLUG,
					'post_content' => '',
				)
			);
		}
		if ( ! get_option( 'rf_goal' ) ) {
			update_option( 'rf_goal', 12 );
		}
		// Starter books so the shelves aren't empty on first run.
		if ( ! get_posts( array( 'post_type' => self::CPT, 'posts_per_page' => 1, 'post_status' => 'publish', 'fields' => 'ids' ) ) ) {
			self::create_book( __( 'The Pragmatic Programmer', 'readforge' ), __( 'Hunt & Thomas', 'readforge' ), 'want', 0, 0 );
			self::create_book( __( 'Atomic Habits', 'readforge' ), __( 'James Clear', 'readforge' ), 'reading', 40, 0 );
			self::create_book( __( 'The Hobbit', 'readforge' ), __( 'J.R.R. Tolkien', 'readforge' ), 'finished', 100, 5 );
		}
		flush_rewrite_rules();
	}

	private static function create_book( $title, $author, $shelf, $progress, $rating ) {
		$id = wp_insert_post(
			array(
				'post_type'   => self::CPT,
				'post_status' => 'publish',
				'post_title'  => sanitize_text_field( $title ),
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			$today = current_time( 'Y-m-d' );
			update_post_meta( $id, '_rf_author', sanitize_text_field( $author ) );
			update_post_meta( $id, '_rf_shelf', array_key_exists( $shelf, self::shelves() ) ? $shelf : 'want' );
			update_post_meta( $id, '_rf_progress', max( 0, min( 100, (int) $progress ) ) );
			update_post_meta( $id, '_rf_rating', max( 0, min( 5, (int) $rating ) ) );
			if ( 'reading' === $shelf ) {
				update_post_meta( $id, '_rf_started', $today );
			}
			if ( 'finished' === $shelf ) {
				update_post_meta( $id, '_rf_finished', $today );
			}
		}
		return $id;
	}

	public static function admin_menu() {
		add_menu_page(
			__( 'Books', 'readforge' ),
			__( 'Books', 'readforge' ),
			'edit_posts',
			'readforge',
			function () {
				wp_safe_redirect( home_url( '/' . self::PAGE_SLUG . '/' ) );
				exit;
			},
			'dashicons-book',
			34
		);
	}

	// ---------------------------------------------------------------- ajax --

	private static function check_request() {
		if ( ! current_user_can( 'edit_posts' ) || ! check_ajax_referer( self::NONCE, 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Not allowed' ), 403 );
		}
	}

	private static function get_book_or_fail( $id ) {
		if ( get_post_type( $id ) !== self::CPT ) {
			wp_send_json_error( array( 'message' => 'Bad request' ), 400 );
		}
		return (int) $id;
	}

	public static function ajax_add() {
		self::check_request();
		$title  = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
		$author = sanitize_text_field( wp_unslash( $_POST['author'] ?? '' ) );
		$shelf  = sanitize_key( $_POST['shelf'] ?? 'want' );
		if ( '' === $title ) {
			wp_send_json_error( array( 'message' => 'Title required' ), 400 );
		}
		$id = self::create_book( $title, $author, $shelf, 0, 0 );
		wp_send_json_success( array( 'id' => $id ) );
	}

	public static function ajax_move() {
		self::check_request();
		$id    = self::get_book_or_fail( (int) ( $_POST['book'] ?? 0 ) );
		$shelf = sanitize_key( $_POST['shelf'] ?? '' );
		if ( ! array_key_exists( $shelf, self::shelves() ) ) {
			wp_send_json_error( array( 'message' => 'Bad shelf' ), 400 );
		}
		$today = current_time( 'Y-m-d' );
		update_post_meta( $id, '_rf_shelf', $shelf );
		if ( 'reading' === $shelf && ! get_post_meta( $id, '_rf_started', true ) ) {
			update_post_meta( $id, '_rf_started', $today );
		}
		if ( 'finished' === $shelf ) {
			update_post_meta( $id, '_rf_progress', 100 );
			update_post_meta( $id, '_rf_finished', $today );
		} else {
			delete_post_meta( $id, '_rf_finished' );
		}
		wp_send_json_success();
	}

	public static function ajax_progress() {
		self::check_request();
		$id       = self::get_book_or_fail( (int) ( $_POST['book'] ?? 0 ) );
		$progress = max( 0, min( 100, (int) ( $_POST['progress'] ?? 0 ) ) );
		update_post_meta( $id, '_rf_progress', $progress );
		wp_send_json_success( array( 'progress' => $progress ) );
	}

	public static function ajax_rate() {
		self::check_request();
		$id     = self::get_book_or_fail( (int) ( $_POST['book'] ?? 0 ) );
		$rating = max( 0, min( 5, (int) ( $_POST['rating'] ?? 0 ) ) );
		update_post_meta( $id, '_rf_rating', $rating );
		wp_send_json_success( array( 'rating' => $rating ) );
	}

	public static function ajax_goal() {
		self::check_request();
		$goal = max( 1, min( 999, (int) ( $_POST['goal'] ?? 12 ) ) );
		update_option( 'rf_goal', $goal );
		wp_send_json_success( array( 'goal' => $goal ) );
	}

	public static function ajax_delete() {
		self::check_request();
		$id = self::get_book_or_fail( (int) ( $_POST['book'] ?? 0 ) );
		wp_trash_post( $id );
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

	private static function render_app() {
		$shelves = self::shelves();
		$books   = get_posts(
			array(
				'post_type'      => self::CPT,
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);
		$by_shelf = array_fill_keys( array_keys( $shelves ), array() );
		$year     = (int) current_time( 'Y' );
		$done     = 0;
		foreach ( $books as $book ) {
			$shelf = get_post_meta( $book->ID, '_rf_shelf', true );
			if ( ! isset( $by_shelf[ $shelf ] ) ) {
				$shelf = 'want';
			}
			$by_shelf[ $shelf ][] = $book;
			if ( 'finished' === $shelf && (int) gmdate( 'Y', strtotime( (string) get_post_meta( $book->ID, '_rf_finished', true ) ) ) === $year ) {
				$done++;
			}
		}
		$goal  = max( 1, (int) get_option( 'rf_goal', 12 ) );
		$nonce = wp_create_nonce( self::NONCE );

		header( 'Content-Type: text/html; charset=utf-8' );
		?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php esc_html_e( 'ReadForge', 'readforge' ); ?></title>
<style>
:root {
	--bg: #f6f7f9; --card: #ffffff; --text: #1a202c; --muted: #64748b;
	--line: #e2e8f0; --accent: #7c3aed; --star: #f59e0b;
}
@media (prefers-color-scheme: dark) {
	:root { --bg: #141020; --card: #1f1a30; --text: #ece8f8; --muted: #9a92b8; --line: #2e2749; }
}
* { box-sizing: border-box; }
body { margin: 0; background: var(--bg); color: var(--text); font: 15px/1.5 -apple-system, "Segoe UI", Roboto, sans-serif; }
.rf-wrap { max-width: 1020px; margin: 0 auto; padding: 22px 16px 60px; }
.rf-top { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
.rf-top h1 { font-size: 24px; margin: 0; }
.rf-top h1 span { color: var(--accent); }
.rf-goal { background: var(--card); border: 1px solid var(--line); border-radius: 12px; padding: 10px 16px; display: flex; align-items: center; gap: 12px; }
.rf-goal b { font-size: 18px; white-space: nowrap; }
.rf-goal input { width: 58px; padding: 5px 8px; border: 1px solid var(--line); border-radius: 8px; background: var(--bg); color: var(--text); font-size: 15px; text-align: center; }
.rf-goalbar { width: 130px; height: 8px; border-radius: 999px; background: var(--bg); overflow: hidden; }
.rf-goalbar i { display: block; height: 100%; background: var(--accent); }
.rf-addcard { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 13px 16px; margin: 16px 0 20px; }
.rf-add { display: flex; gap: 8px; flex-wrap: wrap; }
.rf-add input, .rf-add select { padding: 9px 12px; border: 1px solid var(--line); border-radius: 9px; background: var(--bg); color: var(--text); font-size: 14.5px; }
.rf-add input[name=title] { flex: 2 1 200px; }
.rf-add input[name=author] { flex: 1 1 140px; }
.rf-add button { padding: 9px 18px; border: 0; border-radius: 9px; background: var(--accent); color: #fff; font-weight: 600; font-size: 14.5px; cursor: pointer; }
.rf-board { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
.rf-shelf { background: color-mix(in srgb, var(--card) 55%, var(--bg)); border: 1px solid var(--line); border-radius: 12px; padding: 10px; min-height: 240px; }
.rf-shelf.rf-over { outline: 2px dashed var(--accent); }
.rf-shelfhead { display: flex; align-items: center; gap: 7px; font-weight: 700; font-size: 13.5px; margin: 2px 2px 10px; }
.rf-shelfhead i { width: 9px; height: 9px; border-radius: 50%; }
.rf-shelfhead small { color: var(--muted); font-weight: 600; margin-left: auto; }
.rf-book { background: var(--card); border: 1px solid var(--line); border-radius: 10px; padding: 10px 12px; margin-bottom: 8px; cursor: grab; }
.rf-book b { display: block; font-size: 14px; }
.rf-au { color: var(--muted); font-size: 12.5px; }
.rf-bar { margin-top: 8px; height: 9px; border-radius: 999px; background: var(--bg); overflow: hidden; cursor: pointer; position: relative; }
.rf-bar i { display: block; height: 100%; background: #f59e0b; pointer-events: none; }
.rf-pct { font-size: 11px; color: var(--muted); margin-top: 3px; }
.rf-stars { margin-top: 6px; font-size: 15px; letter-spacing: 2px; cursor: pointer; color: var(--line); }
.rf-stars .on { color: var(--star); }
.rf-foot { display: flex; align-items: center; margin-top: 6px; }
.rf-date { font-size: 11px; color: var(--muted); }
.rf-del { margin-left: auto; border: 0; background: none; color: var(--muted); cursor: pointer; font-size: 13px; opacity: .5; }
.rf-del:hover { opacity: 1; color: #ef4444; }
.rf-hint { margin-top: 14px; color: var(--muted); font-size: 12.5px; }
@media (max-width: 760px) { .rf-board { grid-template-columns: 1fr; } .rf-shelf { min-height: 0; } }
</style>
</head>
<body>
<div class="rf-wrap">
	<div class="rf-top">
		<h1>📚 Read<span>Forge</span></h1>
		<div class="rf-goal">
			<b><?php echo (int) $done; ?> / <span id="rf-goalnum"><?php echo (int) $goal; ?></span></b>
			<span class="rf-goalbar"><i id="rf-goalfill" style="width:<?php echo esc_attr( min( 100, round( $done / $goal * 100 ) ) ); ?>%"></i></span>
			<span style="color:var(--muted);font-size:12.5px"><?php echo esc_html( sprintf( /* translators: %d: year */ __( 'books in %d — goal:', 'readforge' ), $year ) ); ?></span>
			<input type="number" id="rf-goal" min="1" max="999" value="<?php echo (int) $goal; ?>" aria-label="<?php esc_attr_e( 'Yearly goal', 'readforge' ); ?>">
		</div>
	</div>

	<div class="rf-addcard">
		<form class="rf-add" id="rf-add">
			<input type="text" name="title" placeholder="<?php esc_attr_e( 'Book title', 'readforge' ); ?>" required>
			<input type="text" name="author" placeholder="<?php esc_attr_e( 'Author', 'readforge' ); ?>">
			<select name="shelf">
				<?php foreach ( $shelves as $key => $shelf ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $shelf['label'] ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="submit"><?php esc_html_e( 'Add book', 'readforge' ); ?></button>
		</form>
	</div>

	<div class="rf-board">
		<?php foreach ( $shelves as $key => $shelf ) : ?>
		<div class="rf-shelf" data-shelf="<?php echo esc_attr( $key ); ?>">
			<div class="rf-shelfhead">
				<i style="background:<?php echo esc_attr( $shelf['color'] ); ?>"></i>
				<?php echo esc_html( $shelf['label'] ); ?>
				<small><?php echo count( $by_shelf[ $key ] ); ?></small>
			</div>
			<?php
			foreach ( $by_shelf[ $key ] as $book ) :
				$author   = get_post_meta( $book->ID, '_rf_author', true );
				$progress = (int) get_post_meta( $book->ID, '_rf_progress', true );
				$rating   = (int) get_post_meta( $book->ID, '_rf_rating', true );
				$finished = get_post_meta( $book->ID, '_rf_finished', true );
				?>
			<div class="rf-book" draggable="true" data-book="<?php echo (int) $book->ID; ?>">
				<b><?php echo esc_html( $book->post_title ); ?></b>
				<?php if ( $author ) : ?><span class="rf-au"><?php echo esc_html( $author ); ?></span><?php endif; ?>
				<?php if ( 'reading' === $key ) : ?>
				<div class="rf-bar" title="<?php esc_attr_e( 'Click to set progress', 'readforge' ); ?>"><i style="width:<?php echo esc_attr( $progress ); ?>%"></i></div>
				<span class="rf-pct"><?php echo (int) $progress; ?>%</span>
				<?php endif; ?>
				<?php if ( 'finished' === $key ) : ?>
				<div class="rf-stars" title="<?php esc_attr_e( 'Click to rate', 'readforge' ); ?>">
					<?php for ( $s = 1; $s <= 5; $s++ ) : ?><span class="<?php echo $s <= $rating ? 'on' : ''; ?>" data-star="<?php echo (int) $s; ?>">★</span><?php endfor; ?>
				</div>
				<?php endif; ?>
				<div class="rf-foot">
					<?php if ( 'finished' === $key && $finished ) : ?>
					<span class="rf-date"><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $finished ) ) ); ?></span>
					<?php endif; ?>
					<button class="rf-del" title="<?php esc_attr_e( 'Delete book', 'readforge' ); ?>">✕</button>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
		<?php endforeach; ?>
	</div>

	<p class="rf-hint"><?php esc_html_e( 'Drag a book between shelves · click the progress bar to log where you are · click the stars to rate a finished book · dropping a book on Finished counts it toward this year\'s goal.', 'readforge' ); ?></p>
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

	document.addEventListener('click', function (e) {
		var book = e.target.closest && e.target.closest('.rf-book');

		if (e.target.classList.contains('rf-del')) {
			if (!confirm('<?php echo esc_js( __( 'Delete this book?', 'readforge' ) ); ?>')) return;
			post('rf_delete', { book: book.dataset.book }).then(function () { location.reload(); });
			return;
		}

		if (e.target.classList.contains('rf-bar')) {
			var rect = e.target.getBoundingClientRect();
			var pct = Math.round((e.clientX - rect.left) / rect.width * 100);
			post('rf_progress', { book: book.dataset.book, progress: pct }).then(function (res) {
				if (!res.success) return;
				e.target.querySelector('i').style.width = res.data.progress + '%';
				book.querySelector('.rf-pct').textContent = res.data.progress + '%';
			});
			return;
		}

		if (e.target.dataset && e.target.dataset.star) {
			var stars = e.target.closest('.rf-stars');
			var val = parseInt(e.target.dataset.star, 10);
			post('rf_rate', { book: book.dataset.book, rating: val }).then(function (res) {
				if (!res.success) return;
				stars.querySelectorAll('span').forEach(function (s, i) {
					s.classList.toggle('on', i < res.data.rating);
				});
			});
		}
	});

	document.getElementById('rf-goal').addEventListener('change', function () {
		post('rf_goal', { goal: this.value }).then(function () { location.reload(); });
	});

	// Drag between shelves.
	var dragged = null;
	document.addEventListener('dragstart', function (e) {
		var book = e.target.closest && e.target.closest('.rf-book');
		if (book) { dragged = book; e.dataTransfer.effectAllowed = 'move'; }
	});
	document.addEventListener('dragover', function (e) {
		var shelf = e.target.closest && e.target.closest('.rf-shelf');
		if (shelf && dragged) { e.preventDefault(); shelf.classList.add('rf-over'); }
	});
	document.addEventListener('dragleave', function (e) {
		var shelf = e.target.closest && e.target.closest('.rf-shelf');
		if (shelf) shelf.classList.remove('rf-over');
	});
	document.addEventListener('drop', function (e) {
		var shelf = e.target.closest && e.target.closest('.rf-shelf');
		if (!shelf || !dragged) return;
		e.preventDefault();
		shelf.classList.remove('rf-over');
		post('rf_move', { book: dragged.dataset.book, shelf: shelf.dataset.shelf })
			.then(function () { location.reload(); });
		dragged = null;
	});

	document.getElementById('rf-add').addEventListener('submit', function (e) {
		e.preventDefault();
		var f = e.target;
		post('rf_add', { title: f.title.value, author: f.author.value, shelf: f.shelf.value })
			.then(function () { location.reload(); });
	});
})();
</script>
</body>
</html>
		<?php
	}
}

ReadForge::init();
