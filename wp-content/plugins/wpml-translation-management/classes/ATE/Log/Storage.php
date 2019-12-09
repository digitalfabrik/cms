<?php

namespace WPML\TM\ATE\Log;

use WPML\Collect\Support\Collection;
use WPML\WP\OptionManager;

class Storage {

	const OPTION_GROUP = 'TM\ATE\Log';
	const OPTION_NAME  = 'logs';
	const MAX_ENTRIES  = 50;

	/** @var OptionManager $optionManager */
	private $optionManager;

	public function __construct( OptionManager $optionManager ) {
		$this->optionManager = $optionManager;
	}

	public function add( Entry $entry ) {
		$entry->timestamp = $entry->timestamp ?: time();

		$entries = $this->getAll();
		$entries->prepend( $entry );

		$newOptionValue = $entries->forPage( 1, self::MAX_ENTRIES )
		                          ->map( function( Entry $entry ) { return (array) $entry; } )
		                          ->toArray();

		$this->optionManager->set( self::OPTION_GROUP, self::OPTION_NAME, $newOptionValue, false );
	}

	/**
	 * @return Collection Collection of Entry objects.
	 */
	public function getAll() {
		return wpml_collect( $this->optionManager->get( self::OPTION_GROUP, self::OPTION_NAME, [] ) )
			->map( function( array $item ) { return new Entry( $item ); } );
	}
}
