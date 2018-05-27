<?php

abstract class WPML_TP_REST_Object {

	public function __construct( stdClass $object = null ) {
		if ( $object ) {
			$properties = $this->get_properties();

			foreach ( $properties as $object_property => $new_property ) {
				if ( isset( $object->{$object_property} ) ) {
					$this->{"set_$new_property"}( $object->{$object_property} );
				}
			}
		}
	}

	abstract protected function get_properties();
}
