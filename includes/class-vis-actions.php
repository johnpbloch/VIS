<?php
/**
 * Original Filename: class-vis-actions.php
 * User: carldanley
 * Created on: 1/28/13
 * Time: 12:17 AM
 */

class VIS_Actions {

	/**
	 * Blank constructor
	 */
	public function __construct() {}

	/**
	 * Wrapper function that handles the integration of Vagrant into the current working directory
	 *
	 * @param string $base_dir The current working directory that VIS was launched from
	 */
	public static function integrate( $base_dir = '' ) {
		// check to see if this folder already contains .git
		if( !VIS_System::directory_contains( $base_dir, '.git' ) )
			die( 'ERROR: Please initialize Git in this directory before continuing.' . "\n" );

		if( !VIS_System::file_can_be_created( $base_dir ) )
			die( 'ERROR: Please make sure this script was given proper permissions before continuing.' . "\n" );

		if( 'n' === VIS::prompt( 'Do you want to integrate Vagrant into this folder?', 'y', array( 'y', 'n' ) ) )
			return;

		$settings = self::_build_integration_settings( $base_dir );

		echo "\n", 'Preparing Directories...', "\n\n";
		self::_prepare_integration_directories( $base_dir );

		echo "\n", 'Generating Vagrantfile...', "\n\n";
		VIS_System::create_file( $base_dir, 'Vagrantfile', VIS_Templates::render_vagrant_file( $settings ) );

		echo "\n", 'Generating Custom Provisioning Cookbook...', "\n\n";
		VIS_System::create_file( $base_dir . '/cookbooks/custom-provisions/recipes', 'default.rb', VIS_Templates::render_custom_provisioning_recipe( $settings ) );
		VIS_System::create_file( $base_dir . '/cookbooks/custom-provisions/files/default', $settings[ 'domain-name' ], VIS_Templates::render_nginx_site_configuration( $settings[ 'domain-name' ], $settings[ 'wordpress-site' ] ) );

		// generate any sub-domain site configurations
		if( $settings[ 'has-sub-domains' ] )
			foreach( $settings[ 'sub-domains' ] as $sub_domain )
				VIS_System::create_file( $base_dir . '/cookbooks/custom-provisions/files/default', $sub_domain . '.' . $settings[ 'domain-name' ], VIS_Templates::render_nginx_site_configuration( $sub_domain . '.' . $settings[ 'domain-name' ], $settings[ 'wordpress-site' ] ) );

		// copy any mysql import scripts to the cookbook files
		if( $settings[ 'has-mysql-imports' ] )
			foreach( $settings[ 'mysql-imports' ] as $import )
				VIS_System::copy_file( $import, $base_dir . '/cookbooks/custom-provisions/files/default' );

		foreach( $settings[ 'cookbooks' ] as $recipe_name => $cookbook_settings )
			VIS_System::add_git_sub_module( $cookbook_settings[ 'git-path' ], $base_dir, './cookbooks/' . $cookbook_settings[ 'git-folder' ] );
	}

	/**
	 * Prepares all of the required integration directories
	 *
	 * @param string $base_dir The directory we're building the required integration directories for
	 */
	protected static function _prepare_integration_directories( $base_dir = '') {
		$directories = array(
			'cookbooks',
			'cookbooks/custom-provisions',
			'cookbooks/custom-provisions/files',
			'cookbooks/custom-provisions/files/default',
			'cookbooks/custom-provisions/recipes'
		);

		foreach( $directories as $dir )
			VIS_System::create_directory( $base_dir, $dir );
	}

	/**
	 * Prompts the user for their specifications for integrating Vagrant into this directory.
	 *
	 * @param string $base_dir The current working directory that VIS was launched from
	 * @return array An array containing the settings for this Vagrant configuration
	 */
	protected static function _build_integration_settings( $base_dir = '' ) {
		$settings = array(
			'base-dir' => $base_dir,
			'wordpress-site' => true,
			'domain-name' => 'my.dev',
			'multi-site' => false,
			'has-sub-domains' => false,
			'sub-domains' => array(),
			'has-mysql-imports' => false,
			'mysql-imports' => array(),
			'xdebug' => false,
			'cookbooks' => VIS_Git_Library::get_default_library()
		);

		// ask for the domain name that will be run on this VM
		while( true ) {
			$domain_name = VIS::prompt( 'Please enter the primary domain name that this VM will use (leave blank for "my.dev")', 'my.dev' );
			if( 'y' === VIS::prompt( 'Are you sure you want to use "' . $domain_name . '" as your primary domain name?', 'y', array( 'y', 'n' ) ) ) {
				$settings[ 'domain-name' ] = $domain_name;
				break;
			}
		}


		// ask the user if this is a wordpress site
		$result = VIS::prompt( 'Will this VM run a WordPress site?', 'y', array( 'y', 'n' ) );
		$settings[ 'wordpress-site' ] = ( 'y' === $result ) ? true : false;

		// ask about multi-site
		if( $settings[ 'wordpress-site' ] ) {
			$result = VIS::prompt( 'Will the WordPress site be a multi-site?', 'n', array( 'y', 'n' ) );
			$settings[ 'multi-site' ] = ( 'y' === $result ) ? true : false;

			// ask about the sub domain entries
			if( $settings[ 'multi-site' ] ) {
				$result = VIS::prompt( 'Do you want to add sub-domain entries for this WordPress site?', 'n', array( 'y', 'n' ) );
				$settings[ 'has-sub-domains' ] = ( 'y' === $result ) ? true : false;

				// begin prompting for each sub domain
				while( true ) {
					$result = VIS::prompt( 'Please enter the sub-domain name, e.g., if the sub-domain is "demo.my.dev", type "demo" (otherwise, type "done" when you are finished)' );

					if( empty( $result ) )
						continue;
					else if( 'done' === $result )
						break;

					$add_sub_domain = VIS::prompt( 'Do you want to add the sub-domain "' . $result . '"?', 'y', array( 'y', 'n' ) );
					if( 'y' === $add_sub_domain )
						$settings[ 'sub-domains' ][] = $result;
				}
			}
		}

		// determine if the user has specified any database imports
		$db_imports = VIS_System::search_directory_for_file_type( $base_dir . '/db-imports', 'sql' );
		if( false !== $db_imports && 'y' === VIS::prompt( 'VIS has detected that your "db-imports" folder contains SQL files. Do you want to import these when the VM boots up?', 'n', array( 'y', 'n' ) ) ) {
			$settings[ 'has-mysql-imports' ] = true;
			$settings[ 'mysql-imports' ] = $db_imports;
		}

		// ask about other PHP extensions
		$result = VIS::prompt( 'Would you like to enable Xdebug on this machine?', 'n', array( 'y', 'n' ) );
		$settings[ 'xdebug' ] = ( 'y' === $result ) ? true : false;

		return $settings;
	}

}