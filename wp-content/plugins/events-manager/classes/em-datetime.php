<?php
/**
 * Extends DateTime allowing supplied timezone to be a string, which can also be a UTC offset.
 * Also prevents an exception being thrown. Some additional shortcuts added so less coding is required for regular tasks.
 * By doing this, we support WP's option to manually offset time without DST, which is not supported by DateTimeZone in PHP <5.5.10
 * 
 * @since 5.8.2
 */
class EM_DateTime extends DateTime {
	
	/**
	 * The name of this timezone. For example, America/New_York or UTC+3.5
	 * @var string
	 */
	protected $timezone_name = false;
	/**
	 * Shortcut representing the offset of time in the timezone, if it's a UTC manual offset, false if not.
	 * @var int
	 */
	protected $timezone_manual_offset = false;
	/**
	 * Flag for validation purposes, so we can still have a real EM_DateTime and extract dates but know if the intended datetime failed validation.
	 * A completely invalid date and time will become 1970-01-01 00:00:00 in local timezone, however a valid time can still exist with the 1970-01-01 date.
	 * If the date is invalid, only local timezones should be used since the time will not accurately convert timezone switches.
	 * @var string
	 */
	public $valid = true;
	
	/**
	 * @see DateTime::__construct()
	 * @param string $time
	 * @param string|EM_DateTimeZone $timezone Unlike DateTime this also accepts string representation of a valid timezone, as well as UTC offsets in form of 'UTC -3' or just '-3'
	 * @throws Exception
	 */
	public function __construct( $time = 'now', $timezone = null ){
		//get our EM_DateTimeZone
		$timezone = EM_DateTimeZone::create($timezone);
		//save timezone name for use in getTimezone()
		$this->timezone_name = $timezone->getName();
		$this->timezone_manual_offset = $timezone->manual_offset;
		//fix DateTime error if a regular timestamp is supplied without prepended @ symbol
		if( is_numeric($time) ){
			$time = '@'.$time;
		}elseif( is_null($time) ){
			$time = 'now';
		}
		//finally, run parent function with our custom timezone
		try{
			@parent::__construct( (string) $time, $timezone);
			if( substr($time,0,1) == '@' || $time == 'now' ) $this->setTimezone($timezone);
			$this->valid = true; //if we get this far, supplied time is valid
		}catch( Exception $e ){
			//get current date/time in relevant timezone and set valid flag to false
			parent::__construct('@0');
			$this->setTimezone($timezone);
			$this->setDate(1970,1,1);
			$this->setTime(0,0,0);
			$this->valid = false;
		}
		//deal with manual UTC offsets, but only if we haven't defaulted to the current timestamp since that would already be a correct relative value
		if( $time !== null && $time != 'now' && substr($time,0,1) != '@' ) $this->handleOffsets($timezone);
	}
	
	/**
	 * If a UTC offset timezone is active, upon object creation or modification of the time, we need to store the actual UTC time relative to
	 * the offset timezone, whereas initially the saved time will be UTC time relative to the time modified/created.
	 * 
	 * Example: 
	 * We create an object with local UTC-5 time 12pm which is actually 5pm UTC. However, by default we'll have a 12PM UTC time stored as our internal timestamp.
	 * What we'll want to do is make sure we're internally storing 5pm UTC time, which is the same value we'd have if we had a PHP native timezone like New York.
	 * The only exception we don't want to do this is if we're setting the time to NOW, as in the current time which will always be the same value in UTC no matter what timezone.
	 */
	protected function handleOffsets(){
		//handle manual UTC offsets
		if( $this->timezone_manual_offset !== false ){
			//the actual time here needs to be in actual UTC time because offsets are applied to UTC on any output functions
			$this->setTimestamp( $this->getTimestamp() - $this->timezone_manual_offset );
		}
	}
	
	/**
	 * {@inheritDoc}
	 * @see DateTime::format()
	 */
	public function format( $format = 'Y-m-d H:i:s'){
		if( !$this->valid && ($format == 'Y-m-d' || $format == em_get_date_format())) return '';
		//if we deal with offsets, then we offset UTC time by that much
		if( $this->timezone_manual_offset !== false ){
			$format = $this->formatTimezones( $format );
			if( function_exists('date_timestamp_get') ){
				return date($format, $this->getTimestampWithOffset(true) );
			}else{
				//PHP < 5.3 fallback :/ Messed up, but it works...
				$timestamp = parent::format('U');
				$server_offset = date('Z', $timestamp);
				return date( $format, $timestamp - ($server_offset * 2) + $this->getOffset() );
			}
		}
		return parent::format($format);
	}
	
