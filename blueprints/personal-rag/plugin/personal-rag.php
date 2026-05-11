<?php
/**
 * Plugin Name: Personal RAG
 * Description: Private local RAG over WordPress posts and pages using Ollama, EmbeddingGemma, Gemma 4, and SQLite-backed storage.
 * Version: 0.1.0
 * Author: Playground
 * License: GPL-2.0-or-later
 * Text Domain: personal-rag
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Personal_RAG_Plugin {
	const VERSION        = '0.1.0';
	const REST_NAMESPACE = 'personal-rag/v1';
	const DB_VERSION     = '2';
	const OPTION_VERSION = 'personal_rag_db_version';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function activate() {
		self::install_schema();
	}

	private function __construct() {
		add_action( 'init', array( $this, 'maybe_install_schema' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'save_post', array( $this, 'handle_save_post' ), 10, 3 );
		add_action( 'before_delete_post', array( $this, 'handle_delete_post' ) );
		add_action( 'trashed_post', array( $this, 'handle_delete_post' ) );
		add_action( 'untrashed_post', array( $this, 'handle_untrashed_post' ) );
		add_action( 'plugins_loaded', array( $this, 'maybe_register_abilities' ) );
	}

	public function maybe_install_schema() {
		if ( get_option( self::OPTION_VERSION ) !== self::DB_VERSION ) {
			self::install_schema();
		}
	}

	public static function install_schema() {
		global $wpdb;

		$tables  = self::table_names();
		$charset = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta(
			"CREATE TABLE {$tables['sources']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				source_type varchar(20) NOT NULL DEFAULT 'post',
				source_id bigint(20) unsigned NOT NULL,
				post_type varchar(20) NOT NULL DEFAULT '',
				post_status varchar(20) NOT NULL DEFAULT '',
				title text NOT NULL,
				url text NOT NULL,
				content_hash char(64) NOT NULL DEFAULT '',
				updated_at datetime NOT NULL,
				indexed_at datetime DEFAULT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY source_unique (source_type, source_id),
				KEY content_hash (content_hash)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$tables['chunks']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				source_id bigint(20) unsigned NOT NULL,
				chunk_index int(11) NOT NULL DEFAULT 0,
				chunk_text longtext NOT NULL,
				chunk_hash char(64) NOT NULL DEFAULT '',
				token_estimate int(11) NOT NULL DEFAULT 0,
				embedding_status varchar(20) NOT NULL DEFAULT 'queued',
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY source_chunk (source_id, chunk_index),
				KEY source_id (source_id),
				KEY embedding_status (embedding_status)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$tables['vectors']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				chunk_id bigint(20) unsigned NOT NULL,
				model varchar(100) NOT NULL DEFAULT '',
				dimensions int(11) NOT NULL DEFAULT 0,
				vector longtext NOT NULL,
				norm double NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY chunk_id (chunk_id),
				KEY model_dimensions (model, dimensions)
			) {$charset};"
		);

		update_option( self::OPTION_VERSION, self::DB_VERSION );
	}

	private static function table_names() {
		global $wpdb;

		return array(
			'sources' => $wpdb->prefix . 'personal_rag_sources',
			'chunks'  => $wpdb->prefix . 'personal_rag_chunks',
			'vectors' => $wpdb->prefix . 'personal_rag_vectors',
		);
	}

	public function add_admin_page() {
		add_management_page(
			__( 'Personal RAG', 'personal-rag' ),
			__( 'Personal RAG', 'personal-rag' ),
			'read',
			'personal-rag',
			array( $this, 'render_admin_page' )
		);
	}

	public function enqueue_admin_assets( $hook ) {
		if ( 'tools_page_personal-rag' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'personal-rag-admin',
			plugin_dir_url( __FILE__ ) . 'assets/personal-rag.css',
			array(),
			self::VERSION
		);

		wp_enqueue_script(
			'personal-rag-admin',
			plugin_dir_url( __FILE__ ) . 'assets/personal-rag.js',
			array(),
			self::VERSION,
			true
		);

		wp_localize_script(
			'personal-rag-admin',
			'personalRagSettings',
			array(
				'restUrl'          => esc_url_raw( rest_url( self::REST_NAMESPACE ) ),
				'nonce'            => wp_create_nonce( 'wp_rest' ),
				'canManageOptions' => current_user_can( 'manage_options' ),
				'origin'           => esc_url_raw( home_url( '/' ) ),
				'defaults'         => array(
					'endpoint'       => 'http://localhost:11434',
					'embeddingModel' => 'embeddinggemma',
					'chatModel'      => 'gemma4:e4b',
					'topK'           => 5,
				),
			)
		);
	}

	public function render_admin_page() {
		?>
		<div class="wrap personal-rag-wrap">
			<div id="personal-rag-app">
				<h1><?php esc_html_e( 'Personal RAG', 'personal-rag' ); ?></h1>
				<p><?php esc_html_e( 'Loading local search assistant...', 'personal-rag' ); ?></p>
			</div>
		</div>
		<?php
	}

	public function register_rest_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/status',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_status' ),
				'permission_callback' => array( $this, 'permission_read' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/index/queue',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rest_queue_index' ),
				'permission_callback' => array( $this, 'permission_manage' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/index/batch',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_index_batch' ),
				'permission_callback' => array( $this, 'permission_manage' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/index/embeddings',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rest_save_embeddings' ),
				'permission_callback' => array( $this, 'permission_manage' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/search',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rest_search' ),
				'permission_callback' => array( $this, 'permission_read' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/reset',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rest_reset' ),
				'permission_callback' => array( $this, 'permission_manage' ),
			)
		);
	}

	public function permission_read() {
		return current_user_can( 'read' );
	}

	public function permission_manage() {
		return current_user_can( 'manage_options' );
	}

	public function rest_status() {
		return rest_ensure_response( $this->get_status() );
	}

	private function get_status() {
		global $wpdb;

		$tables = self::table_names();

		return array(
			'sources'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tables['sources']}" ),
			'chunks'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tables['chunks']}" ),
			'queued'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tables['chunks']} WHERE embedding_status = 'queued'" ),
			'embedded'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tables['vectors']}" ),
			'dbVersion'  => get_option( self::OPTION_VERSION ),
			'indexables' => count( $this->get_indexable_post_ids() ),
		);
	}

	public function rest_queue_index( WP_REST_Request $request ) {
		$params = $request->get_json_params();
		$force  = ! empty( $params['force'] );

		return rest_ensure_response( $this->queue_all_sources( $force ) );
	}

	public function rest_index_batch( WP_REST_Request $request ) {
		global $wpdb;

		$tables = self::table_names();
		$limit  = max( 1, min( 50, absint( $request->get_param( 'limit' ) ? $request->get_param( 'limit' ) : 8 ) ) );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.id, c.chunk_index, c.chunk_text, c.token_estimate, s.title, s.url, s.source_id, s.post_type
				FROM {$tables['chunks']} c
				INNER JOIN {$tables['sources']} s ON s.id = c.source_id
				WHERE c.embedding_status = 'queued'
				ORDER BY s.updated_at DESC, c.id ASC
				LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		$items = array();
		foreach ( $rows as $row ) {
			$items[] = array(
				'id'            => (int) $row['id'],
				'chunkIndex'    => (int) $row['chunk_index'],
				'text'          => $row['chunk_text'],
				'tokenEstimate' => (int) $row['token_estimate'],
				'title'         => $row['title'],
				'url'           => $row['url'],
				'sourceId'      => (int) $row['source_id'],
				'postType'      => $row['post_type'],
			);
		}

		return rest_ensure_response(
			array(
				'items'  => $items,
				'status' => $this->get_status(),
			)
		);
	}

	public function rest_save_embeddings( WP_REST_Request $request ) {
		global $wpdb;

		$params = $request->get_json_params();
		$items  = isset( $params['items'] ) && is_array( $params['items'] ) ? $params['items'] : array();
		$model  = sanitize_text_field( isset( $params['model'] ) ? $params['model'] : '' );

		if ( '' === $model ) {
			return new WP_Error( 'personal_rag_missing_model', __( 'Embedding model is required.', 'personal-rag' ), array( 'status' => 400 ) );
		}

		$tables     = self::table_names();
		$saved      = 0;
		$source_ids = array();

		foreach ( $items as $item ) {
			$chunk_id = absint( isset( $item['chunkId'] ) ? $item['chunkId'] : 0 );
			$encoded  = isset( $item['vector'] ) ? (string) $item['vector'] : '';

			if ( ! $chunk_id || '' === $encoded ) {
				continue;
			}

			$decoded = $this->decode_vector( $encoded );
			if ( is_wp_error( $decoded ) ) {
				return $decoded;
			}

			$dimension = count( $decoded['values'] );
			if ( isset( $item['dimensions'] ) && absint( $item['dimensions'] ) !== $dimension ) {
				return new WP_Error( 'personal_rag_dimension_mismatch', __( 'Embedding dimension mismatch.', 'personal-rag' ), array( 'status' => 400 ) );
			}

			$source_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT source_id FROM {$tables['chunks']} WHERE id = %d",
					$chunk_id
				)
			);

			if ( ! $source_id ) {
				continue;
			}

			$stored = $wpdb->replace(
				$tables['vectors'],
				array(
					'chunk_id'    => $chunk_id,
					'model'       => $model,
					'dimensions'  => $dimension,
					'vector'      => $decoded['encoded'],
					'norm'        => $decoded['norm'],
					'created_at'  => current_time( 'mysql' ),
				),
				array( '%d', '%s', '%d', '%s', '%f', '%s' )
			);

			if ( false === $stored ) {
				return new WP_Error(
					'personal_rag_vector_save_failed',
					$wpdb->last_error ? $wpdb->last_error : __( 'Could not save embedding vector.', 'personal-rag' ),
					array( 'status' => 500 )
				);
			}

			$wpdb->update(
				$tables['chunks'],
				array(
					'embedding_status' => 'embedded',
					'updated_at'       => current_time( 'mysql' ),
				),
				array( 'id' => $chunk_id ),
				array( '%s', '%s' ),
				array( '%d' )
			);

			$source_ids[ $source_id ] = true;
			$saved++;
		}

		$this->mark_completed_sources( array_keys( $source_ids ) );

		return rest_ensure_response(
			array(
				'saved'  => $saved,
				'status' => $this->get_status(),
			)
		);
	}

	public function rest_search( WP_REST_Request $request ) {
		global $wpdb;

		$params  = $request->get_json_params();
		$encoded = isset( $params['vector'] ) ? (string) $params['vector'] : '';
		$model   = sanitize_text_field( isset( $params['model'] ) ? $params['model'] : '' );
		$top_k   = max( 1, min( 12, absint( isset( $params['topK'] ) ? $params['topK'] : 5 ) ) );

		$query_vector = $this->decode_vector( $encoded );
		if ( is_wp_error( $query_vector ) ) {
			return $query_vector;
		}

		$tables    = self::table_names();
		$dimension = count( $query_vector['values'] );

		if ( '' !== $model ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT v.vector, v.norm, c.id AS chunk_id, c.chunk_index, c.chunk_text, s.title, s.url, s.source_id, s.post_type
					FROM {$tables['vectors']} v
					INNER JOIN {$tables['chunks']} c ON c.id = v.chunk_id
					INNER JOIN {$tables['sources']} s ON s.id = c.source_id
					WHERE v.dimensions = %d AND v.model = %s",
					$dimension,
					$model
				),
				ARRAY_A
			);
		} else {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT v.vector, v.norm, c.id AS chunk_id, c.chunk_index, c.chunk_text, s.title, s.url, s.source_id, s.post_type
					FROM {$tables['vectors']} v
					INNER JOIN {$tables['chunks']} c ON c.id = v.chunk_id
					INNER JOIN {$tables['sources']} s ON s.id = c.source_id
					WHERE v.dimensions = %d",
					$dimension
				),
				ARRAY_A
			);
		}

		$matches = array();
		foreach ( $rows as $row ) {
			$vector = $this->stored_vector_to_floats( $row['vector'] );
			if ( count( $vector ) !== $dimension ) {
				continue;
			}

			$score = $this->cosine_similarity( $query_vector['values'], $query_vector['norm'], $vector, (float) $row['norm'] );
			if ( null === $score ) {
				continue;
			}

			$matches[] = array(
				'chunkId'    => (int) $row['chunk_id'],
				'sourceId'   => (int) $row['source_id'],
				'postType'   => $row['post_type'],
				'chunkIndex' => (int) $row['chunk_index'],
				'title'      => $row['title'],
				'url'        => $row['url'],
				'text'       => $row['chunk_text'],
				'score'      => round( $score, 6 ),
			);
		}

		usort(
			$matches,
			function ( $a, $b ) {
				if ( $a['score'] === $b['score'] ) {
					return 0;
				}
				return ( $a['score'] > $b['score'] ) ? -1 : 1;
			}
		);

		return rest_ensure_response(
			array(
				'matches' => array_slice( $matches, 0, $top_k ),
				'total'   => count( $matches ),
			)
		);
	}

	public function rest_reset() {
		$this->reset_index();
		return rest_ensure_response( $this->get_status() );
	}

	public function handle_save_post( $post_id, $post, $update ) {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$this->queue_post( $post_id, false );
	}

	public function handle_delete_post( $post_id ) {
		$this->delete_source_by_wp_post( $post_id );
	}

	public function handle_untrashed_post( $post_id ) {
		$this->queue_post( $post_id, false );
	}

	private function queue_all_sources( $force = false ) {
		global $wpdb;

		$results = array(
			'queued'    => 0,
			'unchanged' => 0,
			'skipped'   => 0,
			'deleted'   => 0,
			'status'    => array(),
		);

		$post_ids = $this->get_indexable_post_ids();
		$seen     = array();

		foreach ( $post_ids as $post_id ) {
			$seen[ $post_id ] = true;
			$result           = $this->queue_post( $post_id, $force );
			if ( isset( $results[ $result ] ) ) {
				$results[ $result ]++;
			}
		}

		$tables  = self::table_names();
		$sources = $wpdb->get_results( "SELECT id, source_id FROM {$tables['sources']} WHERE source_type = 'post'", ARRAY_A );
		foreach ( $sources as $source ) {
			if ( ! isset( $seen[ (int) $source['source_id'] ] ) ) {
				$this->delete_source_by_id( (int) $source['id'] );
				$results['deleted']++;
			}
		}

		$results['status'] = $this->get_status();
		return $results;
	}

	private function queue_post( $post_id, $force = false ) {
		global $wpdb;

		$post = get_post( $post_id );
		if ( ! $post || ! $this->is_indexable_post( $post ) ) {
			$this->delete_source_by_wp_post( $post_id );
			return 'skipped';
		}

		$tables       = self::table_names();
		$title        = get_the_title( $post );
		$content      = $this->extract_post_text( $post );
		$source_text  = trim( $title . "\n\n" . $content );
		$content_hash = hash( 'sha256', $source_text );
		$permalink    = get_permalink( $post );
		$url          = $permalink ? $permalink : home_url( '?p=' . $post_id );
		$now          = current_time( 'mysql' );

		$source = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$tables['sources']} WHERE source_type = %s AND source_id = %d",
				'post',
				$post_id
			),
			ARRAY_A
		);

		if ( $source && ! $force && hash_equals( $source['content_hash'], $content_hash ) ) {
			return 'unchanged';
		}

		$source_data = array(
			'source_type'  => 'post',
			'source_id'    => $post_id,
			'post_type'    => $post->post_type,
			'post_status'  => $post->post_status,
			'title'        => $title,
			'url'          => (string) $url,
			'content_hash' => $content_hash,
			'updated_at'   => $now,
			'indexed_at'   => null,
		);

		if ( $source ) {
			$source_id = (int) $source['id'];
			$wpdb->update(
				$tables['sources'],
				$source_data,
				array( 'id' => $source_id ),
				array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
		} else {
			$wpdb->insert(
				$tables['sources'],
				$source_data,
				array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
			);
				$source_id = (int) $wpdb->insert_id;
			}

			if ( ! $source_id ) {
				return 'skipped';
			}

			$this->delete_chunks_for_source( $source_id );

			$chunks = $this->chunk_text( $source_text, $title );
			if ( empty( $chunks ) ) {
				$this->delete_source_by_id( $source_id );
				return 'skipped';
			}

			foreach ( $chunks as $index => $chunk_text ) {
			$wpdb->insert(
				$tables['chunks'],
				array(
					'source_id'        => $source_id,
					'chunk_index'      => $index,
					'chunk_text'       => $chunk_text,
					'chunk_hash'       => hash( 'sha256', $chunk_text ),
					'token_estimate'   => $this->estimate_tokens( $chunk_text ),
					'embedding_status' => 'queued',
					'created_at'       => $now,
					'updated_at'       => $now,
				),
				array( '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s' )
			);
		}

		return 'queued';
	}

	private function get_indexable_post_ids() {
		return get_posts(
			array(
				'post_type'      => array( 'post', 'page' ),
				'post_status'    => array( 'publish', 'private' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);
	}

	private function is_indexable_post( $post ) {
		return in_array( $post->post_type, array( 'post', 'page' ), true )
			&& in_array( $post->post_status, array( 'publish', 'private' ), true );
	}

	private function extract_post_text( WP_Post $post ) {
		$content = do_blocks( $post->post_content );
		$content = strip_shortcodes( $content );
		$content = wp_strip_all_tags( $content, true );
		$content = html_entity_decode( $content, ENT_QUOTES, get_bloginfo( 'charset' ) );
		$content = preg_replace( '/\s+/', ' ', $content );

		return trim( $content );
	}

	private function chunk_text( $text, $title ) {
		$text = trim( preg_replace( '/\s+/', ' ', $text ) );
		if ( '' === $text ) {
			return array();
		}

		$words       = preg_split( '/\s+/', $text );
		$target_size = 650;
		$overlap     = 80;
		$chunks      = array();
		$count       = count( $words );

		for ( $start = 0; $start < $count; $start += max( 1, $target_size - $overlap ) ) {
			$slice = array_slice( $words, $start, $target_size );
			if ( empty( $slice ) ) {
				break;
			}

			$chunk = implode( ' ', $slice );
			if ( $title && 0 !== strpos( $chunk, $title ) ) {
				$chunk = $title . "\n\n" . $chunk;
			}
			$chunks[] = $chunk;

			if ( $start + $target_size >= $count ) {
				break;
			}
		}

		return $chunks;
	}

	private function estimate_tokens( $text ) {
		return max( 1, (int) ceil( strlen( $text ) / 4 ) );
	}

	private function delete_source_by_wp_post( $post_id ) {
		global $wpdb;

		$tables = self::table_names();
		$id     = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$tables['sources']} WHERE source_type = %s AND source_id = %d",
				'post',
				$post_id
			)
		);

		if ( $id ) {
			$this->delete_source_by_id( $id );
		}
	}

	private function delete_source_by_id( $source_id ) {
		global $wpdb;

		$tables = self::table_names();
		$this->delete_chunks_for_source( $source_id );
		$wpdb->delete( $tables['sources'], array( 'id' => $source_id ), array( '%d' ) );
	}

	private function delete_chunks_for_source( $source_id ) {
		global $wpdb;

		$tables = self::table_names();
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$tables['vectors']} WHERE chunk_id IN (SELECT id FROM {$tables['chunks']} WHERE source_id = %d)",
				$source_id
			)
		);
		$wpdb->delete( $tables['chunks'], array( 'source_id' => $source_id ), array( '%d' ) );
	}

	private function reset_index() {
		global $wpdb;

		$tables = self::table_names();
		$wpdb->query( "DELETE FROM {$tables['vectors']}" );
		$wpdb->query( "DELETE FROM {$tables['chunks']}" );
		$wpdb->query( "DELETE FROM {$tables['sources']}" );
	}

	private function mark_completed_sources( $source_ids ) {
		global $wpdb;

		if ( empty( $source_ids ) ) {
			return;
		}

		$tables = self::table_names();
		foreach ( $source_ids as $source_id ) {
			$queued = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$tables['chunks']} WHERE source_id = %d AND embedding_status = 'queued'",
					$source_id
				)
			);
			if ( 0 === $queued ) {
				$wpdb->update(
					$tables['sources'],
					array( 'indexed_at' => current_time( 'mysql' ) ),
					array( 'id' => $source_id ),
					array( '%s' ),
					array( '%d' )
				);
			}
		}
	}

	private function decode_vector( $encoded ) {
		$binary = base64_decode( $encoded, true );
		if ( false === $binary || '' === $binary || 0 !== strlen( $binary ) % 4 ) {
			return new WP_Error( 'personal_rag_invalid_vector', __( 'Vector payload is invalid.', 'personal-rag' ), array( 'status' => 400 ) );
		}

		$values = $this->binary_to_floats( $binary );
		if ( empty( $values ) ) {
			return new WP_Error( 'personal_rag_empty_vector', __( 'Vector payload is empty.', 'personal-rag' ), array( 'status' => 400 ) );
		}

		return array(
			'binary'  => $binary,
			'encoded' => base64_encode( $binary ),
			'values'  => $values,
			'norm'    => $this->vector_norm( $values ),
		);
	}

	private function stored_vector_to_floats( $stored ) {
		$binary = base64_decode( (string) $stored, true );
		if ( false !== $binary && '' !== $binary && 0 === strlen( $binary ) % 4 ) {
			return $this->binary_to_floats( $binary );
		}

		return $this->binary_to_floats( $stored );
	}

	private function binary_to_floats( $binary ) {
		$values = unpack( 'f*', $binary );
		return $values ? array_values( $values ) : array();
	}

	private function vector_norm( $values ) {
		$sum = 0.0;
		foreach ( $values as $value ) {
			$sum += (float) $value * (float) $value;
		}
		return sqrt( $sum );
	}

	private function cosine_similarity( $a, $a_norm, $b, $b_norm ) {
		if ( $a_norm <= 0 || $b_norm <= 0 || count( $a ) !== count( $b ) ) {
			return null;
		}

		$dot = 0.0;
		$n   = count( $a );
		for ( $i = 0; $i < $n; $i++ ) {
			$dot += (float) $a[ $i ] * (float) $b[ $i ];
		}

		return $dot / ( $a_norm * $b_norm );
	}

	public function maybe_register_abilities() {
		if ( ! function_exists( 'wp_register_ability_category' ) || ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_ability_category' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
		add_filter( 'ai_assistant_ability_domains', array( $this, 'ability_domains' ) );
	}

	public function register_ability_category() {
		wp_register_ability_category(
			'personal-rag',
			array(
				'label'       => __( 'Personal RAG', 'personal-rag' ),
				'description' => __( 'Private local retrieval over WordPress content.', 'personal-rag' ),
			)
		);
	}

	public function register_abilities() {
		wp_register_ability(
			'personal-rag/get-status',
			array(
				'label'               => __( 'Get Personal RAG Status', 'personal-rag' ),
				'description'         => 'Returns local RAG index counts: source count, chunk count, queued embeddings, and embedded vectors.',
				'category'            => 'personal-rag',
				'execute_callback'    => array( $this, 'ability_get_status' ),
				'permission_callback' => array( $this, 'permission_read' ),
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);
	}

	public function ability_get_status() {
		return $this->get_status();
	}

	public function ability_domains( $domains ) {
		$domains['personal-rag'] = 'private rag, local search, site knowledge, blog search, embedded WordPress content';
		return $domains;
	}
}

register_activation_hook( __FILE__, array( 'Personal_RAG_Plugin', 'activate' ) );
Personal_RAG_Plugin::instance();
