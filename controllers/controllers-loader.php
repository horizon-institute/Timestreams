<?php
	// Exit if accessed directly
	if ( !defined( 'ABSPATH' ) ) exit;
	
	// Utilites directory
	if ( !defined( 'HN_TS_CONTROLLERS_DIR' ) )
		define( 'HN_TS_CONTROLLERS_DIR', HN_TS_PLUGIN_DIR . '/controllers' );
	
	// Require utility files
	require_once( HN_TS_CONTROLLERS_DIR . '/metadata_ctrl.php'     );
	require_once( HN_TS_CONTROLLERS_DIR . '/context_ctrl.php'     );
?>