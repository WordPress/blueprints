<?php
/**
 * Setup content for the Block Editor Creator Lab blueprint.
 *
 * @package BlockEditorCreatorLab
 */

require_once '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

if ( ! function_exists( 'creator_lab_find_post_id' ) ) {
	/**
	 * Find a post by slug, post type, and any status.
	 *
	 * @param string $slug      Post slug.
	 * @param string $post_type Post type.
	 * @return int Post ID, or 0 when missing.
	 */
	function creator_lab_find_post_id( $slug, $post_type ) {
		$query = new WP_Query(
			array(
				'name'           => $slug,
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => 1,
			)
		);

		return empty( $query->posts ) ? 0 : (int) $query->posts[0];
	}
}

if ( ! function_exists( 'creator_lab_upsert_post' ) ) {
	/**
	 * Create or update a post-like object by slug.
	 *
	 * @param array $post_data Post data for wp_insert_post/wp_update_post.
	 * @return int Post ID.
	 */
	function creator_lab_upsert_post( $post_data ) {
		$existing_id = creator_lab_find_post_id( $post_data['post_name'], $post_data['post_type'] );

		if ( $existing_id ) {
			$post_data['ID'] = $existing_id;
			return (int) wp_update_post( wp_slash( $post_data ) );
		}

		return (int) wp_insert_post( wp_slash( $post_data ) );
	}
}

if ( ! function_exists( 'creator_lab_term_id' ) ) {
	/**
	 * Return an existing term ID or create the term.
	 *
	 * @param string $name     Term name.
	 * @param string $taxonomy Taxonomy slug.
	 * @return int Term ID.
	 */
	function creator_lab_term_id( $name, $taxonomy ) {
		$existing = term_exists( $name, $taxonomy );
		if ( is_array( $existing ) ) {
			return (int) $existing['term_id'];
		}
		if ( $existing ) {
			return (int) $existing;
		}

		$created = wp_insert_term( $name, $taxonomy );
		return is_wp_error( $created ) ? 0 : (int) $created['term_id'];
	}
}

if ( ! function_exists( 'creator_lab_editor_url' ) ) {
	/**
	 * Build the editor URL for an exercise.
	 *
	 * @param array $exercise Exercise configuration.
	 * @param int   $work_id  Work post/page ID.
	 * @return string URL.
	 */
	function creator_lab_editor_url( $exercise, $work_id ) {
		if ( ! empty( $exercise['editor_path'] ) ) {
			return admin_url( $exercise['editor_path'] );
		}

		return admin_url( 'post.php?post=' . (int) $work_id . '&action=edit' );
	}
}

if ( ! function_exists( 'creator_lab_button' ) ) {
	/**
	 * Render a button-style link.
	 *
	 * @param string $url   URL.
	 * @param string $label Link label.
	 * @param string $class Extra class.
	 * @return string HTML.
	 */
	function creator_lab_button( $url, $label, $class = '' ) {
		return sprintf(
			'<a class="lab-button %1$s" href="%2$s">%3$s</a>',
			esc_attr( $class ),
			esc_url( $url ),
			esc_html( $label )
		);
	}
}