	/**
	 * Formats timezone placeholders when there is a manual offset, which would be passed onto date formatting functions and usually output UTC timezone information.
	 * The $force_format flag is also useful if passing a format to any date() type of function, such as date_i18n, which will not inherit this function's timezone settings.
	 * @param string $format The format to be parsed.
	 * @param bool $force_format If set to true timezone placeholders will be formatted regardless.
	 * @return string
	 */
	public function formatTimezones($format, $force_format = false ){
		$timezone_formats = array( 'P', 'I', 'O', 'T', 'Z', 'e' );
		$timezone_formats_regex = "/P|I|O|T|Z|e/";
		if( $this->timezone_manual_offset !== false ){
			if ( preg_match( $timezone_formats_regex, $format ) ) {
				foreach ( $timezone_formats as $timezone_format ) {
					if ( false !== strpos( $format, $timezone_format ) ) {
						switch( $timezone_format ){
							case 'P':
							case 'O':
								$offset = $this->getOffset();
								$formatted_format = $timezone_format == 'P' ? 'H:i':'Hi';
								$plus_minus = $offset < 0 ? '-':'+';
								$formatted = $plus_minus . gmdate($formatted_format, absint($offset));
								break;
							case 'I':
								$formatted = '0';
								break;
							case 'T':
							case 'e':
								$formatted = $this->getTimezone()->getName();
								break;
							case 'Z':
								$formatted = $this->getOffset();
								break;
						}
						$format = ' '.$format;
						$format = preg_replace( "/([^\\\])$timezone_format/", "\\1" . backslashit( $formatted ), $format );
						$format = substr( $format, 1, strlen( $format ) -1 );
					}
				}
			}
		}elseif( $force_format ){
			//useful in cases where we may pass a format onto a date() function e.g. date_i18n, where the timezone is not relative to this object
			if ( preg_match( $timezone_formats_regex, $format ) ) {
				foreach ( $timezone_formats as $timezone_format ) {
					if ( false !== strpos( $format, $timezone_format ) ) {
						$format = ' '.$format;
						$format = preg_replace( "/([^\\\])$timezone_format/", "\\1" . backslashit( $this->format( $timezone_format ) ), $format );
						$format = substr( $format, 1, strlen( $format ) -1 );
					}
				}
			}
		}
		return $format;
	}
	
	/**
	 * Returns a date and time representation in the format stored in Events Manager settings.
	 * @param bool $include_hour
	 * @return string
	 */
	public function formatDefault( $include_hour = true ){
		$format = $include_hour ? em_get_date_format() . ' ' . em_get_hour_format() : em_get_date_format();
		$format = apply_filters( 'em_datetime_format_default', $format, $include_hour );
		return $this->i18n( $format );
	}
	
	/**
	 * Provides a translated date and time according to the current blog language. 
	 * Useful if using formats that provide date-related names such as 'Monday' or 'January', which should be translated if displayed in another language.
	 * @param string $format
	 * @return string
	 */
	public function i18n( $format = 'Y-m-d H:i:s' ){
		if( !$this->valid && $format == em_get_date_format()) return '';
		//since we use WP's date functions which don't use DateTime (and if so, don't inherit our timezones), we need to preformat timezone related formats, adapted from date_i18n
		$format = $this->formatTimezones( $format, true );
		//if we deal with offsets, then we offset UTC time by that much
		if( !function_exists('date_timestamp_get') && $this->timezone_manual_offset !== false ){
			//PHP < 5.3 fallback :/ Messed up, but it works...
			$timestamp = parent::format('U');
			$server_offset = date('Z', $timestamp);
			return date_i18n( $format, $timestamp - ($server_offset * 2) + $this->getOffset() );
		}else{
			return date_i18n( $format, $this->getTimestampWithOffset(true) );
		}
	}
	
	/**
	 * Outputs a default mysql datetime formatted string. 
	 * @return string
	 */
	public function __toString(){
		return $this->format('Y-m-d H:i:s');
	}
	
