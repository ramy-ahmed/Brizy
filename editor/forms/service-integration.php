<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 11/20/18
 * Time: 4:48 PM
 */

class Brizy_Editor_Forms_ServiceIntegration extends Brizy_Editor_Forms_AbstractIntegration {


	/**
	 * @var array
	 */
	protected $accounts = array();

	/**
	 * @var array
	 */
	protected $fields = array();

	/**
	 * @var array
	 */
	protected $lists = array();


	/**
	 * @var
	 */
	protected $usedAccount;

	/**
	 * @var
	 */
	protected $fieldsMap;

	/**
	 * @var
	 */
	protected $usedList;


	/**
	 * @return array|mixed
	 */
	public function jsonSerialize() {

		$get_object_vars = parent::jsonSerialize();

		$get_object_vars['fields']      = $this->getFields();
		$get_object_vars['lists']       = $this->getLists();
		$get_object_vars['usedAccount'] = $this->getUsedAccount();
		$get_object_vars['usedList']    = $this->getUsedList();
		$get_object_vars['fieldsMap']   = $this->getFieldsMap();
		$get_object_vars['accounts']    = $this->getAccounts();

		return $get_object_vars;
	}

	/**
	 * @return string
	 */
	public function serialize() {
		$value = $this->jsonSerialize();
		unset( $value['accounts'] );

		return serialize( $value );
	}

	public static function createFromJson( $json_obj ) {
		$instance = null;
		if ( is_object( $json_obj ) ) {
			$instance = new self( $json_obj->id );

			if ( isset( $json_obj->fields ) ) {
				foreach ( $json_obj->fields as $field ) {
					$instance->addField( Brizy_Editor_Forms_Field::createFromJson( $field ) );
				}
			}
			if ( isset( $json_obj->lists ) ) {
				foreach ( $json_obj->lists as $lists ) {
					$instance->addList( Brizy_Editor_Forms_Group::createFromJson( $lists ) );
				}
			}
			if ( isset( $json_obj->usedAccount ) ) {
				$instance->setUsedAccount( $json_obj->usedAccount );
			}
			if ( isset( $json_obj->usedList ) ) {
				$instance->setUsedList( $json_obj->usedList );
			}
			if ( isset( $json_obj->fieldsMap ) ) {
				$instance->setFieldsMap( $json_obj->fieldsMap );
			}
		}

		return $instance;
	}


	public function addList( Brizy_Editor_Forms_Group $list ) {
		$this->lists[] = $list;
	}

	public function addField( Brizy_Editor_Forms_Field $field ) {
		$this->fields[] = $field;
	}


	/**
	 * @return array
	 */
	public function getFields() {
		return $this->fields;
	}

	/**
	 * @param array $fields
	 *
	 * @return self
	 */
	public function setFields( $fields ) {
		$this->fields = $fields;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getLists() {
		return $this->lists;
	}

	/**
	 * @param array $lists
	 *
	 * @return self
	 */
	public function setLists( $lists ) {
		$this->lists = $lists;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getUsedAccount() {
		return $this->usedAccount;
	}

	/**
	 * @param mixed $usedAccount
	 *
	 * @return self
	 */
	public function setUsedAccount( $usedAccount ) {
		$this->usedAccount = $usedAccount;

		return $this;
	}


	/**
	 * @return mixed
	 */
	public function getUsedListObject() {

		foreach ( (array) $this->lists as $list ) {
			$var          = $list->getId();
			$used_account = $this->getUsedList();
			if ( $var == $used_account ) {
				return $list;
			}
		}

		return array();
	}

	/**
	 * @return mixed
	 */
	public function getUsedList() {
		return $this->usedList;
	}

	/**
	 * @param mixed $usedList
	 *
	 * @return self
	 */
	public function setUsedList( $usedList ) {
		$this->usedList = $usedList;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getFieldsMap() {
		return $this->fieldsMap;
	}

	/**
	 * @param mixed $fieldsMap
	 *
	 * @return self
	 */
	public function setFieldsMap( $fieldsMap ) {
		$this->fieldsMap = $fieldsMap;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getAccounts() {
		return $this->accounts;
	}

	/**
	 * @param array $accounts
	 *
	 * @return Brizy_Editor_Forms_ServiceIntegration
	 */
	public function setAccounts( $accounts ) {
		$this->accounts = $accounts;

		return $this;
	}
}