if ( ! function_exists( 'creator_lab_guide_content' ) ) {
	/**
	 * Build a public guide page for an exercise.
	 *
	 * @param array $exercise Exercise configuration.
	 * @param int   $work_id  Editable work post/page ID.
	 * @return string Post content.
	 */
	function creator_lab_guide_content( $exercise, $work_id ) {
		$steps = '';
		foreach ( $exercise['steps'] as $index => $step ) {
			$steps .= sprintf(
				'<li><span>Paso %1$02d</span><p>%2$s</p></li>',
				$index + 1,
				esc_html( $step )
			);
		}

		$checklist = '';
		foreach ( $exercise['checklist'] as $item ) {
			$checklist .= '<li>' . esc_html( $item ) . '</li>';
		}

		$actions = creator_lab_button( creator_lab_editor_url( $exercise, $work_id ), $exercise['primary_action'], 'primary' );
		if ( empty( $exercise['editor_path'] ) ) {
			$actions .= creator_lab_button( get_preview_post_link( $work_id ), 'Previsualizar borrador', 'page' );
		} else {
			$actions .= creator_lab_button( admin_url( 'post.php?post=' . (int) $work_id . '&action=edit' ), 'Abrir nota de trabajo', 'page' );
		}
		$actions .= creator_lab_button( $exercise['source'], 'Recurso Learn WordPress' );

		return '<!-- wp:html -->' . "\n" .
			'<article class="creator-lab exercise-guide">' .
			'<section class="lab-hero exercise-hero">' .
			'<p class="lab-kicker">' . esc_html( $exercise['module'] ) . '</p>' .
			'<h1>' . esc_html( $exercise['title'] ) . '</h1>' .
			'<p class="lab-lede">' . esc_html( $exercise['story'] ) . '</p>' .
			'<div class="lab-actions">' . $actions . '</div>' .
			'</section>' .
			'<section class="lab-section exercise-overview">' .
			'<div class="exercise-meta">' .
			'<span>' . esc_html( $exercise['duration'] ) . '</span>' .
			'<span>' . esc_html( $exercise['surface'] ) . '</span>' .
			'<span>' . esc_html( $exercise['difficulty'] ) . '</span>' .
			'</div>' .
			'<div class="lab-callout exercise-objective"><div><p class="lab-tag">Objetivo</p><h2>Resultado esperado</h2></div><p>' . esc_html( $exercise['objective'] ) . '</p></div>' .
			'</section>' .
			'<section class="lab-section exercise-step-panel">' .
			'<div class="lab-section-header"><div><p class="lab-tag">Guion de práctica</p><h2>Sigue los pasos</h2><p class="lab-section-intro">Lee esta página, abre el editor y completa cada acción dentro de WordPress. El borrador ya contiene la estructura inicial.</p></div></div>' .
			'<ol class="exercise-steps">' . $steps . '</ol>' .
			'</section>' .
			'<section class="lab-section creator-panel exercise-finish">' .
			'<h3>Checklist de entrega</h3>' .
			'<ul class="checklist">' . $checklist . '</ul>' .
			'</section>' .
			'</article>' . "\n" .
			'<!-- /wp:html -->';
	}
}

if ( ! function_exists( 'creator_lab_hub_content' ) ) {
	/**
	 * Build the public exercise hub.
	 *
	 * @param array $records Exercise records.
	 * @return string Post content.
	 */
	function creator_lab_hub_content( $records ) {
		$cards = '';
		foreach ( $records as $record ) {
			$exercise = $record['exercise'];
			$cards   .= sprintf(
				'<a class="exercise-card" href="%1$s"><span>%2$s</span><h3>%3$s</h3><p>%4$s</p><small>%5$s · %6$s</small></a>',
				esc_url( get_permalink( $record['guide_id'] ) ),
				esc_html( $exercise['module'] ),
				esc_html( $exercise['title'] ),
				esc_html( $exercise['objective'] ),
				esc_html( $exercise['duration'] ),
				esc_html( $exercise['surface'] )
			);
		}

		return '<!-- wp:html -->' . "\n" .
			'<article class="creator-lab lab-hub">' .
			'<section class="lab-hero">' .
			'<p class="lab-kicker">Estructura · 30h</p>' .
			'<h1>Laboratorio de creación con bloques</h1>' .
			'<p class="lab-lede">Ocho misiones guiadas para practicar estructura de información, bloques, patrones, plantillas y estilos globales dentro de WordPress.</p>' .
			'<div class="lab-actions">' . creator_lab_button( admin_url( 'edit.php?post_type=page' ), 'Ver páginas del laboratorio', 'primary' ) . creator_lab_button( admin_url( 'site-editor.php' ), 'Abrir el Editor del sitio', 'page' ) . '</div>' .
			'</section>' .
			'<section class="lab-section">' .
			'<div class="lab-section-header"><div><p class="lab-tag">Misiones</p><h2>Elige un ejercicio</h2><p class="lab-section-intro">Cada página explica la historia, el objetivo, los pasos y el borrador que debes editar.</p></div></div>' .
			'<div class="exercise-grid">' . $cards . '</div>' .
			'</section>' .
			'</article>' . "\n" .
			'<!-- /wp:html -->';
	}
}

