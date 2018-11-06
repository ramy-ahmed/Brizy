<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 11/26/18
 * Time: 5:00 PM
 */

class Brizy_Editor_Forms_Account extends Brizy_Admin_Serializable {

	use Brizy_Editor_Forms_DynamicPropsAware;

	/**
	 * Brizy_Editor_Forms_Account constructor.
	 *
	 * @param null $data
	 */
	public function __construct( $data = null ) {
		if ( ! is_array( $data ) ) {
			$this->data = array( 'id' => md5( time() . rand( 0, 10000 ) ) );
		} else {
			$this->data = $data;
		}
	}

	public function isEqual( self $account ) {
		$aData = $account->convertToOptionValue();
		foreach ( $this->data as $key => $val ) {
			if($key=='id') continue;
			if ( $aData[ $key ] != $val ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param string[] $data
	 *
	 * @return Brizy_Editor_Forms_Account
	 */
	public function setData( $data ) {
		$id               = $this->data['id'];
		$this->data       = $data;
		$this->data['id'] = $id;

		return $this;
	}

	/**
	 * @return string
	 */
	public function serialize() {
		return serialize( $this->jsonSerialize() );
	}

	public function unserialize( $serialized ) {
		$this->data = unserialize( $serialized );
	}

	public function jsonSerialize() {
		return $this->data;
	}

	public function convertToOptionValue() {
		return $this->data;
	}

	public function convertToAuthData() {

		$data = $this->data;

		unset( $data['id'] );

		return $data;
	}

	static public function createFromSerializedData( $data ) {
		$instance = new self();

		foreach ( $data as $key => $val ) {
			$instance->set( $key, $val );
		}

		return $instance;
	}

	/**
	 * @return Brizy_Editor_Forms_Form
	 * @throws Exception
	 */
	public static function createFromJson( $json_obj ) {

		if ( ! isset( $json_obj ) ) {
			throw new Exception( 'Bad Request', 400 );
		}

		if ( is_object( $json_obj ) ) {
			return self::createFromSerializedData( get_object_vars( $json_obj ) );
		}

		return new self();
	}
}