<?php

class Brizy_Editor_Editor_Editor {
	/**
	 * @var Brizy_Editor_Post
	 */
	private $post;

	/**
	 * @var Brizy_Editor_Project
	 */
	private $project;

	public static function get( Brizy_Editor_Project $project, Brizy_Editor_Post $post ) {
		return new self( $project, $post );
	}

	public function __construct( Brizy_Editor_Project $project, Brizy_Editor_Post $post ) {
		$this->post    = $post;
		$this->project = $project;
	}

	public function load() {
		$this->register_static();
		add_action( 'wp_enqueue_scripts', array( $this, '_action_register_static' ) );
	}

	protected function get_post() {
		return $this->post;
	}

	protected function register_static() {

		$config = $this->config();
		$url    = $config['urls']['primary'];

		wp_register_style(
			brizy()->get_slug() . '-wireframes',
			$url . '/visual/wireframes.css',
			array()
		);
		wp_register_style(
			brizy()->get_slug() . '-main',
			$url . '/assets/css/main.css',
			array()
		);

		wp_register_style(
			brizy()->get_slug() . '-editor',
			$url . '/visual/editor.css',
			array(
				brizy()->get_slug() . '-wireframes',
				brizy()->get_slug() . '-main'
			)
		);

		wp_register_script(
			brizy()->get_slug() . '-typekit',
			'//use.typekit.net/ueo0lzq.js',
			array(),
			false,
			true
		);
		wp_register_script(
			brizy()->get_slug() . '-jquery',
			$this->static_url() . '/jquery.js',
			array( 'jquery' ),
			brizy()->get_version(),
			true
		);

		wp_register_script(
			brizy()->get_slug() . '-react',
			'https://cdnjs.cloudflare.com/ajax/libs/react/0.12.2/react-with-addons.js',
			array( brizy()->get_slug() . '-jquery', 'underscore' ),
			'0.12.2',
			true
		);
		wp_register_script(
			brizy()->get_slug() . '-wireframes-editor',
			$this->static_url() . '/visual/wireframes.editor.js',
			array(),
			false,
			true
		);
		wp_register_script(
			brizy()->get_slug() . '-editor-vendor',
			$url . '/visual/editor.vendor.js',
			array(),
			false,
			true
		);
		wp_register_script(
			brizy()->get_slug() . '-shortcodes-config',
			$url . '/assets/js/shortcodes-config.js',
			array(),
			false,
			true
		);
		wp_register_script(
			brizy()->get_slug() . '-editor',
			$url . '/visual/editor.dev.js',
			array(
				brizy()->get_slug() . '-typekit',
				brizy()->get_slug() . '-react',
				//brizy()->get_slug() . '-wireframes-editor',
				brizy()->get_slug() . '-editor-vendor',
				brizy()->get_slug() . '-shortcodes-config',
				'media-upload'
			),
			false,
			true
		);

		wp_localize_script(
			brizy()->get_slug() . '-editor-vendor',
			'__VISUAL_CONFIG__',
			$this->config()
		);
		wp_localize_script(
			brizy()->get_slug() . '-editor-vendor',
			'__SHORTCODES_CONFIG__',
			array()
		);
	}

	public function config() {
		return array(
			'env'             => 'WP',
			'rootElement'     => '#' . brizy()->get_slug() . '-root-element',
			'editorOptions'   => array(
				'isLegacy'    => false,
				'isMultipage' => false,
				'isVariant'   => false,
			),
			'hosts'           => array(
				'api'     => 'api.testblox.info',
				'base'    => 'www.testblox.info',
				'origin'  => 'testblox.info',
				'primary' => 'testblox.info',
			),
			'project'         => $this->project->get_id(),
			'projectLanguage' => array(
				'id'      => 7,
				'variant' => array(
					'id'   => 7,
					'name' => 'A',
				),
			),
			'serverTimestamp' => time(),
			'urls'            => array(
				'api'         => home_url( '/wp-json/v1' ),
				'base'        => 'http://www.testblox.info',
				'image'       => 'http://static.bitblox.xyz/storage/media',
				'integration' => 'http://integration.bitblox.site',
				'origin'      => 'http://testblox.info',
				'primary'     => 'http://bitblox.dev',
			),
			'user'            => $this->project->get_id(),
			'versions'        => array(
				'editor'   => '4.3.0',
				'template' => null
			),
			'wp'              => array(
				'page'       => $this->post->get_id(),
				'templates'  => $this->post->get_templates(),
				'api'        => array(
					'hash'         => wp_create_nonce( Brizy_Editor_API::nonce ),
					'url'          => admin_url( 'admin-ajax.php' ),
					'globals'      => array(
						'set' => Brizy_Editor_API::AJAX_SET_GLOBALS,
						'get' => Brizy_Editor_API::AJAX_GET_GLOBALS,
					),
					'media'        => Brizy_Editor_API::AJAX_MEDIA,
					'ping'         => Brizy_Editor_API::AJAX_PING,
					'getPage'      => Brizy_Editor_API::AJAX_GET,
					'updatePage'   => Brizy_Editor_API::AJAX_UPDATE,
					'getSidebars'  => Brizy_Editor_API::AJAX_SIDEBARS,
					'buildContent' => Brizy_Editor_API::AJAX_BUILD,
				),
				'shortcodes' => array(
					'sidebar' => BRIZY_SHORTCODES_PREFIX . 'sidebar'
				)
			)
		);
	}

	public function static_url() {
		return brizy()->get_url( '/editor/editor/static' );
	}

	/**
	 * @internal
	 **/
	public function _action_register_static() {
		wp_enqueue_media();
		wp_enqueue_style( brizy()->get_slug() . '-editor' );
		wp_enqueue_script( brizy()->get_slug() . '-editor' );
	}
}