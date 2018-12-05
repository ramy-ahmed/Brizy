<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 11/19/18
 * Time: 4:56 PM
 */

class Brizy_Editor_Forms_Api {

	const AJAX_GET_FORM = 'brizy_get_form';
	const AJAX_CREATE_FORM = 'brizy_create_form';
	const AJAX_DELETE_FORM = 'brizy_delete_form';
	const AJAX_SUBMIT_FORM = 'brizy_submit_form';

	const AJAX_GET_SERVICE_ACCOUNTS = 'brizy_service_accounts';
	const AJAX_DELETE_SERVICE_ACCOUNT = 'brizy_delete_service_account';

	const AJAX_GET_INTEGRATION = 'brizy_get_integration';
	const AJAX_CREATE_INTEGRATION = 'brizy_create_integration';
	const AJAX_UPDATE_INTEGRATION = 'brizy_update_integration';
	const AJAX_DELETE_INTEGRATION = 'brizy_delete_integration';

	const AJAX_GET_LISTS = 'brizy_get_lists';
	const AJAX_GET_FIELDS = 'brizy_get_fields';

	const AJAX_AUTHENTICATE_INTEGRATION = 'brizy_authenticate_integration';
	const AJAX_AUTHENTICATION_CALLBACK = 'brizy_authentication_callback';

	/**
	 * @var Brizy_Editor_Project
	 */
	private $project;

	/**
	 * @var Brizy_Editor_Post
	 */
	private $post;

	/**
	 * @return Brizy_Editor_Project
	 */
	public function get_project() {
		return $this->project;
	}

	/**
	 * @return Brizy_Editor_Post
	 */
	public function get_post() {
		return $this->post;
	}

	/**
	 * Brizy_Editor_API constructor.
	 *
	 * @param Brizy_Editor_Project $project
	 * @param Brizy_Editor_Post $post
	 */
	public function __construct( $project, $post ) {

		$this->project = $project;
		$this->post    = $post;

		$this->initialize();
	}

