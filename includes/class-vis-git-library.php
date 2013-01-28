<?php
/**
 * Original Filename: class-vis-git-library.php
 * User: carldanley
 * Created on: 1/28/13
 * Time: 2:48 AM
 */

class VIS_Git_Library {

	/**
	 * @var array A container of known cookbooks and their corresponding git paths and folder names
	 */
	protected static $_cookbooks = array(
		'apt' => array( 'git-path' => 'git@github.com:opscode-cookbooks/apt.git', 'git-folder' => 'apt' ),
		'build-essential' => array( 'git-path' => 'git@github.com:opscode-cookbooks/build-essential.git', 'git-folder' => 'build-essential' ),
		'mysql::server' => array( 'git-path' => 'git@github.com:opscode-cookbooks/mysql.git', 'git-folder' => 'mysql' ),
		'nginx' => array( 'git-path' => 'git@github.com:opscode-cookbooks/nginx.git', 'git-folder' => 'nginx' ),
		'ohai' => array( 'git-path' => 'git@github.com:opscode-cookbooks/ohai.git', 'git-folder' => 'ohai' ),
		'openssl' => array( 'git-path' => 'git@github.com:opscode-cookbooks/openssl.git', 'git-folder' => 'openssl' ),
		'php-fpm' => array( 'git-path' => 'git@github.com:carldanley/php-fpm-cookbook.git', 'git-folder' => 'php-fpm' ),
	);

	/**
	 * Blank constructor
	 */
	public function __construct() {}

	/**
	 * Gets the default library of cookbooks to install
	 *
	 * @return array An array of cookbooks that represents what will be installed on the VM by default
	 */
	public static function get_default_library() {
		$cookbooks = array( 'apt', 'build-essential', 'nginx', 'ohai', 'openssl', 'mysql::server', 'php-fpm' );
		$tmp = array();
		foreach( $cookbooks as $cookbook )
			$tmp[ $cookbook ] = self::$_cookbooks[ $cookbook ];

		return $tmp;
	}

}