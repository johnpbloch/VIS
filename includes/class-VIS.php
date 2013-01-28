<?php
/**
 * Original Filename: class-VIS.php
 * User: carldanley
 * Created on: 1/28/13
 * Time: 12:03 AM
 */

class VIS {

	/**
	 * @var string The current version of VIS
	 */
	public static $version = '1.0.0';

	/**
	 * Blank constructor
	 */
	public function __construct() {}

	/**
	 * Prompt's the user for information by displaying a message and waiting for the an answer that matches what we're
	 * looking for.
	 *
	 * @param string $message The message that will be displayed to the user
	 * @param string $default_value The default value used if the user didn't enter anything
	 * @param array $valid_values A container holding all of the possible values that the user is allowed to enter
	 * @return string The answer the user typed
	 */
	public static function prompt( $message = '', $default_value = false, $valid_values = array( '*' ) ) {
		$valid = false;

		// force all valid answers as strings
		$tmp = array();
		foreach( $valid_values as $answer )
			$tmp[] = strtolower( ( string )$answer );
		$valid_values = $tmp;

		// keep prompting the user while the answer is invalid
		while( !$valid ) {

			// print the command
			echo $message;
			if( !in_array( '*', $valid_values ) && !empty( $valid_values ) )
				echo ' (', implode( '/', $valid_values ), ')';
			echo ': ';

			// wait for a response from the user
			$result = '';
			while( true ) {
				$char = fgetc( STDIN );
				if( "\n" === $char )
					break;
				$result .= $char;
			}
			$result = strtolower( $result );

			// check to see if this answer was invalid
			if( in_array( '*', $valid_values ) || in_array( $result, $valid_values ) )
				$valid = true;
		}

		// default the answer if the user's was empty and a default value was supplied
		if( empty( $result ) && !!$default_value )
			$result = $default_value;

		return $result;
	}

}