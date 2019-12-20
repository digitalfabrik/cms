<?php

namespace WPML\TM\ATE\REST;

use WP_REST_Request;
use WPML\Rest\Adaptor;
use WPML\TM\ATE\Sync\Arguments;
use WPML\TM\ATE\Sync\Factory;
use WPML\TM\REST\Base;
use WPML_TM_ATE_AMS_Endpoints;

class Sync extends Base {

	/**
	 * @var Factory $factory
	 */
	private $factory;

	public function __construct( Adaptor $adaptor, Factory $factory ) {
		$this->factory = $factory;
		parent::__construct( $adaptor );
	}

	/**
	 * @return array
	 */
	public function get_routes() {
		return [
			[
				'route' => WPML_TM_ATE_AMS_Endpoints::SYNC_JOBS,
				'args'  => [
					'methods'  => 'POST',
					'callback' => [ $this, 'sync' ],
					'args'     => [
						'lockKey'       => self::getStringType(),
						'ateToken'      => self::getStringType(),
						'page'          => self::getIntType(),
						'numberOfPages' => self::getIntType(),
					]
				]
			]
		];
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 */
	public function get_allowed_capabilities( WP_REST_Request $request ) {
		return [
			'manage_options',
			'manage_translations',
			'translate'
		];
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 * @throws \Auryn\InjectionException
	 */
	public function sync( WP_REST_Request $request ) {
		$args                = new Arguments();
		$args->lockKey       = $request->get_param( 'lockKey' );
		$args->ateToken      = $request->get_param( 'ateToken' );
		$args->page          = $request->get_param( 'nextPage' );
		$args->numberOfPages = $request->get_param( 'numberOfPages' );

		return (array) $this->factory->create()->run( $args );
	}
}
