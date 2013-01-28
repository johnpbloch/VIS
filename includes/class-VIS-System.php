<?php
/**
 * Original Filename: class-VIS-System.php
 * User: carldanley
 * Created on: 1/28/13
 * Time: 12:04 AM
 */

class VIS_System {

	/**
	 * Blank constructor
	 */
	public function __construct() {}

	/**
	 * Creates a new file, or truncates an existing file, and writes the specified contents to it.
	 *
	 * @param string $directory The directory where the file will be created
	 * @param string $file The filename we're creating
	 * @param string $contents The contents that will be written to the folder.
	 * @return bool Indicates whether or not the file was successfully created
	 */
	public static function create_file( $directory = '', $file = '', $contents = '' ) {
		if( !is_dir( $directory ) ) {
			echo 'ERROR: Could not create file "', $file, '" because the directory "', $directory, '" did not exist!', "\n";
			return false;
		}

		$io = fopen( $directory . '/' . $file, 'w' );
		if( false !== $io ) {
			fwrite( $io, $contents );
			fclose( $io );
			echo 'Created file "', $directory, '/', $file, "\n";
		}
		else {
			echo 'ERROR: Could not create the file "', $directory, '/', $file, '"!', "\n";
			return false;
		}

		return true;
	}

	/**
	 * Creates a new directory.
	 *
	 * @param string $directory The directory that the new folder will be created in
	 * @param string $folder The folder that will be created
	 * @return bool Indicates whether or not the directory could be created or not
	 */
	public static function create_directory( $directory = '', $folder = '' ) {
		if( !is_dir( $directory ) ) {
			echo 'ERROR: Could not create the directory "', $folder, '" because it\'s parent directory "', $directory, '" did not exist!', "\n";
			return false;
		}

		if( !is_dir( $directory . '/' . $folder ) )
			mkdir( $directory . '/' . $folder );

		echo 'The directory "', $directory. '/', $folder, '" was created successfully!', "\n";;
		return true;
	}

	/**
	 * Copies a file from its point of origination to the destination path specified
	 *
	 * @param string $from The origin file's full path.
	 * @param string $to The destination folder we're copying the file to
	 * @return bool Indicates whether or not the file was copied
	 */
	public static function copy_file( $from = '', $to = '' ) {
		if( !is_file( $from ) || !is_dir( $to ) ) {
			echo 'ERROR: Could not copy the file "', $from, '" to the folder "', $to, '"!', "\n";
			return false;
		}

		$filename = self::extract_filename( $from );

		exec( 'cp ' . $from . ' ' . $to );

		if( !is_file( $to . '/' . $filename ) ) {
			echo 'ERROR: The file "', $filename, '"was not copied!', "\n";
			return false;
		}

		echo 'Copied "', $filename, '" successfully!', "\n";

		return true;
	}

	/**
	 * Extracts the filename from a full file path.
	 *
	 * @param string $file_path The file path we're extracting the filename from
	 * @return string The filename that was found in this file path
	 */
	public static function extract_filename( $file_path = '' ) {
		$file_parts = explode( '/', $file_path );
		return $file_parts[ count( $file_parts ) - 1 ];
	}

	/**
	 * Extracts the extension for the specified filename
	 *
	 * @param string $file_name The filename we're finding the extension for
	 * @return string The file extension of the filename passed to this function
	 */
	public static function extract_extension( $filename = '' ) {
		$file_parts = explode( '.', $filename );

		if( 2 <= count( $file_parts ) )
			$file_parts = $file_parts[ count( $file_parts ) - 1 ];
		else
			$file_parts = '';

		return $file_parts;
	}

	/**
	 * Checks to see if a directory contains a thing that we're looking for, whether file or folder
	 *
	 * @param string $directory The directory we're looking in for a specific thing
	 * @param string $thing The thing we are looking for within the directory that was passed
	 * @return bool Indicates whether or not the directory contains the thing we were looking for
	 */
	public static function directory_contains( $directory = '', $thing = '' ) {
		return is_dir( $directory . '/' . $thing ) || is_file( $directory . '/' . $thing );
	}

	/**
	 * Scans the directory specified, searching for all files with all extensions that match the file type specified.
	 *
	 * @param string $directory The directory to search.
	 * @param string $file_type The file extension to look for.
	 * @return array|bool If any files with the file type passed were found, returns an array containing the full path of those files. Otherwise, returns false indicating no files were found.
	 */
	public static function search_directory_for_file_type( $directory = '', $file_type = '' ) {
		if( !is_dir( $directory ) )
			return false;

		$files_found = array();

		$dir = opendir( $directory );
		if( false !== $dir ) {
			while( ( $file = readdir( $dir ) ) ) {
				if( '.' == $file || '..' === $file || is_dir( $directory . '/' . $file ) )
					continue;
				else if( is_file( $directory . '/' . $file ) && $file_type === self::extract_extension( $file ) )
					$files_found[] = $directory . '/' . $file;
			}
			closedir( $dir );
		}

		return empty( $files_found ) ? false : $files_found;
	}

	/**
	 * Checks to see if a file can be created within the specified directory or not.
	 *
	 * @param string $base_dir The directory to test creating files in
	 * @return bool Indicates whether or not a file can be created in this directory or not.
	 */
	public static function file_can_be_created( $base_dir = '' ) {
		$io = fopen( $base_dir . '/test.txt', 'w' );
		if( false !== $io ) {
			fclose( $io );
			unlink( $base_dir . '/test.txt' );

			return true;
		}

		return false;
	}

	/**
	 * Adds a git sub-module to the specified directory
	 *
	 * @param string $git_path The git location for the sub-module to integrate
	 * @param string $directory The directory to add the git sub-module to
	 * @param string $git_folder The name of the git sub-module that will be added
	 * @return bool Indicates whether or not the git sub-module was successfully added
	 */
	public static function add_git_sub_module( $git_path = '', $directory = '', $git_folder = '' ) {
		if( !is_dir( $directory ) ) {
			echo 'ERROR: Could not add submodule "', $git_folder, '" because directory (', $directory, ') does not exist!', "\n";
			return false;
		}

		echo "\n", 'Importing Git submodule "', $git_folder, '" located at "', $git_path, '"', "\n\n";
		exec( 'cd ' . $directory . ' && git submodule add ' . $git_path . ' ' . $git_folder );

		if( !is_dir( $directory . '/' . $git_folder ) ) {
			echo 'ERROR: Something went wrong during the integration of git sub-module "', $git_folder, '"', "\n";
			return false;
		}

		return true;
	}

}