	/**
	 * Modifies the time of this object, if a mysql TIME valid format is provided (e.g. 14:30:00).
	 * Returns EM_DateTime object in all cases, but $this->valid will be set to false if unsuccessful
	 * @param string $hour
	 * @return EM_DateTime Returns object for chaining.
	 */
	public function setTimeString( $hour ){
		if( preg_match('/^\d{2}:\d{2}:\d{2}$/', $hour) ){
			$time = explode(':', $hour);
			$this->setTime($time[0], $time[1], $time[2]);
		}else{
			$this->valid = false;
		}
		return $this;
	}
	
	/**
	 * Sets timestamp with PHP 5.2.x fallback.
	 * Returns EM_DateTime object in all cases, but $this->valid will be set to false if unsuccessful
	 * @param int $timestamp
	 * @see DateTime::setTimestamp()
	 * @return EM_DateTime
	 */
	public function setTimestamp( $timestamp ){
		if( function_exists('date_timestamp_set') ){
			$return = parent::setTimestamp( $timestamp );
			$this->valid = $return !== false;
		}else{
			//PHP < 5.3 fallback :/ setting modify() with a timestamp produces unpredictable results, so we play more tricks...
			$date = explode(',', date('Y,n,j,G,i,s', $timestamp));
			parent::setDate( (int) $date[0], (int) $date[1], (int) $date[2]);
			parent::setTime( (int) $date[3], (int) $date[4], (int) $date[5]);
			//$this->valid determined in functions above
		}
		return $this;
	}
	
	/**
	 * Extends DateTime functionality by accepting a false or string value for a timezone. 
	 * Returns EM_DateTime object in all cases, but $this->valid will be set to false if unsuccessful
	 * @param string $timezone
	 * @see DateTime::setTimezone()
	 * @return EM_DateTime Returns object for chaining.
	 */
	public function setTimezone( $timezone ){
		if( $timezone == $this->getTimezone()->getName() ) return $this;
		$timezone = EM_DateTimeZone::create($timezone);
		$return = parent::setTimezone($timezone);
		$this->timezone_name = $timezone->getName();
		$this->timezone_manual_offset = $timezone->manual_offset;
		$this->valid = $return !== false;
		return $this;
	}
	
	/**
	 * Sets time along with adjusting internal timestamp for manual UTC offsets.
	 * Returns EM_DateTime object in all cases, but $this->valid will be set to false if unsuccessful
	 * {@inheritDoc}
	 * @see DateTime::setTime()
	 */
	public function setTime( $hour, $minute, $second = NULL, $microseconds = NULL ){
		/*
		 * manual offsets stores internal timestamp and date as UTC and the time is changed in UTC date/time
		 * this causes problems when UTC time is on a different date to the local time with manual offset.
		 * example: 2018-01-01 14:00 UTC => 2018-01-02 00:00 UTC+10
		 * action: set the time to 12:00
		 * result: 2018-01-01 02:00 UTC => 2018-01-01 12:00 UTC+10 -> after offset handling 
		 * expected: 2018-01-02 02:00 UTC => 2018-01-02 12:00 UTC+10 -> after offset handling
		 * solution : change date AFTER setting the time and offset handling
		 */
		if( $this->timezone_manual_offset !== false ){
			$date_array = explode('-', $this->format('Y-m-d')); 
		}
		$return = parent::setTime( (int) $hour, (int) $minute, (int) $second );
		$this->handleOffsets();
		//post-handle offsets for time changes where dates change as stated above
		if( $this->timezone_manual_offset !== false ){
			$this->setDate($date_array[0], $date_array[1], $date_array[2]);
		}
		$this->valid = $return !== false;
		return $this;
	}
	
	/**
	 * Sets date along with adjusting internal timestamp for manual UTC offsets.
	 * Returns EM_DateTime object in all cases, but $this->valid will be set to false if unsuccessful
	 * {@inheritDoc}
	 * @see DateTime::setDate()
	 */
	public function setDate( $year, $month, $day ){
		if( $this->timezone_manual_offset !== false ){
			//we run into issues if we're dealing with timezones on the fringe of date changes e.g. 2018-01-01 01:00:00 UTC+2
			$DateTime = new DateTime( $this->getDateTime(), new DateTimeZone('UTC'));
			$DateTime->setDate( (int) $year, (int) $month, (int) $day ); //$this->valid is determined here
			//create a new timestamp based on UTC DateTime and offset it to current timezone
			if( function_exists('date_timestamp_get') ){
				$timestamp = $DateTime->getTimestamp();
			}else{
				//PHP < 5.3 fallback :/
				$timestamp = $DateTime->format('U');
			}
			$timestamp -= $this->timezone_manual_offset;
			$this->setTimestamp( $timestamp );
			$return = $this->valid;
		}else{
			$return = parent::setDate( $year, $month, $day );
		}
		$this->valid = $return !== false;
		return $this;
	}
	
