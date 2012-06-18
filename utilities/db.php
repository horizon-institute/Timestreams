<?php
/**
 * Class to interact with the wp_ts database tables
 * Author: Jesse Blum (JMB)
 * Date: 2012
 */

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');	// provides dbDelta
	
	/**
	 * Controls calls to the database for timestreams
	 * @author pszjmb
	 *
	 */
	class Hn_TS_Database {
		
		/**
		 * Creates the initial timestreams db tables. This is expected only to 
		 * run at plugin install.
		 */
		function hn_ts_createMultisiteTables(){
			global $wpdb;
			$sql[] = 'CREATE TABLE IF NOT EXISTS '.$wpdb->prefix.'ts_context (
				context_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				context_type varchar(100) COLLATE utf8_unicode_ci NOT NULL,
				value varchar(100) COLLATE utf8_unicode_ci NOT NULL,
				PRIMARY KEY  (context_id)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;';
			
			$sql[] = 'CREATE TABLE IF NOT EXISTS '.$wpdb->prefix.'ts_metadata (
				metadata_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				tablename varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  				measurement_type varchar(45) COLLATE utf8_unicode_ci NOT NULL,
			    min_value varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
			    max_value varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
			    unit varchar(45) COLLATE utf8_unicode_ci NOT NULL,
			    unit_symbol varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
			    device_details varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
			    other_info varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
				PRIMARY KEY  (metadata_id)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;';
			
			$sql[] = 'CREATE TABLE IF NOT EXISTS '.$wpdb->prefix.'ts_timestreams (
				  timestream_id bigint(20) unsigned NOT NULL,
				  name varchar(55) COLLATE utf8_unicode_ci NOT NULL,
				  head_id bigint(20) NOT NULL,
				  metadata_id bigint(20) unsigned NOT NULL,
				  starttime timestamp,
				  endtime timestamp,
				  PRIMARY KEY  (timestream_id)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;';
			
			$sql[] = 'CREATE  TABLE IF NOT EXISTS '.$wpdb->prefix.'ts_timestream_has_context (
				 wp_ts_timestream_id BIGINT(20) UNSIGNED NOT NULL,
				 wp_ts_context_id BIGINT(20) UNSIGNED NOT NULL,
				 PRIMARY KEY  (wp_ts_timestream_id, wp_ts_context_id)
				) ENGINE = MyISAM DEFAULT CHARACTER SET = utf8 COLLATE = utf8_unicode_ci;';
			
			$sql[] = 'CREATE TABLE IF NOT EXISTS '.$wpdb->prefix.'ts_timestream_has_context (
				 timestream_id BIGINT(20) UNSIGNED NOT NULL,
				 context_id BIGINT(20) UNSIGNED NOT NULL,
				 PRIMARY KEY  (timestream_id, context_id)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;';
			
			$sql[] = 'CREATE  TABLE IF NOT EXISTS '.$wpdb->prefix.'ts_head (
				  head_id BIGINT(20) NOT NULL,
				  currenttime TIMESTAMP,
				  lasttime TIMESTAMP,
				  rate INT(11) NOT NULL,
				  PRIMARY KEY  (head_id) 
				) ENGINE = MyISAM DEFAULT CHARACTER SET = utf8 COLLATE = utf8_unicode_ci;';			
			
			dbDelta($sql);
		}
		/**
		 * Creates a sensor measurement table for a site.
		 * @param $blogId is the id for the site that the sensor belongs to
		 * @param $type is the type of measurement taken (such as temperature)
		 * @param $deviceId is the id for the device that took the readings
		 * @param $dataType is the type of value to use. Any MySQL type (such as decimal(4,1) ) is a legal value. 
		 */
		function hn_ts_createMeasurementTable($blogId, $type, $deviceId,$dataType){
			global $wpdb;
			$tablename = $wpdb->prefix.$blogId.'_ts_'.$type.'_'.$deviceId;
			$idName = 'id';//$type.'_'.$blogId.'_'.$deviceId.'_id';
			$sql =
			'CREATE TABLE IF NOT EXISTS '.$tablename.' (
				'.$idName.' bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				value '.$dataType.' DEFAULT NULL,
				timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  ('.$idName.')
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;';
			dbDelta($sql);
						
			$sql =
			'CREATE TABLE IF NOT EXISTS '.$tablename.'_has_context (
			'.$idName.' bigint(20) unsigned NOT NULL,
			context_id bigint(20) unsigned NOT NULL,
			PRIMARY KEY  ('.$idName.',context_id)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;';
			dbDelta($sql);
			
			return $tablename;
		}
		
		/**
		 * Adds records to the wp_ts_metadata table
		 * @param String $measurementType 
		 * @param String $minimumvalue
		 * @param String $maximumvalue
		 * @param String $unit
		 * @param String $unitSymbol
		 * @param String $deviceDetails
		 * @param String $otherInformation
		 * @param $dataType is the type of value to use. Any MySQL type (such as decimal(4,1) ) is a legal value.
		 */
		function hn_ts_addMetadataRecord($measurementType, $minimumvalue, $maximumvalue,
				$unit, $unitSymbol, $deviceDetails, $otherInformation, $dataType){
			global $wpdb;
			global $blog_id;
			//$nextdevice= $this->getCount('wp_ts_metadata')+1;
			/*$nextdevice=$this->getRecord(
					'wp_ts_metadata', 'metadata_id', 
					'1=1 ORDER BY metadata_id DESC Limit 1')+1;*/
			$nextdevice=$wpdb->get_row($wpdb->prepare( 
					"SHOW TABLE STATUS LIKE 'wp_ts_metadata';" )
			);
			$nextdevice=$nextdevice->Auto_increment;
			$tablename = $wpdb->prefix.$blog_id.'_ts_'.$measurementType.'_'.$nextdevice;
			$wpdb->insert(  
			    'wp_ts_metadata', 
			    array( 	'tablename' => $tablename,
			    		'measurement_type' => $measurementType, 
			    		'min_value' => $minimumvalue, 
			    		'max_value' => $maximumvalue, 
			    		'unit' => $unit, 
			    		'unit_symbol' => $unitSymbol,
			    		'device_details' => $deviceDetails,			    		
			    		'other_info' => $otherInformation,			    		
			    		'data_type' => $dataType), 
			    array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' , '%s' )  
			);  
			
			$this->hn_ts_createMeasurementTable($blog_id, $measurementType, $nextdevice, $dataType);
		}
		
		/**
		 * Adds records to the wp_ts_context table. 
		 * @param String $context_type
		 * @param String $context_value
		 */
		function hn_ts_addContextRecord($context_type, $context_value){
			global $wpdb;
			
			$wpdb->insert(  
			    'wp_ts_context', 
			    array( 	'context_type' => $context_type,
			    		'value' => $context_value), 
			    array( '%s', '%s' )  
			);  
		}
		
		/**
		 * Retrieves records from a given table 
		 * @param String $table is the table to select from
		 * @param String $field is the list of columns to select from
		 * @param String $where is the where clause in the select statement
		 * @return the result of the select
		 */
		function getRecord($table, $field, $where){
			global $wpdb;
			return $wpdb->get_var( 
					$wpdb->prepare( "SELECT $field FROM $table WHERE $where;" ) 
			);
		}
		
		/**
		 * Retrieves a count from a table
		 * @param $table is the table to count
		 */
		function getCount($table){
			global $wpdb;
			$sql="SELECT COUNT(*) FROM $table;";
			return $wpdb->get_var($sql);
		}
		
		/**
		 * Selects all from a table
		 * @param  $table is the table to select from
		 */
		function hn_ts_select($table){
			global $wpdb;
			$sql="SELECT * FROM $table;";
			return $wpdb->get_results($sql);
		}
		
		/**
		 * Retrieves context information
		 * @return the selection
		 */
		function hn_ts_select_context(){
			global $wpdb;
			$sql="SELECT c.context_id, t.name, c.value 
				   FROM wp_ts_context c
				   INNER JOIN wp_ts_context_type t USING(context_type_id)";
			return $wpdb->get_results($sql);
		}
		
		/**
		 * Inserts a reading into a data table
		 * Todo: handle write permissions from username and password
		 * 		Or better yet, implement OAuth
		 * 		Also, handle the format param for $wpdb->insert.
		 * 		And also make it more robust!
		 * @param $args is an array in the expected format of:
		 * [0]username
		 * [1]password
		 * [2]table name
		 * [3]value
		 * [4]timestamp 
		 */
		function hn_ts_insert_reading($args){
			global $wpdb;
			return $wpdb->insert( $args[2],
					 array('value' => $args[3],'timestamp' => $args[4]) );
			
		}
	}
?>