if ( ! function_exists( 'creator_lab_work_note' ) ) {
	/**
	 * Wrap starter content in a visible work brief.
	 *
	 * @param string $title       Brief title.
	 * @param string $description Brief text.
	 * @param string $body        Starter blocks.
	 * @return string Post content.
	 */
	function creator_lab_work_note( $title, $description, $body ) {
		return '<!-- wp:html -->' .
			'<section class="work-brief"><p class="lab-tag">Borrador de trabajo</p><h2>' . esc_html( $title ) . '</h2><p>' . esc_html( $description ) . '</p></section>' .
			'<!-- /wp:html -->' . "\n\n" .
			$body;
	}
}

if ( ! function_exists( 'creator_lab_seed_media' ) ) {
	/**
	 * Add one bundled WordPress image to the Media Library.
	 *
	 * @param string $source_path Absolute source image path.
	 * @return int Attachment ID, or 0 when unavailable.
	 */
	function creator_lab_seed_media( $source_path ) {
		$existing = get_page_by_path( 'creator-lab-referencia-visual', OBJECT, 'attachment' );
		if ( $existing instanceof WP_Post ) {
			return (int) $existing->ID;
		}

		if ( ! is_readable( $source_path ) ) {
			return 0;
		}

		$temp_file = wp_tempnam( 'creator-lab-referencia.webp' );
		if ( ! $temp_file || ! copy( $source_path, $temp_file ) ) {
			return 0;
		}

		$attachment_id = media_handle_sideload(
			array(
				'name'     => 'creator-lab-referencia.webp',
				'tmp_name' => $temp_file,
			),
			0,
			'Escritura y creación de contenidos'
		);

		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $temp_file );
			return 0;
		}

		wp_update_post(
			array(
				'ID'           => $attachment_id,
				'post_name'    => 'creator-lab-referencia-visual',
				'post_excerpt' => 'Imagen de referencia para practicar imágenes destacadas y bloques de medios.',
			)
		);
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', 'Persona escribiendo a máquina en una mesa de trabajo' );

		return (int) $attachment_id;
	}
}

if ( ! function_exists( 'creator_lab_set_editor_preferences' ) ) {
	/**
	 * Keep first-run guides from covering the exercise instructions.
	 */
	function creator_lab_set_editor_preferences() {
		global $wpdb;

		update_user_meta(
			1,
			$wpdb->get_blog_prefix() . 'persisted_preferences',
			array(
				'_modified'      => gmdate( 'c' ),
				'core/edit-post' => array(
					'welcomeGuide'         => false,
					'welcomeGuideTemplate' => false,
				),
				'core/edit-site' => array(
					'welcomeGuide'         => false,
					'welcomeGuideStyles'   => false,
					'welcomeGuidePage'     => false,
					'welcomeGuideTemplate' => false,
				),
			)
		);
	}
}

$default_content = array(
	array( 'hello-world', 'post' ),
	array( 'hola-mundo', 'post' ),
	array( 'sample-page', 'page' ),
	array( 'pagina-de-ejemplo', 'page' ),
);

foreach ( $default_content as $default_item ) {
	$default_id = creator_lab_find_post_id( $default_item[0], $default_item[1] );
	if ( $default_id ) {
		wp_delete_post( $default_id, true );
	}
}

$privacy_page_id = (int) get_option( 'wp_page_for_privacy_policy' );
if ( $privacy_page_id ) {
	wp_delete_post( $privacy_page_id, true );
	update_option( 'wp_page_for_privacy_policy', 0 );
}

creator_lab_set_editor_preferences();

update_option( 'permalink_structure', '/%postname%/' );
global $wp_rewrite;
$wp_rewrite->set_permalink_structure( '/%postname%/' );

$lab_category_id  = creator_lab_term_id( 'Laboratorio', 'category' );
$news_category_id = creator_lab_term_id( 'Noticias del laboratorio', 'category' );
$default_category_id = (int) get_option( 'default_category' );
if ( $default_category_id ) {
	wp_update_term(
		$default_category_id,
		'category',
		array(
			'name' => 'Sin categoría',
			'slug' => 'sin-categoria',
		)
	);
}
$featured_image_id = creator_lab_seed_media(
	get_theme_root() . '/twentytwentyfive/assets/images/typewriter.webp'
);

