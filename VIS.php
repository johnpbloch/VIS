<?php

require __DIR__ . '/includes/class-vis.php';
require __DIR__ . '/includes/class-vis-actions.php';
require __DIR__ . '/includes/class-vis-git-library.php';
require __DIR__ . '/includes/class-vis-system.php';
require __DIR__ . '/includes/class-vis-templates.php';

// --integrate
if( 3 === count( $argv ) && '--integrate' === $argv[ 2 ] ) {
	if( is_dir( $argv[ 1 ] ) )
		VIS_Actions::integrate( $argv[ 1 ] );
}

echo 'Thank you for using VIS v', VIS::$version, "\n";