	private function initialize() {

		if ( Brizy_Editor::is_user_allowed() ) {
			add_action( 'wp_ajax_' . self::AJAX_GET_FORM, array( $this, 'get_form' ) );
			add_action( 'wp_ajax_' . self::AJAX_CREATE_FORM, array( $this, 'create_form' ) );
			add_action( 'wp_ajax_' . self::AJAX_DELETE_FORM, array( $this, 'delete_form' ) );

			add_action( 'wp_ajax_' . self::AJAX_GET_SERVICE_ACCOUNTS, array( $this, 'getServiceAccountList' ) );
			add_action( 'wp_ajax_' . self::AJAX_DELETE_SERVICE_ACCOUNT, array( $this, 'deleteServiceAccountList' ) );


			add_action( 'wp_ajax_' . self::AJAX_CREATE_INTEGRATION, array( $this, 'createIntegration' ) );
			add_action( 'wp_ajax_' . self::AJAX_GET_INTEGRATION, array( $this, 'getIntegration' ) );
			add_action( 'wp_ajax_' . self::AJAX_UPDATE_INTEGRATION, array( $this, 'updateIntegration' ) );
			add_action( 'wp_ajax_' . self::AJAX_DELETE_INTEGRATION, array( $this, 'deleteIntegration' ) );
			add_action( 'wp_ajax_' . self::AJAX_AUTHENTICATE_INTEGRATION, array( $this, 'authenticateIntegration' ) );

			add_action( 'wp_ajax_' . self::AJAX_GET_LISTS, array( $this, 'getIntegrationLists' ) );
			add_action( 'wp_ajax_' . self::AJAX_GET_FIELDS, array( $this, 'getIntegrationFields' ) );
		}

		add_action( 'wp_ajax_' . self::AJAX_SUBMIT_FORM, array( $this, 'submit_form' ) );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_SUBMIT_FORM, array( $this, 'submit_form' ) );
		//add_action( 'wp_ajax_nopriv_' . self::AJAX_AUTHENTICATION_CALLBACK, array( $this, 'authenticationCallback' ) );
	}

	protected function error( $code, $message ) {
		wp_send_json_error( array( 'code' => $code, 'message' => $message ), $code );
	}

	protected function success( $data ) {
		wp_send_json_success( $data );
	}

	private function authorize() {
		if ( ! wp_verify_nonce( $_REQUEST['hash'], Brizy_Editor_API::nonce ) ) {
			wp_send_json_error( array( 'code' => 400, 'message' => 'Bad request' ), 400 );
		}
	}

	public function get_form() {
		try {
			$this->authorize();

			$manager = new Brizy_Editor_Forms_FormManager( Brizy_Editor_Project::get() );

			$form = $manager->getForm( $_REQUEST['formId'] );

			if ( $form ) {
				$this->success( $form );
			}

			$this->error( 404, 'Form not found' );

		} catch ( Exception $exception ) {
			Brizy_Logger::instance()->exception( $exception );
			$this->error( $exception->getCode(), $exception->getMessage() );
			exit;
		}
	}

	public function create_form() {
		try {
			$this->authorize();

			$manager           = new Brizy_Editor_Forms_FormManager( Brizy_Editor_Project::get() );
			$instance          = Brizy_Editor_Forms_Form::createFromJson( json_decode( file_get_contents( 'php://input' ) ) );
			$validation_result = $instance->validate();

			if ( $validation_result === true ) {
				$manager->addForm( $instance );
				$this->success( $instance );
			}

			$this->error( 400, $validation_result );

		} catch ( Exception $exception ) {
			Brizy_Logger::instance()->exception( $exception );
			$this->error( $exception->getCode(), $exception->getMessage() );
			exit;
		}
	}

	public function delete_form() {
		try {
			$this->authorize();
			$manager = new Brizy_Editor_Forms_FormManager( Brizy_Editor_Project::get() );
			$manager->deleteFormById( $_REQUEST['formId'] );
			$this->success( array() );
		} catch ( Exception $exception ) {
			Brizy_Logger::instance()->exception( $exception );
			$this->error( $exception->getCode(), $exception->getMessage() );
			exit;
		}
	}

	public function submit_form() {
		try {
			$manager        = new Brizy_Editor_Forms_FormManager( Brizy_Editor_Project::get() );
			$accountManager = new Brizy_Editor_Forms_ServiceAccountManager( Brizy_Editor_Project::get() );
			/**
			 * @var Brizy_Editor_FormsCompatibility fix_Form $form ;
			 */

			$form = $manager->getForm( $_REQUEST['form_id'] );

			if ( ! $form ) {
				$this->error( 400, "Invalid form id" );
			}

			$fields = json_decode( stripslashes( $_REQUEST['data'] ) );

			if ( ! $fields ) {
				$this->error( 400, "Invalid form data" );
			}


			$form   = apply_filters( 'brizy_form', $form );
			$fields = apply_filters( 'brizy_form_submit_data', $fields, $form );


			foreach ( $form->getIntegrations() as $integration ) {
				if ( ! $integration->isCompleted() ) {
					continue;
				}

				try {
					/**
					 * @var \BrizyForms\Service\Service $service ;
					 */
					$service = \BrizyForms\ServiceFactory::getInstance( $integration->getId() );

					if ( ! ( $service instanceof \BrizyForms\Service\Service ) ) {
						$this->error( 400, "Invalid integration service" );
					}

					if ( $integration instanceof Brizy_Editor_Forms_WordpressIntegration ) {

						$headers   = array();
						$headers[] = 'Content-type: text/html; charset=UTF-8';

						$field_string = array();
						foreach ( $fields as $field ) {
							$field_string[] = "{$field->label}: " . esc_html( $field->value );
						}

						$email_body = implode( '<br>', $field_string );

						$headers    = apply_filters( 'brizy_form_email_headers', $headers, $form, $fields );
						$email_body = apply_filters( 'brizy_form_email_body', $email_body, $form, $fields );

						$result = wp_mail(
							$form->getEmailTo(),
							$form->getSubject(),
							$email_body,
							$headers
						);

					} else {
						// initialize an instance of AuthenticationData
						$account = $accountManager->getAccount( $integration->getId(), $integration->getUsedAccount() );

						$authData = new \BrizyForms\Model\AuthenticationData( $account->convertToOptionValue() );
						$service->setAuthenticationData( $authData );

						$fieldMap = new \BrizyForms\FieldMap( array_map( function ( $obj ) {
							return get_object_vars( $obj );
						}, $integration->getFieldsMap() ) );

						$data = array_map( function ( $obj ) {
							return new \BrizyForms\Model\Data( $obj->name, $obj->value );
						}, $fields );

						$service->createMember( $fieldMap, $integration->getUsedList(), $data );
					}
				} catch ( Exception $e ) {
					$this->error( 500, "Unable to create integration member." );
				}
			}

			$this->success( 200 );

		} catch ( Exception $exception ) {
			Brizy_Logger::instance()->exception( $exception );
			$this->error( $exception->getCode(), $exception->getMessage() );
			exit;
		}
	}

	public function createIntegration() {
		$this->authorize();
		$manager = new Brizy_Editor_Forms_FormManager( Brizy_Editor_Project::get() );
		$form    = $manager->getForm( $_REQUEST['formId'] );

		if ( ! $form ) {
			$this->error( 400, "Invalid form id" );
		}

		$integration = Brizy_Editor_Forms_AbstractIntegration::createInstanceFromJson( json_decode( file_get_contents( 'php://input' ) ) );

		if ( $form->getIntegration( $integration->getid() ) ) {
			$this->error( 400, "This integration is already created" );
		}

		if ( $form->addIntegration( $integration ) ) {
			$manager->addForm( $form );
			$this->success( $integration );
		}

		$this->error( 500, "Unable to create integration" );
	}

	public function getIntegration() {

		$this->authorize();

		$manager        = new Brizy_Editor_Forms_FormManager( Brizy_Editor_Project::get() );
		$accountManager = new Brizy_Editor_Forms_ServiceAccountManager( Brizy_Editor_Project::get() );

		$form = $manager->getForm( $_REQUEST['formId'] );
		if ( ! $form ) {
			$this->error( 400, "Invalid form id" );
		}
		$integrationId = $_REQUEST['integration'];
		if ( ! $integrationId ) {
			$this->error( 400, "Invalid form integration" );
		}

		$integration = $form->getIntegration( $integrationId );

		if ( $integration instanceof Brizy_Editor_Forms_ServiceIntegration ) {
			$integration->getAccounts( $accountManager->getAccounts( $integrationId ) );
		}

		if ( $integration ) {
			$this->success( $integration );
		}

		$this->error( 404, 'Integration not found' );
	}

	public function updateIntegration() {

		$this->authorize();

		$manager = new Brizy_Editor_Forms_FormManager( Brizy_Editor_Project::get() );
		$form    = $manager->getForm( $_REQUEST['formId'] );
		if ( ! $form ) {
			$this->error( 400, "Invalid form id" );
		}

		$integration = Brizy_Editor_Forms_AbstractIntegration::createInstanceFromJson( json_decode( file_get_contents( 'php://input' ) ) );

		if ( $integration instanceof Brizy_Editor_Forms_ServiceIntegration ) {
			// detect integration changes and reset the values when account or lists is changed
			$oldIntegration = $form->getIntegration( $integration->getid() );

			if ( ! $oldIntegration ) {
				$this->error( 404, "Integration not found" );
			}

			// reset fields and lists if the account is changed
			if ( $oldIntegration->getUsedAccount() != $integration->getUsedAccount() ) {
				$integration->setLists( array() );
				$integration->setFields( array() );
				$integration->setUsedList( null );
				$integration->setFieldsMap( array() );
				$integration->setCompleted( false );
			}

			// reset fields and fieldsmap if the used list is changed
			if ( $oldIntegration->getUsedList() != $integration->getUsedList() ) {
				$integration->setFields( array() );
				$integration->setFieldsMap( array() );
				$integration->setCompleted( false );
			}
		}

		//------------------

		if ( $form->updateIntegration( $integration ) ) {
			$manager->addForm( $form );
			$this->success( $integration );
		}

		$this->error( 404, 'Integration not found' );
	}

	public function deleteIntegration() {

		$this->authorize();

		$manager = new Brizy_Editor_Forms_FormManager( Brizy_Editor_Project::get() );
		$form    = $manager->getForm( $_REQUEST['formId'] );
		if ( ! $form ) {
			$this->error( 400, "Invalid form id" );
		}

		$integrationId = $_REQUEST['integration'];
		if ( ! $integrationId ) {
			$this->error( 400, "Invalid form integration" );
		}

		$deleted = $form->deleteIntegration( $integrationId );

		if ( $deleted ) {
			$manager->addForm( $form );
			$this->success( null );
		}

		$this->error( 404, 'Integration not found' );
	}

	public function authenticateIntegration() {

		$manager        = new Brizy_Editor_Forms_FormManager( Brizy_Editor_Project::get() );
		$accountManager = new Brizy_Editor_Forms_ServiceAccountManager( Brizy_Editor_Project::get() );

		$form = $manager->getForm( $_REQUEST['formId'] );
		if ( ! $form ) {
			$this->error( 404, "Form not found" );
		}

		$integrationId = $_REQUEST['integration'];

		if ( ! $integrationId ) {
			$this->error( 400, "Invalid form integration" );
		}

		$integration = $form->getIntegration( $integrationId );

		if ( ! $integration ) {
			$this->error( 404, "Integration not found" );
		}

		if ( $integration instanceof Brizy_Editor_Forms_WordpressIntegration ) {
			$this->error( 400, "Unsupported integration" );
		}

		/**
		 * @var \BrizyForms\Service\Service $service ;
		 */
		$service = \BrizyForms\ServiceFactory::getInstance( $integration->getId() );

		if ( ! ( $service instanceof \BrizyForms\Service\Service ) ) {
			$this->error( 400, "Invalid integration service" );
		}

		$data = json_decode( file_get_contents( 'php://input' ) );

		$account = new Brizy_Editor_Forms_Account();
		$account->setData( get_object_vars( $data ) );

		if ( $accountManager->getAccount( $integration->getId(), $account ) ) {
			$this->error( 400, "Duplicate account" );
		}

		$accountManager->addAccount( $integration->getId(), $account );

		try {
			// initialize an instance of AuthenticationData
			$authData = new \BrizyForms\Model\AuthenticationData( $account->convertToAuthData() );
			$service->setAuthenticationData( $authData );

		} catch ( Exception $e ) {
			$this->error( 401, "Invalid account" );
		}

		$response = $service->authenticate();

		if ( $response instanceof \BrizyForms\Model\Response ) {

			if ( $response->getCode() == 200 ) {
				if ( $form->updateIntegration( $integration ) ) {
					$manager->addForm( $form );
					$this->success( $account );
				}
			} else {
				$this->error( 401, $response->getMessage() );
			}
		}

		$this->error( 500, 'Failed to authenticate service' );
	}

	public function getIntegrationLists() {

		$this->authorize();

		$manager        = new Brizy_Editor_Forms_FormManager( Brizy_Editor_Project::get() );
		$accountManager = new Brizy_Editor_Forms_ServiceAccountManager( Brizy_Editor_Project::get() );
		$form           = $manager->getForm( $_REQUEST['formId'] );
		if ( ! $form ) {
			$this->error( 400, "Invalid form id" );
		}
		$integrationId = $_REQUEST['integration'];
		if ( ! $integrationId ) {
			$this->error( 400, "Invalid form integration" );
		}

		$integration = $form->getIntegration( $integrationId );

		if ( $integration instanceof Brizy_Editor_Forms_WordpressIntegration ) {
			$this->error( 400, "Unsupported integration" );
		}

		if ( $integration instanceof Brizy_Editor_Forms_WordpressIntegration ) {
			$this->error( 400, "Unsupported integration" );
		}

		if ( ! $integration ) {
			$this->error( 400, "Invalid form integration" );
		}

		if ( ! $integration->getUsedAccount() ) {
			$this->error( 400, "Invalid integration account" );
		}

		try {
			/**
			 * @var \BrizyForms\Service\Service $service ;
			 */
			$service = \BrizyForms\ServiceFactory::getInstance( $integration->getId() );

			if ( ! ( $service instanceof \BrizyForms\Service\Service ) ) {
				$this->error( 400, "Invalid integration service" );
			}

			// initialize an instance of AuthenticationData
			$account = $accountManager->getAccount( $integration->getId(), $integration->getUsedAccount() );

			$authData = new \BrizyForms\Model\AuthenticationData( $account->convertToAuthData() );

			$service->setAuthenticationData( $authData );

			$groups = $service->getGroups();
			$integration->setFields( array() );
			foreach ( $groups as $group ) {
				$integration->addList( new Brizy_Editor_Forms_Group( $group ) );
			}
			// save groups in integration
			$form->updateIntegration( $integration );
			$manager->addForm( $form );

			if ( count( $groups ) ) {
				$this->success( $groups );
			} else {
				$this->error( 404, "No lists created" );
			}

		} catch ( Exception $e ) {
			$this->error( 500, "Unable to initialize service" );
		}
	}

	public function getIntegrationFields() {

		$this->authorize();

		$manager        = new Brizy_Editor_Forms_FormManager( Brizy_Editor_Project::get() );
		$accountManager = new Brizy_Editor_Forms_ServiceAccountManager( Brizy_Editor_Project::get() );
		$form           = $manager->getForm( $_REQUEST['formId'] );
		if ( ! $form ) {
			$this->error( 400, "Invalid form id" );
		}
		$integrationId = $_REQUEST['integration'];
		if ( ! $integrationId ) {
			$this->error( 400, "Invalid form integration" );
		}

		$integration = $form->getIntegration( $integrationId );

		if ( ! $integration ) {
			$this->error( 400, "Invalid form integration" );
		}

		if ( $integration instanceof Brizy_Editor_Forms_WordpressIntegration ) {
			$this->error( 400, "Unsupported integration" );
		}

		if ( ! $integration->getUsedAccount() ) {
			$this->error( 400, "Invalid integration account" );
		}

		try {
			/**
			 * @var \BrizyForms\Service\Service $service ;
			 */
			$service = \BrizyForms\ServiceFactory::getInstance( $integration->getId() );

			if ( ! ( $service instanceof \BrizyForms\Service\Service ) ) {
				$this->error( 400, "Invalid integration service" );
			}

			// initialize an instance of AuthenticationData
			$account = $accountManager->getAccount( $integration->getId(), $integration->getusedAccount() );

			$authData = new \BrizyForms\Model\AuthenticationData( $account->convertToOptionValue() );
			$service->setAuthenticationData( $authData );


			$list = new \BrizyForms\Model\Group();
			if ( $integration->getUsedList() ) {
				$userlist = $integration->getUsedListObject();
				$list     = new \BrizyForms\Model\Group( $userlist->getId(), $userlist->getName() );
			}

			$fields = $service->getFields( $list );

			$integration->setFields( array() );
			foreach ( $fields as $field ) {
				$integration->addField( new Brizy_Editor_Forms_Field( $field ) );
			}

			// save groups in integration
			$form->updateIntegration( $integration );
			$manager->addForm( $form );

			if ( count( $fields ) ) {
				$this->success( $fields );
			} else {
				$this->error( 404, "No lists created" );
			}
		} catch ( Exception $e ) {
			$this->error( 500, "Unable to initialize service" );
		}
	}

	public function getServiceAccountList() {

		$this->authorize();

		$manager = new Brizy_Editor_Forms_ServiceAccountManager( Brizy_Editor_Project::get() );

		$serviceId = $_REQUEST['service'];
		if ( ! $serviceId ) {
			$this->error( 400, "Invalid form service id" );
		}

		$accounts = $manager->getAccounts( $serviceId );

		$this->success( $accounts );
	}

	public function deleteServiceAccountList() {

		$this->authorize();

		$manager = new Brizy_Editor_Forms_ServiceAccountManager( Brizy_Editor_Project::get() );

		$serviceId = $_REQUEST['service'];
		if ( ! $serviceId ) {
			$this->error( 400, "Invalid form service id" );
		}

		$accountId = $_REQUEST['account'];
		if ( ! $accountId ) {
			$this->error( 400, "Invalid account id" );
		}

		$manager->deleteAccountById( $serviceId, $accountId );

		$this->success( null );
	}

}