$sample_news = array(
	array(
		'slug'    => 'apertura-del-laboratorio',
		'title'   => 'Apertura del laboratorio',
		'excerpt' => 'La clase inicia con una exploración de páginas, entradas y bloques.',
	),
	array(
		'slug'    => 'galeria-de-proyectos',
		'title'   => 'Galería de proyectos',
		'excerpt' => 'Los estudiantes preparan ejemplos para practicar patrones y medios.',
	),
	array(
		'slug'    => 'revision-de-estilos',
		'title'   => 'Revisión de estilos',
		'excerpt' => 'El grupo revisa color, tipografía y consistencia visual.',
	),
);

foreach ( $sample_news as $index => $item ) {
	$post_id = creator_lab_upsert_post(
		array(
			'post_title'    => $item['title'],
			'post_name'     => $item['slug'],
			'post_excerpt'  => $item['excerpt'],
			'post_status'   => 'publish',
			'post_type'     => 'post',
			'post_author'   => 1,
			'post_date'     => gmdate( 'Y-m-d H:i:s', strtotime( '2026-06-18 10:0' . $index . ':00' ) ),
			'post_content'  => '<!-- wp:paragraph --><p>' . esc_html( $item['excerpt'] ) . '</p><!-- /wp:paragraph -->',
			'post_category' => array_filter( array( $lab_category_id, $news_category_id ) ),
		)
	);

	if ( $post_id ) {
		wp_set_post_terms( $post_id, array( 'estructura', 'bloques' ), 'post_tag', false );
		if ( $featured_image_id ) {
			set_post_thumbnail( $post_id, $featured_image_id );
		}
	}
}

