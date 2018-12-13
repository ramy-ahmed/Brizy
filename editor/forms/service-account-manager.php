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

	public function getAllAccounts() {
		return $this->accounts;
	}

	/**
	 * @param $service
	 * @param $accountId
	 *
	 * @return Brizy_Editor_Forms_Account[]|null
	 */
	public function getAccounts( $service ) {

		if ( isset($this->accounts[ $service ]) ) {
			return array_values( $this->accounts[ $service ] );
		}

		return array();
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
	 * @param Brizy_Editor_Forms_Account $anAccount
	 *
	 * @return bool
	 */
	public function hasAccount( $service, Brizy_Editor_Forms_Account $anAccount ) {
		foreach ( $this->getAccounts( $service ) as $account ) {
			if ( $anAccount->isEqual( $account ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param $service
	 * @param Brizy_Editor_Forms_Account $account
	 */
	public function addAccount( $service, Brizy_Editor_Forms_Account $account ) {

		if ( $this->hasAccount( $service, $account ) ) {
			return;
		}

		$this->accounts[ $service ][ $account->getId() ] = $account;


		$this->updateStorage();
	}

	/**
	 * @param $service
	 * @param Brizy_Editor_Forms_Account $account
	 */
	public function deleteAccount( $service, Brizy_Editor_Forms_Account $account ) {

		throw new Exception('YOU TRIED TODELETE ACCOUNT :) :)');

		unset( $this->accounts[ $service ][ $account->getId() ] );

		$this->updateStorage();
	}

	/**
	 * @param $service
	 * @param $accountId
	 */
	public function deleteAccountById( $service, $accountId ) {

		throw new Exception('YOU TRIED TODELETE ACCOUNT :) :)');

		unset( $this->accounts[ $service ][ $accountId ] );

		$this->updateStorage();
	}

	/**
	 *
	 */
	private function updateStorage() {

		$data = array();

		foreach ( $this->accounts as $service => $accounts ) {
			foreach ( $accounts as $account ) {
				$data[ $service ][] = $account->convertToOptionValue();
			}
		}

		$this->project->setMetaValue( 'accounts', $data );
	}

	/**
	 * @param Brizy_Editor_Project $project
	 *
	 * @throws Brizy_Editor_Exceptions_NotFound
	 */
	private function loadAccounts( Brizy_Editor_Project $project ) {

		//$this->project->setMetaValue( 'accounts', [] );
		$meta_value = $project->getMetaValue( 'accounts' );

		if ( is_array( $meta_value ) ) {
			foreach ( $meta_value as $service => $accounts ) {
				foreach ( $accounts as $account ) {
					$account1 = Brizy_Editor_Forms_Account::createFromSerializedData( $account );
					$this->addAccount( $service, $account1 );
				}
			}
		}
	}
}