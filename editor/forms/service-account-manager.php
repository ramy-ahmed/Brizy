<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 5/23/18
 * Time: 10:59 AM
 */

class Brizy_Editor_Forms_ServiceAccountManager {

	/**
	 * @var Brizy_Editor_Project
	 */
	private $project;

	/**
	 * @var Brizy_Editor_Forms_Account[]
	 */
	private $accounts;

	/**
	 * Brizy_Editor_Forms_Manager constructor.
	 *
	 * @param Brizy_Editor_Project $project
	 */
	public function __construct( Brizy_Editor_Project $project ) {
		$this->project = $project;
		try {
			$this->loadAccounts( $project );
		} catch ( Exception $exception ) {
			$this->accounts = array();
		}
	}

	/**
	 * @param $service
	 * @param $accountId
	 *
	 * @return Brizy_Editor_Forms_Account|null
	 */
	public function getAccounts( $service ) {
		return $this->accounts[ $service ];
	}

	/**
	 * @param $service
	 * @param $accountId
	 *
	 * @return Brizy_Editor_Forms_Account|null
	 */
	public function getAccount( $service, $accountId ) {
		return $this->accounts[ $service ][ $accountId ];
	}

	/**
	 * @param $service
	 * @param Brizy_Editor_Forms_Account $account
	 */
	public function addAccount( $service, Brizy_Editor_Forms_Account $account ) {
		$this->accounts[ $service ][ $account->getId() ] = $account;

		$this->updateStorage();
	}

	/**
	 * @param $service
	 * @param Brizy_Editor_Forms_Account $account
	 */
	public function deleteAccount( $service, Brizy_Editor_Forms_Account $account ) {
		unset( $this->accounts[ $service ][ $account->getId() ] );

		$this->updateStorage();
	}

	/**
	 * @param $service
	 * @param $accountId
	 */
	public function deleteAccountById( $service, $accountId ) {
		unset( $this->accounts[ $service ][ $accountId ] );

		$this->updateStorage();
	}

	/**
	 *
	 */
	private function updateStorage() {
		$this->project->setMetaValue( 'accounts', $this->accounts );
	}

	/**
	 * @param Brizy_Editor_Project $project
	 *
	 * @throws Brizy_Editor_Exceptions_NotFound
	 */
	private function loadAccounts( Brizy_Editor_Project $project ) {
		foreach ( (array)$project->getMetaValue( 'accounts' ) as $service => $account ) {
			$this->addAccount( $service, Brizy_Editor_Forms_Account::createFromSerializedData( $account ) );
		}
	}
}