	/**
	 * Returns EM_DateTime object in all cases, but $this->valid will be set to false if unsuccessful
	 * {@inheritDoc}
	 * @see DateTime::setISODate()
	 */
	public function setISODate( $year, $week, $day = NULL ){
		$return = parent::setISODate( $year, $week, $day );
		$this->valid = $return !== false;
		return $this;
	}
	
	/**
	 * Handles UTC manual offsets along with providing a PHP 5.2.x fallback.
	 * Returns EM_DateTime object in all cases, but $this->valid will be set to false if unsuccessful
	 * {@inheritDoc}
	 * @see DateTime::modify()
	 */
	public function modify( $modify ){
		if( function_exists('date_add') ){
			$result = parent::modify($modify);
			$this->valid = $result !== false;
		}else{
			//PHP < 5.3 fallback :/ wierd stuff happens when using the DateTime modify function
			if( preg_match('/^(first|last) day of this month$/', $modify, $matches) ){
				$format = $matches[1] == 'first' ? 'Y-m-01':'Y-m-t';
				$timestamp = strtotime($this->format( $format ), $this->getTimestamp());
			}else{
				$timestamp = strtotime($modify, $this->getTimestamp());
			}
			$this->valid = $timestamp !== false;
			if( $this->valid ) $this->setTimestamp( $timestamp );
		}
		$this->handleOffsets();
		return $this;
	}
	
	/**
	 * Extends DateTime function to allow string representation of argument passed to create a new DateInterval object.
	 * Returns EM_DateTime object in all cases, but $this->valid will be set to false if unsuccessful
	 * @see DateTime::add()
	 * @param string|DateInterval
	 * @return EM_DateTime Returns object for chaining.
	 */
	public function add( $DateInterval ){
		if( function_exists('date_add') ){
			if( is_object($DateInterval) ){
				$result = parent::add($DateInterval);
			}else{
				$result = parent::add( new DateInterval($DateInterval) );
			}
			$this->valid = $result !== false;
		}else{
			//PHP < 5.3 fallback :/
			$strtotime = $this->dateinterval_fallback($DateInterval, 'add');
			$this->setTimestamp( strtotime($strtotime, $this->getTimestamp()) );
			//$this->valid determined in setTimestamp
		}
		return $this;
	}
	
	/**
	 * Extends DateTime function to allow string representation of argument passed to create a new DateInterval object.
	 * Returns EM_DateTime object in all cases, but $this->valid will be set to false if unsuccessful
	 * @see DateTime::sub()
	 * @param string|DateInterval
	 * @return EM_DateTime
	 */
	public function sub( $DateInterval ){
		if( function_exists('date_sub') ){
			if( is_object($DateInterval) ){
				$result = parent::sub($DateInterval);
			}else{
				$result = parent::sub( new DateInterval($DateInterval) );
			}
			$this->valid = $result !== false;
		}else{
			//PHP < 5.3 fallback :/
			$strtotime = $this->dateinterval_fallback($DateInterval, 'subtract');
			$this->setTimestamp( strtotime($strtotime, $this->getTimestamp()) );
			//$this->valid determined in setTimestamp
		}
		return $this;
	}
	
