<?php

/**
 * Class WPML_TP_API_Services
 *
 * @author OnTheGoSystems
 */
class WPML_TP_API_Services extends WPML_TP_Abstract_API {

	/** @return string */
	protected function get_endpoint_uri() {
		return '/services.json';
	}

	/** @return bool */
	protected function is_authenticated() {
		return false;
	}

	/**
	 * @param bool $reload
	 *
	 * @return mixed|void
	 */
	public function get_all( $reload = false ) {
		$translation_services = get_transient( 'wpml_translation_service_list' );

		if ( $reload || empty( $translation_services ) ) {
			$translation_services = parent::get();
			set_transient( 'wpml_translation_service_list', $translation_services );
		}

		return apply_filters( 'otgs_translation_get_services', $translation_services );
	}

	/**
	 * @param bool $reload
	 *
	 * @return null|stdClass
	 */
	public function get_active( $reload = false ) {
		return $this->get_one( $this->tp_client->get_project()->get_translation_service_id(), $reload );
	}

	/**
	 * @param int  $service_id
	 * @param bool $reload
	 *
	 * @return null|string
	 */
	public function get_name( $service_id, $reload = false ) {
		$translator_name = null;

		/** @var array $translation_services */
		$translation_service = $this->get_one( $service_id, $reload );

		if ( isset( $translation_service->name ) ) {
			$translator_name = $translation_service->name;
		}

		return $translator_name;
	}

	/**
	 * @param int  $translation_service_id
	 * @param bool $reload
	 *
	 * @return null|stdClass
	 */
	private function get_one( $translation_service_id, $reload = false ) {
		$translation_service = null;

		/** @var array $translation_services */
		$translation_services = $this->get_all( $reload );
		$translation_services = wp_list_filter(
			$translation_services,
			array(
				'id' => (int) $translation_service_id,
			)
		);

		if ( $translation_services ) {
			$translation_service = current( $translation_services );
		}

		return $translation_service;
	}
}