$exercises = array(
	array(
		'slug'           => 'pagina-o-entrada',
		'module'         => '01 · Modelo de contenido',
		'title'          => 'Decide si necesitas una página o una entrada',
		'source'         => 'https://learn.wordpress.org/lesson/posts-vs-pages-whats-the-difference/',
		'duration'       => '25 min',
		'surface'        => 'Editor de páginas',
		'difficulty'     => 'Inicial',
		'story'          => 'Tu proyecto necesita ordenar información estable y actualizaciones. La misión es separar lo que vive como página de lo que funciona mejor como entrada.',
		'objective'      => 'Crear una página de estructura que explique qué contenido será permanente y qué contenido será cronológico.',
		'primary_action' => 'Editar la página de trabajo',
		'work_type'      => 'page',
		'work_title'     => 'Borrador: mapa de información',
		'work_slug'      => 'borrador-mapa-de-informacion',
		'work_brief'     => 'Completa este mapa para decidir qué partes del proyecto serán páginas y cuáles serán entradas.',
		'work_content'   => <<<'HTML'
<!-- wp:heading -->
<h2 class="wp-block-heading">Contenido permanente</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul><li>Agrega una página para información que no cambia con frecuencia.</li><li>Describe por qué esta información debe estar siempre accesible.</li></ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Actualizaciones</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul><li>Agrega tres ideas de entradas para noticias, anuncios o reflexiones.</li><li>Explica cómo ayudarán las categorías o etiquetas.</li></ul>
<!-- /wp:list -->
HTML,
		'steps'          => array(
			'Abre el borrador de trabajo y revisa las dos secciones iniciales.',
			'En la sección de contenido permanente, agrega al menos tres páginas que tu proyecto necesitará.',
			'En la sección de actualizaciones, agrega al menos tres ideas de entradas.',
			'Añade una frase final que explique la diferencia entre página y entrada para tu proyecto.',
		),
		'checklist'      => array(
			'La página incluye contenido permanente y actualizaciones.',
			'Cada ejemplo tiene una razón clara.',
			'La diferencia entre página y entrada queda explicada.',
		),
	),
	array(
		'slug'           => 'crear-cover-block',
		'module'         => '02 · Bloques',
		'title'          => 'Construye una portada con el bloque Cover',
		'source'         => 'https://learn.wordpress.org/lesson/nesting-and-using-blocks-to-create-visually-appealing-content/',
		'duration'       => '35 min',
		'surface'        => 'Editor de páginas',
		'difficulty'     => 'Inicial',
		'story'          => 'Vas a crear el inicio visual de una página de campaña. La portada debe contar una promesa clara y llevar a una acción.',
		'objective'      => 'Insertar un bloque Cover con título, texto breve y botón, ajustando alineación, color y espaciado.',
		'primary_action' => 'Editar la portada',
		'work_type'      => 'page',
		'work_title'     => 'Borrador: portada de campaña',
		'work_slug'      => 'borrador-portada-de-campana',
		'work_brief'     => 'Usa este borrador para insertar una portada completa al inicio de la página.',
		'work_content'   => <<<'HTML'
<!-- wp:paragraph -->
<p>Inserta un bloque Cover justo encima de este párrafo. Dentro del Cover, agrega un título, un texto breve y un botón.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Contenido de apoyo</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Después de la portada, explica en dos o tres frases por qué la campaña importa para la audiencia.</p>
<!-- /wp:paragraph -->
HTML,
		'steps'          => array(
			'Haz clic al inicio del borrador y abre el insertador de bloques.',
			'Busca Fondo (Cover) y agrega el bloque antes del primer párrafo.',
			'Dentro del Cover, escribe un título directo y un párrafo de apoyo.',
			'Agrega un bloque Botón dentro del Cover con una llamada a la acción.',
			'Ajusta color, altura mínima y alineación hasta que la portada tenga una jerarquía clara.',
		),
		'checklist'      => array(
			'El bloque Cover aparece al inicio.',
			'La portada contiene título, párrafo y botón.',
			'El contraste permite leer el texto.',
			'La acción principal es clara.',
		),
	),
	array(
		'slug'           => 'ajustes-de-entrada',
		'module'         => '03 · Entradas',
		'title'          => 'Configura una entrada completa',
		'source'         => 'https://learn.wordpress.org/lesson/creating-posts-and-pages-with-the-wordpress-block-editor/',
		'duration'       => '30 min',
		'surface'        => 'Editor de entradas',
		'difficulty'     => 'Inicial',
		'story'          => 'Una entrada no termina en el contenido. También necesita categoría, etiquetas, extracto, fecha e imagen destacada para funcionar dentro del sitio.',
		'objective'      => 'Completar los ajustes principales de una entrada y preparar su publicación programada.',
		'primary_action' => 'Editar la entrada',
		'work_type'      => 'post',
		'work_title'     => 'Borrador: lanzamiento del taller',
		'work_slug'      => 'borrador-lanzamiento-del-taller',
		'work_brief'     => 'Convierte este borrador en una entrada preparada para publicar.',
		'work_content'   => <<<'HTML'
<!-- wp:heading -->
<h2 class="wp-block-heading">Lanzamiento del taller</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Escribe una introducción breve para anunciar el taller y explicar qué aprenderá la audiencia.</p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul><li>Agrega una categoría relacionada con la clase.</li><li>Agrega tres etiquetas útiles.</li><li>Escribe un extracto de una frase.</li></ul>
<!-- /wp:list -->
HTML,
		'steps'          => array(
			'Abre el panel de ajustes de la entrada.',
			'Asigna la categoría Laboratorio, desmarca Sin categoría y agrega tres etiquetas descriptivas.',
			'Escribe un extracto breve que resuma el valor de la entrada.',
			'Abre Imagen destacada y elige Escritura y creación de contenidos en la Biblioteca de medios.',
			'Cambia la fecha para programar la publicación en un momento futuro.',
		),
		'checklist'      => array(
			'La entrada tiene categoría.',
			'La entrada tiene etiquetas útiles.',
			'Existe un extracto claro.',
			'La entrada tiene la imagen destacada de referencia.',
			'La fecha de publicación fue revisada.',
		),
	),
	array(
		'slug'           => 'pagina-con-patrones',
		'module'         => '04 · Patrones',
		'title'          => 'Ensambla una página usando patrones',
		'source'         => 'https://learn.wordpress.org/lesson/using-block-patterns-2/',
		'duration'       => '35 min',
		'surface'        => 'Editor de páginas',
		'difficulty'     => 'Inicial',
		'story'          => 'La página necesita verse terminada rápido. En lugar de diseñar cada bloque desde cero, vas a partir de patrones y adaptarlos.',
		'objective'      => 'Insertar y personalizar al menos dos patrones para construir una página coherente.',
		'primary_action' => 'Editar la página',
		'work_type'      => 'page',
		'work_title'     => 'Borrador: página con patrones',
		'work_slug'      => 'borrador-pagina-con-patrones',
		'work_brief'     => 'Usa patrones para transformar este esquema en una página presentable.',
		'work_content'   => <<<'HTML'
<!-- wp:heading -->
<h2 class="wp-block-heading">Sección principal</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Reemplaza esta sección por un patrón de bienvenida, llamada a la acción o servicios.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Sección secundaria</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Agrega un segundo patrón y adapta textos, botones e imágenes.</p>
<!-- /wp:paragraph -->
HTML,
		'steps'          => array(
			'Abre el insertador y cambia a la pestaña de patrones.',
			'Elige el patrón Presentación con llamada a la acción para reemplazar la sección principal.',
			'Edita todos los textos para que respondan al proyecto de clase.',
			'Agrega el patrón Historia en tres pasos para la sección secundaria.',
			'Revisa espaciado, alineación y orden de las secciones.',
		),
		'checklist'      => array(
			'La página usa al menos dos patrones.',
			'Los textos ya no son genéricos.',
			'Los botones tienen acciones claras.',
			'La página mantiene una narrativa visual.',
		),
	),
	array(
		'slug'           => 'patron-sincronizado',
		'module'         => '05 · Patrones sincronizados',
		'title'          => 'Crea una llamada a la acción reutilizable',
		'source'         => 'https://learn.wordpress.org/lesson/creating-your-own-custom-synced-and-non-synced-patterns/',
		'duration'       => '40 min',
		'surface'        => 'Editor de páginas',
		'difficulty'     => 'Intermedio',
		'story'          => 'Tu sitio necesita repetir una invitación en varias páginas. La misión es convertir esa invitación en un patrón reutilizable.',
		'objective'      => 'Crear un bloque de llamada a la acción, guardarlo como patrón y reutilizarlo en otra zona del contenido.',
		'primary_action' => 'Editar la CTA',
		'work_type'      => 'page',
		'work_title'     => 'Borrador: CTA reutilizable',
		'work_slug'      => 'borrador-cta-reutilizable',
		'work_brief'     => 'Transforma la llamada a la acción en un patrón sincronizado o no sincronizado según indique el docente.',
		'work_content'   => <<<'HTML'
<!-- wp:group {"className":"creator-panel","layout":{"type":"constrained"}} -->
<div class="wp-block-group creator-panel"><!-- wp:heading -->
<h2 class="wp-block-heading">Unete al laboratorio</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Recibe una guía breve para seguir practicando bloques, patrones y estilos.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"cyan","textColor":"ink"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-ink-color has-cyan-background-color has-text-color has-background wp-element-button">Quiero practicar</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
HTML,
		'steps'          => array(
			'Selecciona el grupo completo de la llamada a la acción.',
			'Abre el menú de opciones del bloque y busca la opción para crear un patrón.',
			'Asigna un nombre claro como CTA Laboratorio.',
			'Decide si el patrón será sincronizado o no sincronizado según el objetivo de reutilización.',
			'Inserta el patrón de nuevo debajo del contenido para comprobar que funciona.',
		),
		'checklist'      => array(
			'La CTA está agrupada correctamente.',
			'El patrón tiene un nombre reconocible.',
			'El patrón se insertó al menos una segunda vez.',
			'El comportamiento sincronizado o no sincronizado fue comprobado.',
		),
	),
	array(
		'slug'           => 'partes-de-plantilla',
		'module'         => '06 · Editor del sitio',
		'title'          => 'Edita cabecera y pie como partes de plantilla',
		'source'         => 'https://learn.wordpress.org/lesson/using-template-parts/',
		'duration'       => '45 min',
		'surface'        => 'Editor del sitio',
		'difficulty'     => 'Intermedio',
		'story'          => 'El sitio necesita una identidad reconocible. Vas a modificar la cabecera y el pie sin tocar el contenido de las páginas.',
		'objective'      => 'Abrir el Editor del sitio, editar la cabecera y el pie, y comprobar que los cambios se reflejan en varias páginas.',
		'primary_action' => 'Abrir Editor del sitio',
		'editor_path'    => 'site-editor.php',
		'work_type'      => 'page',
		'work_title'     => 'Nota: cambios en partes de plantilla',
		'work_slug'      => 'nota-cambios-partes-plantilla',
		'work_brief'     => 'Usa esta nota para registrar los cambios que realizaste en la cabecera y el pie.',
		'work_content'   => <<<'HTML'
<!-- wp:heading -->
<h2 class="wp-block-heading">Registro de cambios</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul><li>Describe qué cambiaste en la cabecera.</li><li>Describe qué cambiaste en el pie.</li><li>Explica en qué páginas se puede ver el cambio.</li></ul>
<!-- /wp:list -->
HTML,
		'steps'          => array(
			'Abre Apariencia > Editor o usa el botón del ejercicio.',
			'En Patrones > Gestionar mis patrones > Partes de plantilla, abre Cabecera y edita el título del sitio o la navegación.',
			'Abre Pie de página y agrega una frase corta del laboratorio.',
			'Guarda los cambios globales del sitio.',
			'Vuelve a una página del laboratorio y comprueba que la cabecera y el pie aparecen actualizados.',
		),
		'checklist'      => array(
			'La cabecera fue modificada desde el Editor del sitio.',
			'El pie fue modificado desde el Editor del sitio.',
			'Los cambios se guardaron como cambios globales.',
			'La nota de trabajo resume lo realizado.',
		),
	),
	array(
		'slug'           => 'query-loop-noticias',
		'module'         => '07 · Query Loop',
		'title'          => 'Construye una sección de noticias con Query Loop',
		'source'         => 'https://learn.wordpress.org/lesson/taking-advantage-of-query-loops-2/',
		'duration'       => '45 min',
		'surface'        => 'Editor de páginas',
		'difficulty'     => 'Intermedio',
		'story'          => 'El sitio ya tiene entradas de ejemplo. Tu tarea es crear una sección que las muestre automáticamente con un Query Loop.',
		'objective'      => 'Insertar un bloque Query Loop filtrado por entradas y ajustar título, extracto y enlace.',
		'primary_action' => 'Editar Query Loop',
		'work_type'      => 'page',
		'work_title'     => 'Borrador: noticias del laboratorio',
		'work_slug'      => 'borrador-noticias-del-laboratorio',
		'work_brief'     => 'Agrega un Query Loop debajo del encabezado para mostrar las noticias del laboratorio.',
		'work_content'   => <<<'HTML'
<!-- wp:heading -->
<h2 class="wp-block-heading">Noticias del laboratorio</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Inserta debajo un bloque Query Loop. Configúralo para mostrar entradas recientes y revisa que aparezcan título y extracto.</p>
<!-- /wp:paragraph -->
HTML,
		'steps'          => array(
			'Abre el borrador y coloca el cursor debajo del párrafo inicial.',
			'Inserta el bloque Query Loop (Bucle de consulta).',
			'Elige una variación sencilla que muestre título y extracto.',
			'Configura la consulta para mostrar entradas recientes.',
			'Reduce la cantidad de entradas si la sección queda demasiado larga.',
		),
		'checklist'      => array(
			'La página contiene un Query Loop.',
			'Se muestran entradas recientes.',
			'Cada elemento incluye título y extracto.',
			'La sección tiene un encabezado claro.',
		),
	),
	array(
		'slug'           => 'estilos-globales',
		'module'         => '08 · Estilos globales',
		'title'          => 'Ajusta color y tipografía con Estilos globales',
		'source'         => 'https://learn.wordpress.org/lesson/styling-your-site-with-global-styles/',
		'duration'       => '40 min',
		'surface'        => 'Editor del sitio',
		'difficulty'     => 'Intermedio',
		'story'          => 'El contenido ya existe. Ahora vas a cambiar la atmósfera visual desde un solo lugar usando Estilos globales.',
		'objective'      => 'Modificar paleta, tipografía o espaciado global y comprobar el efecto en varias páginas.',
		'primary_action' => 'Abrir Estilos globales',
		'editor_path'    => 'site-editor.php',
		'work_type'      => 'page',
		'work_title'     => 'Nota: decisiones de estilo global',
		'work_slug'      => 'nota-estilos-globales',
		'work_brief'     => 'Registra qué decisiones visuales cambiaste desde Estilos globales.',
		'work_content'   => <<<'HTML'
<!-- wp:heading -->
<h2 class="wp-block-heading">Decisiones visuales</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul><li>Color principal elegido:</li><li>Cambio de tipografía:</li><li>Elemento que mejoró con el ajuste:</li></ul>
<!-- /wp:list -->
HTML,
		'steps'          => array(
			'Abre el Editor del sitio y entra en Estilos.',
			'Cambia una decisión global de color, tipografía o disposición.',
			'Revisa cómo afecta a una página guía y a un borrador de trabajo.',
			'Guarda los cambios globales solo cuando el resultado sea legible.',
			'Completa la nota de trabajo con las decisiones tomadas.',
		),
		'checklist'      => array(
			'Se modificó al menos una decisión global.',
			'El contraste sigue siendo legible.',
			'El cambio se ve en más de una página.',
			'La nota de trabajo documenta la decisión.',
		),
	),
);