	/**
	 * Fallback function for PHP versions prior to 5.3, as sub() and add() methods aren't available and therefore we need to generate a valid string we can pass onto modify()
	 * @param string $dateinteval_string
	 * @param string $add_or_subtract
	 * @return string
	 */
	private function dateinterval_fallback( $dateinteval_string, $add_or_subtract ){
		$date_time_split = explode('T', $dateinteval_string);
		$matches = $modify_string_array = array();
		//first parse date then time if available
		preg_match_all('/([0-9]+)([YMDW])/', preg_replace('/^P/', '', $date_time_split[0]), $matches['date']);
		if( !empty($date_time_split[1]) ){
			preg_match_all('/([0-9]+)([HMS])/', $date_time_split[1], $matches['time']);
		}
		//convert DateInterval into a strtotime() valid string for use in $this->modify();
		$modify_conversion = array('Y'=>'years', 'M'=>'months', 'D'=>'days', 'W'=>'weeks', 'H'=>'hours', 'S'=>'seconds');
		foreach( $matches as $match_type => $match ){
			foreach( $match[1] as $k => $v ){
				if( $match[2][$k] == 'M' ){
					$modify_string_array[] = $match_type == 'time' ? $v . ' minutes': $v . ' months';
				}else{
					$modify_string_array[] = $v . ' '. $modify_conversion[$match[2][$k]];
				}
			}
		}
		$modifier = $add_or_subtract == 'subtract' ? '-':'+';
		return $modifier . implode(' '.$modifier, $modify_string_array);
	}
	
	/**
	 * Easy chainable cloning function, useful for situations where you may want to manipulate the current date,
	 * such as adding a month and getting the DATETIME string without changing the original value of this object.
	 * @return EM_DateTime
	 */
	public function copy(){
		return clone $this;
	}
	
	/**
	 * {@inheritDoc}
	 * @see DateTime::getTimestamp()
	 */
	public function getTimestamp(){
		if( function_exists('date_timestamp_get') ){
			return parent::getTimestamp();
		}else{
			//PHP < 5.3 fallback :/
			$strtotime = parent::format('Y-m-d H:i:s');
			$timestamp = strtotime($strtotime);
			//offset timestamp in case plugins change default timezone
			$server_offset = date('Z',$timestamp);
			$timestamp += $server_offset;
			return $timestamp;
		}
	}
	
	/**
	 * Gets a timestamp with an offset, which will represent the local time equivalent in UTC time.
	 * If using this to supply to a date() function, set $server_localized to true which will account for any rogue code
	 * that sets the server default timezone to something other than UTC (which is WP sets it to at the start)
	 * @param boolean $server_localized
	 * @return int
	 */
	public function getTimestampWithOffset( $server_localized = false ){
		//aside from the actual offset from the timezone, we also have a local server offset we need to deal with here...
		$server_offset = $server_localized ? date('Z',$this->getTimestamp()) : 0;
		if( function_exists('date_timestamp_get') ){
			return $this->getOffset() + $this->getTimestamp() - $server_offset;
		}else{
			//PHP < 5.3 fallback :/
			return $this->getTimestamp() - $server_offset;
		}
	}
	
	/**
	 * Extends DateTime::getOffset() by checking for timezones with manual offsets, such as UTC+3.5
	 * @see DateTime::getOffset()
	 * @return int
	 */
	public function getOffset(){
		if( $this->timezone_manual_offset !== false ){
			return $this->timezone_manual_offset;
		}
		return parent::getOffset();
	}
	
	/**
	 * Returns an EM_DateTimeZone object instead of the default DateTimeZone object.
	 * @see DateTime::getTimezone()
	 * @return EM_DateTimeZone
	 */
	public function getTimezone(){
		return new EM_DateTimeZone($this->timezone_name);
	}
	
	/**
	 * Returns a MySQL TIME formatted string, with the option of providing the UTC equivalent.
	 * @param bool $utc If set to true a UTC relative time will be provided.
	 * @return string
	 */
	public function getTime( $utc = false ){
		if( $utc ){
			$current_timezone = $this->getTimezone()->getName();
			$this->setTimezone('UTC');
		}
		$return = $this->format('H:i:s');
		if( $utc ) $this->setTimezone($current_timezone);
		return $return;
	}
	
	/**
	 * Returns a MySQL DATE formatted string.
	 * @param bool $utc
	 * @return string
	 */
	public function getDate( $utc = false ){
		return $this->format('Y-m-d');
	}
	
	/**
	 * Returns a MySQL DATETIME formatted string, with the option of providing the UTC equivalent.
	 * @param bool $utc If set to true a UTC relative time will be provided.
	 * @return string
	 */
	public function getDateTime( $utc = false ){
		if( $utc ){
			$current_timezone = $this->getTimezone()->getName();
			$this->setTimezone('UTC');
		}
		$return = $this->format('Y-m-d H:i:s');
		if( $utc ) $this->setTimezone($current_timezone);
		return $return;
	}
	
