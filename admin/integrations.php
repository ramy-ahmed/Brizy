<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 12/6/18
 * Time: 11:24 AM
 */

class Brizy_Admin_Integrations {

	const INTEGRATIONS = 'integrations';
	const INTEGRATIONS_LABEL = 'Integrations';

	/**
	 * @var Brizy_Editor_Forms_ServiceAccountManager
	 */
	private $accountManager;

	/**
	 * @var Brizy_Editor_Forms_FormManager
	 */
	private $formManager;

	/**
	 * @var Brizy_TwigEngine
	 */
	private $twigEngine;

	/**
	 * @param Brizy_Editor_Project $project
	 *
	 * @return Brizy_Admin_Integrations
	 * @throws Exception
	 */
	public static function _init( Brizy_Editor_Project $project ) {

		static $instance;

		return $instance ? $instance : $instance = new self( $project );
	}

	/**
	 * Brizy_Admin_Integrations constructor.
	 *
	 * @param Brizy_Editor_Project $project
	 *
	 * @throws Exception
	 */
	protected function __construct( Brizy_Editor_Project $project ) {

		if ( Brizy_Editor::is_user_allowed() ) {
			add_action( 'admin_menu', array( $this, 'actionRegisterIntegrationsPage' ) );
			add_action( 'current_screen', array( $this, 'actionDeleteAccounts' ) );
		}

		$this->twigEngine     = Brizy_TwigEngine::instance( BRIZY_PLUGIN_PATH . "/admin/views/integrations" );
		$this->accountManager = new Brizy_Editor_Forms_ServiceAccountManager( $project );
		$this->formManager    = new Brizy_Editor_Forms_FormManager( $project );
	}

	public function actionRegisterIntegrationsPage() {
		add_submenu_page( Brizy_Admin_Settings::menu_slug(), __( 'Integrations' ), __( 'Integrations' ), 'manage_options', self::INTEGRATIONS, array(
			$this,
			'render'
		) );
	}

	public function render() {
		try {
			$params = array(
				'content' => $this->renderContent( false ),
			);

			echo $this->twigEngine->render( 'wrapper.html.twig', $params );

		} catch ( Exception $e ) {

		}
	}

	public function renderContent( $echo = true ) {
		try {
			$params     = array(
				'title'    => __( 'Integrations' ),
				'accounts' => $this->accountManager->getAllAccounts(),
				'pageLink' => menu_page_url( self::INTEGRATIONS, false )
			);
			$twigEngine = Brizy_TwigEngine::instance( BRIZY_PLUGIN_PATH . "/admin/views/integrations" );

			$content = $twigEngine->render( 'view.html.twig', $params );

			if ( $echo ) {
				echo $content;
			}

			return $content;
		} catch ( Exception $e ) {

		}
	}

	public function actionDeleteAccounts() {
		if ( isset( $_REQUEST['account'] ) && count( $_REQUEST['account'] ) > 0 ) {
			// delete accounts
			foreach ( $_REQUEST['account'] as $serviceId => $accounts ) {
				foreach ( $accounts as $accountId ) {
					foreach ( $this->formManager->getAllForms() as $form ) {
						foreach ( $form->getIntegrations() as $integration ) {
							if ( $integration instanceof Brizy_Editor_Forms_ServiceIntegration && $integration->getUsedAccount() == $accountId ) {
								$integration->setUsedAccount( null );
								$this->formManager->addForm( $form );
							}
						}
					}

					$this->accountManager->deleteAccountById( $serviceId, $accountId );
				}
			}

			wp_redirect( menu_page_url( self::INTEGRATIONS, false ) );
		}
	}

}