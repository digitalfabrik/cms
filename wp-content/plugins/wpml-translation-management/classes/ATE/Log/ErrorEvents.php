<?php

namespace WPML\TM\ATE\Log;

class ErrorEvents {

	/** Communication errors */
	const SERVER_ATE   = 1;
	const SERVER_AMS   = 2;
	const SERVER_XLIFF = 3;

	/** Internal errors */
	const JOB_DOWNLOAD = 10;
}