	/* PHP 5.3+ functions that are not used and should not be used until 5.3 is a minimum requirement
	
	/**
	 * NOT TO BE USED until PHP 5.3 is a minimum requirement in WordPress
	 * Extends the DateTime::createFromFormat() function by setting the timezone to the default blog timezone if none is provided.
	 * @param string $format
	 * @param string $time
	 * @param string|EM_DateTimeZone $timezone
	 * @return boolean|EM_DateTime
	 */
	public static function createFromFormat( $format, $time, $timezone = null ){
		$timezone = EM_DateTimeZone::create($timezone);
		$DateTime = parent::createFromFormat($format, $time, $timezone);
		if( $DateTime === false ) return false;
		return new EM_DateTime($DateTime->format('Y-m-d H:i:s'), $timezone);
	}
	
	public function diff( $DateTime, $absolute = null ){
		if( function_exists('date_diff') ){
			return parent::diff( $DateTime, $absolute );
		}else{
			//PHP < 5.3 fallback :/ there is no fallback, really
			return new stdClass();
		}
	}
}

/**
 * Extends the native DateTimeZone object by allowing for UTC manual offsets as supported by WordPress, along with eash creation of a DateTimeZone object with the blog's timezone. 
 * @since 5.8.2
 */
class EM_DateTimeZone extends DateTimeZone {
	
	public $manual_offset = false;
	
	public function __construct( $timezone ){
		//if we're not suppiled a DateTimeZone object, create one from string or implement manual offset
		if( $timezone != 'UTC' ){
			$timezone = preg_replace('/^UTC ?/', '', $timezone);
			if( is_numeric($timezone) ){
				if( absint($timezone) == 0 ){
					$timezone = 'UTC';
				}else{
					$this->manual_offset = $timezone * 3600;
					$timezone = 'UTC';
				}
			}
		}
		parent::__construct($timezone);
	}
	
	/**
	 * Special function which converts a timezone string, UTC offset or DateTimeZone object into a valid EM_DateTimeZone object.
	 * If no value supplied, a EM_DateTimezone with the default WP environment timezone is created.
	 * @param mixed $timezone
	 * @return EM_DateTimeZone
	 */
	public static function create( $timezone = false ){
		//if we're not suppiled a DateTimeZone object, create one from string or implement manual offset
		if( !empty($timezone) && !is_object($timezone) ){
			//create EM_DateTimeZone object if valid, otherwise allow defaults to override later
			try {
				$timezone = new EM_DateTimeZone($timezone);
			}catch( Exception $e ){
				$timezone = null;
			}
		}elseif( is_object($timezone) && get_class($timezone) == 'DateTimeZone'){
			//if supplied a regular DateTimeZone, convert it to EM_DateTimeZone
			$timezone = new EM_DateTimeZone($timezone->getName());
		}
		if( !is_object($timezone) ){
			//if no valid timezone supplied, get the default timezone in EM environment, otherwise the WP timezone or offset
			$timezone = get_option( 'timezone_string' );
			if( !$timezone ) $timezone = get_option('gmt_offset');
			$timezone = new EM_DateTimeZone($timezone);
		}
		return $timezone;
	}
	
	/**
	 * {@inheritDoc}
	 * @see DateTimeZone::getOffset()
	 */
	public function getOffset( $datetime ){
		if( $this->manual_offset !== false ){
			return $this->manual_offset;
		}
		return parent::getOffset( $datetime );
	}
	
	/**
	 * {@inheritDoc}
	 * @see DateTimeZone::getName()
	 */
	public function getName(){
		if( $this->manual_offset !== false ){
			if( $this->manual_offset > 0 ){
				$return = 'UTC+'.$this->manual_offset/3600;
			}else{
				$return = 'UTC'.$this->manual_offset/3600;
			}
			return $return;
		}
		return parent::getName();
	}
	
	/**
	 * If the timezone has a manual UTC offset, then an empty array of transitions is returned.
	 * {@inheritDoc}
	 * @see DateTimeZone::getTransitions()
	 */
	public function getTransitions( $timestamp_begin = null, $timestamp_end = null ){
		if( $this->manual_offset !== false ){
			return array();
		}
		if( version_compare(phpversion(), '5.3') < 0 ){
			return parent::getTransitions();
		}else{
			return parent::getTransitions($timestamp_begin, $timestamp_end);
		}
	}
}