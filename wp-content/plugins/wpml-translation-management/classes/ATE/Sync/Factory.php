<?php

namespace WPML\TM\ATE\Sync;

use function WPML\Container\make;
use WPML\Utilities\KeyedLock;

class Factory {

	const LOCK_NAME = 'ate_sync';

	/**
	 * @return Process
	 * @throws \Auryn\InjectionException
	 */
	public function create() {
		$lock = make( KeyedLock::class, [ ':name' => self::LOCK_NAME ] );
		return make( Process::class, [ ':lock' => $lock ] );
	}
}