$hub_data = array(
	'post_title'   => 'Laboratorio de creación con bloques',
	'post_name'    => 'creator-lab',
	'post_status'  => 'publish',
	'post_type'    => 'page',
	'post_author'  => 1,
	'post_excerpt' => 'Ocho ejercicios guiados para practicar estructura, bloques, patrones, plantillas y estilos globales.',
	'post_content' => '',
	'menu_order'   => 0,
);

$hub_id  = creator_lab_upsert_post( $hub_data );
$records = array();

foreach ( $exercises as $index => $exercise ) {
	$work_id = creator_lab_upsert_post(
		array(
			'post_title'   => $exercise['work_title'],
			'post_name'    => $exercise['work_slug'],
			'post_status'  => 'draft',
			'post_type'    => $exercise['work_type'],
			'post_author'  => 1,
				'post_excerpt' => '',
			'post_content' => creator_lab_work_note( $exercise['work_title'], $exercise['work_brief'], $exercise['work_content'] ),
			'menu_order'   => $index + 1,
		)
	);

	if ( 'post' === $exercise['work_type'] && $work_id ) {
		wp_set_post_categories( $work_id, array(), false );
		wp_set_post_terms( $work_id, array(), 'post_tag', false );
	}

	$guide_id = creator_lab_upsert_post(
		array(
			'post_title'   => $exercise['title'],
			'post_name'    => 'ejercicio-' . $exercise['slug'],
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_author'  => 1,
				'post_excerpt' => $exercise['objective'],
				'post_content' => creator_lab_guide_content( $exercise, $work_id ),
				'post_parent'  => $hub_id,
				'menu_order'   => $index + 1,
		)
	);

	$records[ $exercise['slug'] ] = array(
		'exercise' => $exercise,
		'guide_id' => $guide_id,
		'work_id'  => $work_id,
	);
}

$hub_data['post_content'] = creator_lab_hub_content( $records );
$hub_id                   = creator_lab_upsert_post( $hub_data );

$exercise_ids = array();
foreach ( $records as $slug => $record ) {
	$exercise_ids[ $slug ] = array(
		'guide_id' => (int) $record['guide_id'],
		'work_id'  => (int) $record['work_id'],
	);
}

update_option( 'creator_lab_exercise_ids', $exercise_ids );
update_option( 'creator_lab_entry_page_id', (int) $hub_id );
update_option( 'creator_lab_challenge_post_id', (int) reset( $exercise_ids )['work_id'] );
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', (int) $hub_id );
update_option( 'page_for_posts', 0 );

flush_rewrite_rules();
