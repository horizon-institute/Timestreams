<?php

/* Provides HN_TS_SensorDB_XML_RPC which provides access to db through XML-RPC.
    Copyright (C) 2012  Jesse Blum (JMB)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as
    published by the Free Software Foundation, either version 3 of the
    License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
	// Ensure that HORZ_SP_UTILITES_DIR was defined
	if ( !defined( 'HN_TS_UTILITES_DIR' ) ) exit;
	
	require_once( HN_TS_UTILITES_DIR . '/db.php'     );
	require_once(ABSPATH . 'wp-admin/includes/admin.php');
	require_once(ABSPATH . WPINC . '/class-IXR.php');
	require_once(ABSPATH . WPINC . '/class-wp-xmlrpc-server.php');
	
	/**
	 * Uses the shortcode method to output weather data from the DB to a WP page  
	 * @author pszjmb
	 *
	 */
	class HN_TS_SensorDB_XML_RPC{
		private $tsdb;
		private $wpserver;
		private $loginError;
		private $loginErrorCode;
		
		function hn_ts_SensorDB_XML_RPC(){
			$this->tsdb = new Hn_TS_Database();
			$this->wpserver = new wp_xmlrpc_server();
			$this->loginError=NULL;
			$this->loginErrorCode = new IXR_Error(401, __('Incorrect username or password.',HN_TS_NAME));
		}
		
		/**
		 * Checks user name and password to ensure that this is a valid
		 * system user
		 * @param array args should have the first and second params as user name and password
		 * The rest are ignored
		 * @return mixed WP_User object if authentication passed, false otherwise
		 */
		function hn_ts_check_user_pass($args){
			if(!$this->wpserver->login($args[0], $args[1])){
				$this->loginError = $this->wpserver->error;
				return false;
			}
			return true;
		}
		
		/**
		 * Checks username password then creates a new measurement container.
		 * @param array $args should have 11 parameters:
		 * $username, $password, $measurementType, $minimumvalue, $maximumvalue,
		 *	$unit, $unitSymbol, $deviceDetails, $otherInformation, $dataType, 
		 *	$missing_data_value
		 * @return string XML-XPC response with either an error message as a param or the
		 * name of the measurement container
		 */
		function hn_ts_create_measurements($args){
			if(count($args) < 11){
				return new IXR_Error(403, __('Incorrect number of parameters.',HN_TS_NAME));
			}
			/*(blog_id='', $$measurementType, $minimumvalue, $maximumvalue,
			$unit, $unitSymbol, $deviceDetails, $otherInformation, $dataType, 
			$missing_data_value)*/
			
			if(!$this->hn_ts_check_user_pass($args)){
				$err = $this->loginError;
				$this->loginError=NULL;
				if($err){
					return $err;
				}else{
					return $this->loginErrorCode;
				}
			}
			else{
				return $this->tsdb->hn_ts_addMetadataRecord("",$args[2],$args[3],$args[4],
						$args[5],$args[6],$args[7],$args[8],$args[9],$args[10]);
			}
		}
		
		/**
		 * Checks username password then creates a new measurement container.
		 * @param array $args should have 13 parameters:
		 * $username, $password, $measurementType, $minimumvalue, $maximumvalue,
		 *	$unit, $unitSymbol, $deviceDetails, $otherInformation, $dataType, 
		 *	$missing_data_value, $siteId, $blogId
		 * @return string XML-XPC response with either an error message as a param or the
		 * name of the measurement container
		 */
		function hn_ts_create_measurementsForBlog($args){
			if(count($args) < 13){
				return new IXR_Error(403, __('Incorrect number of parameters.',HN_TS_NAME));
			}
			/*(blog_id='', $$measurementType, $minimumvalue, $maximumvalue,
			$unit, $unitSymbol, $deviceDetails, $otherInformation, $dataType, 
			$missing_data_value, $siteId, $blogId)*/
			
			if(!$this->hn_ts_check_user_pass($args)){
				$err = $this->loginError;
				$this->loginError=NULL;
				if($err){
					return $err;
				}else{
					return $this->loginErrorCode;
				}
			}
			else{
				return $this->tsdb->hn_ts_addMetadataRecord($args[12],$args[2],$args[3],$args[4],
						$args[5],$args[6],$args[7],$args[8],$args[9],$args[10],$args[11]);
			}
		}
		
		/**
		 * Checks username password then adds a measurement to a measurement container.
		 * @param array $args should have 4 or 5 parameters:
		 * $username, $password, measurement container name, measurement value, timestamp
		 * @return string XML-XPC response with either an error message as a param or 1 (the number of insertions)
		 */
		function hn_ts_add_measurement($args){
			$this->hn_ts_check_user_pass($args);
			if(NULL != $this->loginError){
				$this->loginError=NULL;
				return $this->loginErrorCode;
			}
			else
			{
				return $this->tsdb->hn_ts_insert_reading($args);
			}
		}
		
		/**
		 * Checks username password then adds measurements to a measurement container.
		 * @param array $args should have at least 5 parameters:
		 * $username, $password, measurement container name, array containing [measurement value, timestamp]
		 * @return string XML-XPC response with either an error message as a param or the
		 * number of insertions
		 */
		function hn_ts_add_measurements($args){
			$this->hn_ts_check_user_pass($args);
			if(NULL != $this->loginError){
				$this->loginError=NULL;
				return $this->loginErrorCode;
			}
			else{
				return $this->tsdb->hn_ts_insert_readings($args);
			}
		}
		
		/**
		 * Checks username password then selects measurements from a measurement container.
		 * @param array $args should have 5-7 parameters:
		 * $username, $password, measurement container name, minimum time, maximum time,
		 * $limit (optional), $offset (optional)
		 * @return string XML-XPC response with either an error message as a param or measurement data
		 */
		function hn_ts_select_measurements($args){
			$this->hn_ts_check_user_pass($args);
			if(NULL != $this->loginError){
				$this->loginError=NULL;
				return $this->loginErrorCode;
			}
			else{
				return $this->tsdb->hn_ts_get_readings_from_name($args);
			}
		}
		
		/**
		 * Checks username password then selects the first measurement from a measurement container.
		 * @param array $args should have 3 parameters:
		 * $username, $password, measurement container name
		 * @return string XML-XPC response with either an error message as a param or measurement data
		 */
		function hn_ts_select_first_measurement($args){
			$this->hn_ts_check_user_pass($args);
			if(NULL != $this->loginError){
				$this->loginError=NULL;
				return $this->loginErrorCode;
			}
			else{
				return $this->tsdb->hn_ts_get_first_reading($args);
			}
		}
		
		/**
		 * Checks username password then selects the latest measurement from a measurement container.
		 * @param array $args should have 3 parameters:
		 * $username, $password, measurement container name
		 * @return string XML-XPC response with either an error message as a param or measurement data
		 */
		function hn_ts_select_latest_measurement($args){
			$this->hn_ts_check_user_pass($args);
			if(NULL != $this->loginError){
				$this->loginError=NULL;
				return $this->loginErrorCode;
			}
			else{
				return $this->tsdb->hn_ts_get_latest_reading($args);
			}
		}
		
		/**
		 * Checks username password then selects the latest measurement from a measurement container.
		 * @param array $args should have 3 parameters:
		 * $username, $password, measurement container name
		 * @return string XML-XPC response with either an error message as a param or count value
		 */
		function hn_ts_count_measurements($args){
			$this->hn_ts_check_user_pass($args);
			if(NULL != $this->loginError){
				$this->loginError=NULL;
				return $this->loginErrorCode;
			}
			else{
				return $this->tsdb->hn_ts_count_readings($args);
			}
		}		
		
		/**
		 * Checks username password then selects the metadata corresponding to the given measurement container.
		 * @param array $args should have 3-5 parameters:
		 * $username, $password, measurement container name,
		 * $limit (optional), $offset (optional)
		 * @return string XML-XPC response with either an error message as a param or the
		 * metadata
		 */
		function hn_ts_select_metadata_by_name($args){
			$this->hn_ts_check_user_pass($args);
			if(NULL != $this->loginError){
				$this->loginError=NULL;
				return $this->loginErrorCode;
			}
			else{
				return $this->tsdb->hn_ts_get_metadata_by_name($args);
			}
		}	
		
		/**
		 * Checks username password then adds a context record to the context container.
		 * @param array $args should have 6 parameters:
		 * $username, $password, context_type, context_value, start time (optional), end time(optional)
		 * @return string XML-XPC response with either an error message as a param or 1 (the number of insertions)
		 */
		function hn_ts_add_context($args){
			$this->hn_ts_check_user_pass($args);
			if(NULL != $this->loginError){
				$this->loginError=NULL;
				return $this->loginErrorCode;
			}
			else{
				return $this->tsdb->hn_ts_addContextRecordTimestamped($args);
			}
		}	
		
		/**
		 * Checks username password then selects context records matching the given type.
		 * @param array $args should have 3-5 parameters:
		 * $username, $password, context type,
		 * $limit (optional), $offset (optional)
		 * @return string XML-XPC response with either an error message as a param or context records
		 */
		function hn_ts_select_context_by_type($args){
			$this->hn_ts_check_user_pass($args);
			if(NULL != $this->loginError){
				$this->loginError=NULL;
				return $this->loginErrorCode;
			}
			else{
				return $this->tsdb->hn_ts_get_context_by_type($args);
			}
		}
		
		/**
		 * Checks username password then selects context records matching the given value.
		 * @param array $args should have 3-5 parameters:
		 * $username, $password, context value,
		 * $limit (optional), $offset (optional)
		 * @return string XML-XPC response with either an error message as a param or context records
		 */
		function hn_ts_select_context_by_value($args){
			$this->hn_ts_check_user_pass($args);
			if(NULL != $this->loginError){
				$this->loginError=NULL;
				return $this->loginErrorCode;
			}
			else{
				return $this->tsdb->hn_ts_get_context_by_value($args);
			}
		}
		
		/**
		 * Checks username password then selects context records matching the given value.
		 * @param array $args should have 4-6 parameters:
		 * $username, $password, context type, context value,
		 * $limit (optional), $offset (optional)
		 * @return string XML-XPC response with either an error message as a param or context records
		 */
		function hn_ts_select_context_by_type_and_value($args){
			$this->hn_ts_check_user_pass($args);
			if(NULL != $this->loginError){
				$this->loginError=NULL;
				return $this->loginErrorCode;
			}
			else{
				return $this->tsdb->hn_ts_get_context_by_type_and_value($args);
			}
		}
		
		/**
		 * Checks username password then selects context records matching the given time values.
		 * @param array $args should have 4-6 parameters:
		 * $username, $password, context type, start time (optional -- use NULL if not desired), End time (optional -- use NULL if not desired),
		 * $limit (optional), $offset (optional)
		 * @return string XML-XPC response with either an error message as a param or context records
		 */
		function hn_ts_select_context_within_time_range($args){
			$this->hn_ts_check_user_pass($args);
			if(NULL != $this->loginError){
				$this->loginError=NULL;
				return $this->loginErrorCode;
			}
			else{
				return $this->tsdb->hn_ts_get_context_within_time_range($args);
			}
		}
		
		/**
		 * Checks username password then updates the end time of the context records matching the given values.
		 * @param array $args should have 6 parameters:
		 * $username, $password, context type, context value, start time (optional -- use NULL if not desired), End time (this is the new end time)
		 * @return string XML-XPC response with either an error message as a param or number of updated records
		 */
		function hn_ts_update_context($args){
			$this->hn_ts_check_user_pass($args);
			if(NULL != $this->loginError){
				$this->loginError=NULL;
				return $this->loginErrorCode;
			}
			else{
				return $this->tsdb->hn_ts_updateContextRecord($args);
			}
		}
		
		/**
		 * Checks username password then uploads and stores details about a file.
		 * @param array $args should have 4 or 5 parameters:
		 * $username, $password, measurement container name, struct with file details (name, type, bits), timestamp
		 * @return string XML-XPC response with either an error message as a param or 1 (the number of insertions)
		 */
		function hn_ts_add_measurement_file($args){
			$this->hn_ts_check_user_pass($args);
			if(NULL != $this->loginError){
				$this->loginError=NULL;
				return $this->loginErrorCode;
			}
			else{
				$afile = $this->tsdb->hn_ts_upload_reading_file($args);
				//$tok = explode("/", $afile);
				//$tok = explode(".", $tok[count($tok)-1]);
				
				return $afile;
			}
		}
		
		/**
		 * Checks username password then uploads and stores details about files.
		 * @param array $args should have at least 4 parameters:
		 * $username, $password, measurement container name, struct with file details (name, type, bits), timestamp
		 * @return string XML-XPC response with either an error message as a param or 1 (the number of insertions)
		 */
		function hn_ts_add_measurement_files($args){
			$this->hn_ts_check_user_pass($args);
			if(NULL != $this->loginError){
				$this->loginError=NULL;
				return $this->loginErrorCode;
			}
			else{
				return $this->tsdb->hn_ts_upload_reading_files($args);
			}
		}
		
		/**
		 * Imports data from files sitting on the server.
		 * @param array $args should have at least 2 parameters:
		 * $username, $password
		 * @return string XML-XPC response with either an error message or 1
		 */
		function hn_ts_import_files($args){
			require_once( HN_TS_PLUGIN_DIR . '/controllers/weather_station_ctrl.php');
			$this->hn_ts_check_user_pass($args);
			if(NULL != $this->loginError){
				$this->loginError=NULL;
				return $this->loginErrorCode;
			}
			else{
				return $this->tsdb->hn_ts_upload_reading_files($args);
			}
		}
		
		/**
		 * Checks username password then updates data source hearbeat record
		 * @param array $args should have 3 parameters:
		 * $username, $password, tablename, ipaddress
		 * @return string XML-XPC response with either an error message or 1
		 */
		function hn_ts_heartbeat($args){
			$this->hn_ts_check_user_pass($args);
			if(NULL != $this->loginError){
				$this->loginError=NULL;
				return $this->loginErrorCode;
			}
			else{
				return $this->tsdb->hn_ts_update_heartbeat($args);
			}
		}
		
		/**
		 * Checks username password then does partial replication for all 
		 * continuous replication records
		 * @todo Output angle brackets instead of html codes
		 * @param array $args should have 4 parameters:
		 * $username, $password, tablename, ipaddress
		 * @return string XML-XPC response with either an error message or 1
		 */
		function hn_ts_replication($args){
			$this->hn_ts_check_user_pass($args);
			if(NULL != $this->loginError){
				$this->loginError=NULL;
				return $this->loginErrorCode;
			}
			else{
				$replRows = $this->tsdb->hn_ts_get_continuous_replications($args);
				if($replRows){
					require_once( HN_TS_PLUGIN_DIR . '/controllers/replication_ctrl.php');					
					$resp = "<replications>";
					foreach($replRows as $replRow){
						$resp = $resp."<replication id=\"$replRow->replication_id\">".
						hn_ts_replicate_partial($replRow)."</replication>";
					}
					$resp = $resp."</replications>";
					return $resp;
				}else{
					return new IXR_Error(403, __('Incorrect number of parameters.',HN_TS_NAME));
				}
			}
		}		
		
		function hn_ts_int_get_timestream_head($args)
		{
			return $this->tsdb->hn_ts_get_timestreamHead($args);
		}
		
		function hn_ts_int_get_timestream_data($args)
		{
			return $this->tsdb->hn_ts_get_timestreamData($args);
		}
		
		function hn_ts_int_update_timestream_head($args)
		{
			return $this->tsdb->hn_ts_get_updateTimestreamHead($args);
		}
		
		
		// TODO authentication
		function hn_ts_ext_get_time($args)
		{
			return $this->tsdb->hn_ts_ext_get_time();
		}
		
		function hn_ts_ext_get_timestreams($args)
		{
			return $this->tsdb->hn_ts_ext_get_timestreams($args);
		}
		
		
		function hn_ts_ext_get_timestream_metadata($args)
		{
			return $this->tsdb->hn_ts_ext_get_timestream_meta($args);			
		}
		
		
		function hn_ts_ext_get_timestream_data($args)
		{	
			//error_log("incoming...");
			//error_log(print_r($args));
			return $this->tsdb->hn_ts_ext_get_timestream_data($args);			
		}
		
		/**
		 * Returns information pertaining to the site, blog and user
		 * @param array $args should have 2 parameters:
		 * $username, $password
		function hn_ts_siteinfo($args){
			$this->hn_ts_check_user_pass($args);
			if(NULL != $this->loginError){
				$this->loginError=NULL;
				return $this->loginErrorCode;
			}
			else{
				global $current_blog;
				global $current_user;
				get_currentuserinfo();
				return array(get_current_blog_id(),
						$current_user->user_login, $current_user->display_name);
			}			
		}
		 */
		
		/**
		 * Associates XML-RPC method names with functions of this class 
		 * @param $methods is a key/value paired array
		 * @return $methods
		 */
		function add_new_xmlrpc_methods( $methods ) {
			//$methods['timestreams.insert_reading'] =  array(&$this, 'hn_ts_insert_reading');
			$methods['timestreams.create_measurements'] =  array(&$this, 'hn_ts_create_measurements');
			$methods['timestreams.create_measurementsForBlog'] =  array(&$this, 'hn_ts_create_measurementsForBlog');
			$methods['timestreams.add_measurement'] =  array(&$this, 'hn_ts_add_measurement');
			$methods['timestreams.add_measurements'] =  array(&$this, 'hn_ts_add_measurements');
			$methods['timestreams.select_measurements'] =  array(&$this, 'hn_ts_select_measurements');
			$methods['timestreams.select_first_measurement'] =  array(&$this, 'hn_ts_select_first_measurement');
			$methods['timestreams.select_latest_measurement'] =  array(&$this, 'hn_ts_select_latest_measurement');
			$methods['timestreams.count_measurements'] =  array(&$this, 'hn_ts_count_measurements');
			$methods['timestreams.select_metadata_by_name'] =  array(&$this, 'hn_ts_select_metadata_by_name');
			$methods['timestreams.add_context'] =  array(&$this, 'hn_ts_add_context');
			$methods['timestreams.select_context_by_type'] =  array(&$this, 'hn_ts_select_context_by_type');
			$methods['timestreams.select_context_by_value'] =  array(&$this, 'hn_ts_select_context_by_value');
			$methods['timestreams.select_context_by_type_and_value'] =  array(&$this, 'hn_ts_select_context_by_type_and_value');
			$methods['timestreams.select_context_within_time_range'] =  array(&$this, 'hn_ts_select_context_within_time_range');
			$methods['timestreams.update_context'] =  array(&$this, 'hn_ts_update_context');
			$methods['timestreams.add_measurement_file'] =  array(&$this, 'hn_ts_add_measurement_file');
			$methods['timestreams.add_measurement_files'] =  array(&$this, 'hn_ts_add_measurement_files');
			$methods['timestreams.import_data_from_files'] =  array(&$this, 'hn_ts_import_files');
			$methods['timestreams.heartbeat'] =  array(&$this, 'hn_ts_heartbeat');
			$methods['timestreams.replication'] =  array(&$this, 'hn_ts_replication');
			//$methods['timestreams.siteinfo'] =  array(&$this, 'hn_ts_siteinfo');
				
			// internal interface
			$methods['timestreams.int_get_timestream_head'] =  array(&$this, 'hn_ts_int_get_timestream_head');
			$methods['timestreams.int_get_timestream_data'] =  array(&$this, 'hn_ts_int_get_timestream_data');
			$methods['timestreams.int_update_timestream_head'] =  array(&$this, 'hn_ts_int_update_timestream_head');
			
			// external api
			$methods['timestreams.ext_get_time'] =  array(&$this, 'hn_ts_ext_get_time');
			$methods['timestreams.ext_get_timestreams'] =  array(&$this, 'hn_ts_ext_get_timestreams');
			$methods['timestreams.ext_get_timestream_metadata'] =  array(&$this, 'hn_ts_ext_get_timestream_metadata');
			$methods['timestreams.ext_get_timestream_data'] =  array(&$this, 'hn_ts_ext_get_timestream_data');
						
			return $methods;
		}
	}

?>