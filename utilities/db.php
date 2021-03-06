<?php
/**
 * Class to interact with the wp_ts database tables
 * Author: Jesse Blum (JMB)
 * Date: 2012
 */

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');	// provides dbDelta
require_once(ABSPATH . WPINC . '/class-IXR.php');
require_once(ABSPATH . WPINC . '/class-wp-xmlrpc-server.php');

/**
 * Controls calls to the database for timestreams
 * @author pszjmb
 * @todo replace "wp" with $wpdb->prefix
 *
 */
class Hn_TS_Database {
	private $wpserver;
	protected $missingcontainername;
	protected $missingParameters;

	function Hn_TS_Database(){
		$this->wpserver = new wp_xmlrpc_server();
		$this->missingcontainername = new IXR_Error(403, __('Missing container name parameter.',HN_TS_NAME));//"Missing container name parameter.";
		$this->missingParameters= new IXR_Error(403, __('Incorrect number of parameters.',HN_TS_NAME));
	}

	/**
	 * Creates the initial timestreams db tables. This is expected only to
	 * run at plugin install.
	 */
	function hn_ts_createMultisiteTables(){
		global $wpdb;
		$sql = 'CREATE TABLE IF NOT EXISTS '.$wpdb->prefix.'ts_context (
		context_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  		user_id bigint(20) NOT NULL,
		context_type varchar(100) COLLATE utf8_unicode_ci NOT NULL,
		value varchar(100) COLLATE utf8_unicode_ci NOT NULL,
		start_time TIMESTAMP NULL DEFAULT 0,
		end_time TIMESTAMP NULL DEFAULT 0,
		PRIMARY KEY  (context_id)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;';
		$wpdb->query($sql);

		$sql = 'CREATE TABLE IF NOT EXISTS '.$wpdb->prefix.'ts_metadata (
		metadata_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		tablename varchar(45) COLLATE utf8_unicode_ci NOT NULL,
	    producer_site_id bigint(20) NOT NULL DEFAULT \'1\',
	    producer_blog_id int(20) NOT NULL DEFAULT \'1\',
	    producer_id int(20) DEFAULT NULL,
		measurement_type varchar(45) COLLATE utf8_unicode_ci NOT NULL,
		min_value varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
		max_value varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
		unit varchar(45) COLLATE utf8_unicode_ci NOT NULL,
		unit_symbol varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
		device_details varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
		other_info varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
		data_type varchar(45) COLLATE utf8_unicode_ci NOT NULL,
		missing_data_value varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
		last_IP_Addr varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
		heartbeat_time timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  		license bigint(20) DEFAULT NULL,
		PRIMARY KEY  (metadata_id)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;';
		$wpdb->query($sql);

		$sql = 'CREATE TABLE IF NOT EXISTS '.$wpdb->prefix.'ts_timestreams (
		timestream_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  		user_id bigint(20) NOT NULL,
		name varchar(55) COLLATE utf8_unicode_ci NOT NULL,
		head_id bigint(20) NOT NULL,
		metadata_id bigint(20) unsigned NOT NULL,
		starttime timestamp,
		endtime timestamp,
		PRIMARY KEY  (timestream_id)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;';
		$wpdb->query($sql);

		$sql = 'CREATE  TABLE IF NOT EXISTS '.$wpdb->prefix.'ts_timestream_has_context (
		'.$wpdb->prefix.'ts_timestream_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		'.$wpdb->prefix.'ts_context_id BIGINT(20) UNSIGNED NOT NULL,
		PRIMARY KEY  ('.$wpdb->prefix.'ts_timestream_id, '.$wpdb->prefix.'ts_context_id)
		) ENGINE = MyISAM DEFAULT CHARACTER SET = utf8 COLLATE = utf8_unicode_ci;';
		$wpdb->query($sql);

		$sql = 'CREATE  TABLE IF NOT EXISTS '.$wpdb->prefix.'ts_head (
		head_id BIGINT(20) NOT NULL AUTO_INCREMENT,
		currenttime TIMESTAMP,
		lasttime TIMESTAMP,
		rate INT(11) NOT NULL,
		PRIMARY KEY  (head_id)
		) ENGINE = MyISAM DEFAULT CHARACTER SET = utf8 COLLATE = utf8_unicode_ci;';
		$wpdb->query($sql);

		$sql = 'CREATE TABLE IF NOT EXISTS '.$wpdb->prefix.'ts_replication (
			  `replication_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			  `mylock` timestamp NULL DEFAULT NULL,
			  `local_user_id` bigint(20) unsigned NOT NULL,
			  `local_table` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
			  `remote_user_login` varchar(60) COLLATE utf8_unicode_ci NOT NULL,
			  `remote_user_pass` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
			  `remote_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			  `remote_table` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
			  `continuous` tinyint(1) NOT NULL,
  			  `copy_files` tinyint(1) NOT NULL,
  			  `blog_id` bigint(20) DEFAULT NULL,
			  `last_replication` varchar(75) COLLATE utf8_unicode_ci DEFAULT NULL,
			  PRIMARY KEY (`replication_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;';
		$wpdb->query($sql);

		$sql = 'CREATE  TABLE IF NOT EXISTS '.$wpdb->prefix.'ts_container_shared_with_blog (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		table_name varchar(45) COLLATE utf8_unicode_ci NOT NULL COMMENT \'Table name for a measurement container\',
		site_id bigint(20) NOT NULL,
		blog_id bigint(20) NOT NULL,
		PRIMARY KEY  (id)
		) ENGINE = MyISAM DEFAULT CHARACTER SET = utf8 COLLATE = utf8_unicode_ci;';
		$wpdb->query($sql);

		// Note that not non-autoincrement primary key is intentional as it should correspond
		//	to an existing wp_ts_metadata id
		$sql = 'CREATE TABLE IF NOT EXISTS `'.$wpdb->prefix.'ts_metadatafriendlynames` (
		  `metadata_id` bigint(20) unsigned NOT NULL,
		  `friendlyname` varchar(45) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
		  PRIMARY KEY (`metadata_id`),
		  UNIQUE KEY `friendlyname` (`friendlyname`)
		) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT=\'Associates friendly names to metadata rows. Legacy data work\';';
		$wpdb->query($sql);

		$sql = "CREATE TABLE IF NOT EXISTS  `$wpdb->prefix"."ts_apikeys` (
		  `publickey` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
		  `privatekey` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
		  `userid` bigint(20) NOT NULL,
		  `creation_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  `revoked` tinyint(1) NOT NULL DEFAULT '0',
		  PRIMARY KEY (`publickey`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		$wpdb->query($sql);

		$sql = "CREATE TABLE IF NOT EXISTS `$wpdb->prefix"."ts_datalicenses` (
		`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  	`name` varchar(100) NOT NULL,
		`shortname` varchar(20) NOT NULL,
		`url` varchar(255) NOT NULL,
		PRIMARY KEY (`id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=10;";
		$wpdb->query($sql);
		$sql = "TRUNCATE TABLE `$wpdb->prefix"."ts_datalicenses`;";
		$wpdb->query($sql);
		$sql = "INSERT INTO `$wpdb->prefix"."ts_datalicenses` (`id`, `name`, `shortname`, `url`) VALUES
		(1, 'Open Data Commons Attribution Licence', 'ODC-By', 'http://opendatacommons.org/licenses/by/'),
		(2, 'Open Data Commons Open Database Licence', 'ODC-ODbL', 'http://opendatacommons.org/licenses/odbl/'),
		(3, 'Open Data Commons Database Contents Licence', 'ODC-DbCL', 'http://opendatacommons.org/licenses/dbcl/'),
		(4, 'Attribution 3.0 Unported', 'CC BY 3.0', 'https://creativecommons.org/licenses/by/3.0/'),
		(5, 'Attribution-ShareAlike 3.0 Unported', 'CC BY-SA 3.0', 'https://creativecommons.org/licenses/by-sa/3.0/'),
		(6, 'Attribution-NoDerivs 3.0 Unported', 'CC BY-ND 3.0', 'https://creativecommons.org/licenses/by-nd/3.0/'),
		(7, 'Attribution-NonCommercial 3.0 Unported', 'CC BY-NC 3.0', 'https://creativecommons.org/licenses/by-nc/3.0/'),
		(8, 'Attribution-NonCommercial-ShareAlike 3.0 Unported', 'CC BY-NC-SA 3.0', 'https://creativecommons.org/licenses/by-nc-sa/3.0/'),
		(9, 'Attribution-NonCommercial-NoDerivs 3.0 Unported ', 'CC BY-NC-ND 3.0', 'https://creativecommons.org/licenses/by-nc-nd/3.0/'),
		(10, 'other', 'other', '');";
		$wpdb->query($sql);

		//For some reason dbDelta wasn't working for all of the tables :(
	}

	/**
	 * Really simple sanitisation for input parameters.
	 * @param is a String to be sanitized $arg
	 * @return a String containing only -a-zA-Z0-9_ or NULL
	 * @todo Improve this to better sanitise the inputs
	 */
	function hn_ts_sanitise($arg){
		if(isset($arg)){
			return preg_replace('/[^-a-zA-Z0-9_\s:\/.]/', '_', (string)$arg);
		}else{
			return null;
		}
	}

	/**
	 * Creates a sensor measurement table for a site.
	 * @param $blogId is the id for the site that the sensor belongs to
	 * @param $type is the type of measurement taken (such as temperature)
	 * @param $deviceId is the id for the device that took the readings
	 * @param $dataType is the type of value to use. Any MySQL type (such as decimal(4,1) ) is a legal value.
	 * @return the table name or null on failure
	 */
	function hn_ts_createMeasurementTable($blogId, $type, $deviceId,$dataType){
		global $wpdb;
		$blogId=absint($blogId);
		$deviceId=absint($deviceId);
		$type = sanitize_text_field($type);
		$dataType = sanitize_text_field($dataType);
		$tablename = $wpdb->prefix.$blogId.'_ts_'.$type.'_'.$deviceId;
		$idName = 'id';//$type.'_'.$blogId.'_'.$deviceId.'_id';
		$sql =
		'CREATE TABLE IF NOT EXISTS '.$tablename.' (
		'.$idName.' bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		value '.$dataType.' DEFAULT NULL,
		valid_time timestamp NULL,
		transaction_time timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  ('.$idName.')
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;';

		if(1 == $wpdb->query($sql)){ // using query instead of dbDelta for consistent return type
			return $tablename;
		} else{
			return null;
		}
	}

	/**
	 * Adds records to the wp_ts_metadata table
	 * @param String $blog_id
	 * @param String $measurementType
	 * @param String $minimumvalue
	 * @param String $maximumvalue
	 * @param String $unit
	 * @param String $unitSymbol
	 * @param String $deviceDetails
	 * @param String $otherInformation
	 * @param $dataType is the type of value to use. Any MySQL type (such as decimal(4,1) ) is a legal value.
	 * @param $missingDataValue is a value of type $dataType which represents rows in the timeseries with unknown values.
	 * @param $siteId site that owns the measurement container
	 * @param $blogId blog that owns the measurement container
	 * @change 01/11/2012 - Modified by JMB to handle siteId and BlogId
	 * @change 19/12/2012 - Modified by JMB to handle invalid characters and sanitisation correctly
	 * @change 19/02/2013 - Modified by JMB to allow friendlyId
	 */
	function hn_ts_addMetadataRecord($blog_id='', $measurementType,
			$minimumvalue, $maximumvalue,
			$unit, $unitSymbol, $deviceDetails, $otherInformation,
			$dataType,$missingDataValue,$friendlyId=NULL, $license=NULL, $siteId=1){
		global $wpdb;
		if($blog_id==''){
			global $blog_id;
		}
		$blog_id=absint($blog_id);
		$siteId=absint($siteId);
		//Ensure that there aren't empty or null values going into mandatory fields.
		if((0==strcmp(strtoupper($measurementType),"NULL") || 0==strcmp($measurementType,""))){
			return new IXR_Error(403, __('Measurement type may not be blank.',HN_TS_NAME));
		}
		if((0==strcmp(strtoupper($unit),"NULL") || 0==strcmp($unit,""))){
			return new IXR_Error(403, __('Unit may not be blank.',HN_TS_NAME));
		}
		if((0==strcmp(strtoupper($dataType),"NULL") || 0==strcmp($dataType,""))){
			return new IXR_Error(403, __('Data type may not be blank.',HN_TS_NAME));
		}
		//Ensure that table names don't have spaces.
		$measurementType = preg_replace('/\s+/', '_', $measurementType);
		//$nextdevice= $this->getCount('wp_ts_metadata')+1;
		/*$nextdevice=$this->getRecord(
				'wp_ts_metadata', 'metadata_id',
				'1=1 ORDER BY metadata_id DESC Limit 1')+1;*/
		$nextdevice=$wpdb->get_row(
				"SHOW TABLE STATUS LIKE '".$wpdb->prefix."ts_metadata';"
		);
		$nextdevice=$nextdevice->Auto_increment;
		$tablename = $this->hn_ts_createMeasurementTable(
				$blog_id, $measurementType, $nextdevice, $dataType);
		if($tablename == null){
			return null;
		}

		global $current_user;
		$current_user = wp_get_current_user();
		//Ensure frinedlyname is unique
		if(NULL != $friendlyId){
			$sql = "SELECT COUNT(*) FROM ".$wpdb->prefix."ts_metadatafriendlynames
			WHERE friendlyname = '$friendlyId'";
			if( 0 != $wpdb->get_var($sql)){
				return new IXR_Error(403, __('Your measurement container name is already being used. Please try a new value.',HN_TS_NAME));
			}
		}
		$res = $wpdb->insert(
				$wpdb->prefix.'ts_metadata',
				array( 	'tablename' => $tablename,
						'measurement_type' => $measurementType,
						'min_value' => $minimumvalue,
						'max_value' => $maximumvalue,
						'unit' => $unit,
						'unit_symbol' => $unitSymbol,
						'device_details' => $deviceDetails,
						'other_info' => $otherInformation,
						'data_type' => $dataType,
						'missing_data_value' => $missingDataValue,
						'producer_site_id' => $siteId,
						'producer_blog_id' => $blog_id,
						'producer_id' => $current_user->ID,
						'license' => $license),
				array( '%s', '%s', '%s', '%s', '%s', '%s',
						'%s' , '%s','%s', '%s' , '%s', '%s' )
		);
		if (FALSE != $res && NULL != $friendlyId){
			$wpdb->insert(
					''.$wpdb->prefix.'ts_metadatafriendlynames',
					array( 	'metadata_id' => $wpdb->insert_id,
					'friendlyname' => $friendlyId),
					array( '%s', '%s')
			);
		}
		return $res;
	}

	/**
	 * Builds a portion of a SQL select query for the limit and offset
	 * @param $limitIn is an integer with the number of rows to limit by
	 * @param $offsetIn is an integer to shift the begining of the returned recordset
	 * @return string of the form "" if $limitIn=0 or else "Limit # OFFSET #"
	 */
	function hn_ts_getLimitStatement($limitIn,$offsetIn){
		$limit="";
		if($limitIn){
			$limit = "LIMIT $limitIn";
			if($offsetIn){
				$limit = $limit." OFFSET $offsetIn";
			}
		}
		return $limit;
	}

	/**
	 * Retrieves records from a readings table of the form
	 * wp_[blog-id]_ts_[measurement-type]_[device-id]
	 * @param $args is an array in the expected format of:
	 * [0]username
	 * [1]password
	 * [2]table name
	 * [3]minimum timestamp
	 * [4]maximum timestamp
	 * [5]limit -- optional
	 * [6]offset -- optional
	 * [7]sort by column -- optional
	 * [8]descending boolean -- optional
	 * @return the result of the select
	 */
	function hn_ts_get_readings_from_name($args){
		global $wpdb;
		if(count($args) < 3){
			return $this->missingcontainername;
		}
		for($i=0; $i < count($args); $i=$i+1){
			$args[$i]=$this->hn_ts_sanitise($args[$i]);
		}
		$table=$args[2];
		$minimumTime=$args[3];
		$maximumTime=$args[4];
		$where="WHERE ";
		$limit=$this->hn_ts_getLimitStatement($args[5], $args[6]);
		$sortcolumn=(count($args) > 7 ? $args[7] : "");
		$descending=(count($args) > 8 ? $args[8] : "");
		$sort = "";

		if($minimumTime){
			$where=$where."valid_time >= '$minimumTime' ";

			if($maximumTime){
				$where=$where."AND valid_time <= '$maximumTime'";
			}
		}else if($maximumTime){
			$where=$where."valid_time <= '$maximumTime'";
		}

		if(0==strcmp($where,"WHERE ")){
			$where="";
		}

		if($sortcolumn) {
			$sort = "ORDER BY " . $sortcolumn;
			if($descending)
				$sort .= " DESC";
		}

		return $wpdb->get_results("SELECT * FROM $table $where $sort $limit;");
	}

	/**
	 * Retrieves count of records from a readings table of the form
	 * wp_[blog-id]_ts_[measurement-type]_[device-id]
	 * @param $args is an array in the expected format of:
	 * [0]username
	 * [1]password
	 * [2]table name
	 * [3]minimum timestamp
	 * [4]maximum timestamp
	 * @return the result of the select
	 */
	function hn_ts_get_count_from_name($args){
		global $wpdb;
		if(count($args) < 3){
			return $this->missingcontainername;
		}
		$argcount=0;
		foreach($args as $arg){
			$args[$argcount++]=$this->hn_ts_sanitise($arg);
		}
		$table=$args[2];
		$minimumTime=$args[3];
		$maximumTime=$args[4];
		$where="WHERE ";
		if($minimumTime){
			$where=$where."valid_time >= '$minimumTime' ";

			if($maximumTime){
				$where=$where."AND valid_time < '$maximumTime'";
			}
		}else if($maximumTime){
			$where=$where."valid_time < '$maximumTime'";
		}

		if(0==strcmp($where,"WHERE ")){
			$where="";
		}
		return $wpdb->get_var( "SELECT COUNT(*) FROM $table $where;" );
	}

	/**
	 * Retrieves the latest record from a readings table
	 * of the form wp_[blog-id]_ts_[measurement-type]_[device-id]
	 * @param $args is an array in the expected format of:
	 * [0]username
	 * [1]password
	 * [2]table name
	 * To do: Sanitise parameters
	 * @return the result of the select
	 */
	function hn_ts_get_latest_reading($args){
		global $wpdb;
		if(count($args) != 3){
			return $this->missingcontainername;
		}

		$argcount=0;
		foreach($args as $arg){
			$args[$argcount++]=$this->hn_ts_sanitise($arg);
		}
		$table=$args[2];

		return $wpdb->get_row( "SELECT * FROM
				$table WHERE id = ( SELECT MAX( id ) FROM $table ) "	);
	}

	/**
	 * Selects all from a table
	 * @param  $table is the table to select from
	 */
	function hn_ts_select($table){
		global $wpdb;
		$sql="SELECT * FROM $table;";
		return $wpdb->get_results( $sql );
	}

	/**
	 * Select metadata for viewable tables
	 * Viewables tables are either owned or shared with the current user or the current blog/site
	 */
	function hn_ts_select_viewable_metadata(){
		if(is_multisite()){
			return $this->hn_ts_select_viewable_metadata_multisite();
		}else{
			return $this->hn_ts_select_viewable_metadata_singlesite();
		}
	}

	/**
	 * Select metadata for viewable tables from multisite wordpress instance
	 * Viewables tables are either owned or shared with the current user or the current blog/site
	 */
	function hn_ts_select_viewable_metadata_multisite(){
		global $current_user;
		global $wpdb;
		$current_user = wp_get_current_user();
		$blogId = get_current_blog_id();
		$siteId = get_current_site();
		$siteId = $siteId->id;
		$sql = "SELECT ".$wpdb->prefix."ts_metadata . * , ".$wpdb->prefix."ts_metadatafriendlynames.friendlyname,
				".$wpdb->prefix."ts_datalicenses.name AS licname, ".$wpdb->prefix."ts_datalicenses.shortname AS licshortname,
				".$wpdb->prefix."ts_datalicenses.url AS licurl
				FROM ".$wpdb->prefix."ts_metadata
				LEFT JOIN ".$wpdb->prefix."ts_metadatafriendlynames ON ".$wpdb->prefix."ts_metadata.metadata_id = ".$wpdb->prefix."ts_metadatafriendlynames.metadata_id
				LEFT JOIN ".$wpdb->prefix."ts_datalicenses ON ".$wpdb->prefix."ts_metadata.license = ".$wpdb->prefix."ts_datalicenses.id
				WHERE (
				".$wpdb->prefix."ts_metadata.producer_id = $current_user->ID OR (
					".$wpdb->prefix."ts_metadata.producer_blog_id = $blogId AND ".$wpdb->prefix."ts_metadata.producer_site_id = $siteId
				) OR
				tablename IN (
					SELECT `".$wpdb->prefix."ts_container_shared_with_blog`.table_name
					FROM `".$wpdb->prefix."ts_container_shared_with_blog` WHERE
					`".$wpdb->prefix."ts_container_shared_with_blog`.blog_id = $blogId AND
					`".$wpdb->prefix."ts_container_shared_with_blog`.site_id = $siteId
				)
			) ORDER BY metadata_id ASC";
		return $wpdb->get_results( $sql );
	}

	/**
	 * Select metadata for viewable tables from singlesite wordpress instance
	 * Viewables tables are either owned or shared with the current user or the current blog/site
	 */
	function hn_ts_select_viewable_metadata_singlesite(){
		global $current_user;
		global $wpdb;
		$current_user = wp_get_current_user();
		$blogId = get_current_blog_id();
		$sql = "SELECT ".$wpdb->prefix."ts_metadata . * , ".$wpdb->prefix."ts_metadatafriendlynames.friendlyname,
		    ".$wpdb->prefix."ts_datalicenses.name AS licname, ".$wpdb->prefix."ts_datalicenses.shortname AS licshortname,
				".$wpdb->prefix."ts_datalicenses.url AS licurl
				FROM ".$wpdb->prefix."ts_metadata
				LEFT JOIN ".$wpdb->prefix."ts_metadatafriendlynames ON ".$wpdb->prefix."ts_metadata.metadata_id = ".$wpdb->prefix."ts_metadatafriendlynames.metadata_id
				LEFT JOIN ".$wpdb->prefix."ts_datalicenses ON ".$wpdb->prefix."ts_metadata.license = ".$wpdb->prefix."ts_datalicenses.id
				WHERE (
					".$wpdb->prefix."ts_metadata.producer_id = $current_user->ID OR
					".$wpdb->prefix."ts_metadata.producer_blog_id = $blogId
				OR
				tablename IN (
					SELECT `".$wpdb->prefix."ts_container_shared_with_blog`.table_name
					FROM `".$wpdb->prefix."ts_container_shared_with_blog` WHERE
					`".$wpdb->prefix."ts_container_shared_with_blog`.blog_id = $blogId
				)
			) ORDER BY metadata_id ASC";
		return $wpdb->get_results( $sql );
	}

	/**
	 * Determines if the current user or the current blog is owner of
	 * the given measurement container
	 * @return bool true if table is owned by blog or user
	 */
	function hn_ts_isTableOwnedByBlogOrUser($tableIn){
		if(is_multisite()){
			return $this->hn_ts_isTableOwnedByBlogOrUserMultisite($tableIn);
		}else{
			return $this->hn_ts_isTableOwnedByBlogOrUserSinglesite($tableIn);
		}
	}

	/**
	 * Multissite variant of hn_ts_isTableOwnedByBlogOrUser
	 * @return bool true if table is owned by blog or user
	 */
	function hn_ts_isTableOwnedByBlogOrUserMultisite($tableIn){
		global $current_user;
		global $wpdb;
		$current_user = wp_get_current_user();
		$blogId = get_current_blog_id();
		$siteId = get_current_site();
		$siteId = $siteId->id;
		if(!isset($tableIn)){
			return false;
		}
		global $wpdb;
		$sql = "SELECT COUNT(*) FROM `".$wpdb->prefix."ts_metadata` WHERE tablename = '$tableIn' AND (" .
			" producer_id = $current_user->ID OR (".
				" producer_blog_id = $blogId AND producer_site_id = $siteId".
			" ) " .
		")";
		$count = $wpdb->get_var( $sql );
		if($count > 0){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * Singlesite variant of hn_ts_isTableOwnedByBlogOrUser
	 * @return bool true if table is owned by blog or user
	 */
	function hn_ts_isTableOwnedByBlogOrUserSinglesite($tableIn){
		global $current_user;
		global $wpdb;
		$current_user = wp_get_current_user();
		$blogId = get_current_blog_id();
		if(!isset($tableIn)){
			return false;
		}
		global $wpdb;
		$sql = "SELECT COUNT(*) FROM `".$wpdb->prefix."ts_metadata` WHERE (" .
		" producer_id = $current_user->ID OR ".
			" producer_blog_id = $blogId )".
		" AND tablename = '$tableIn'";
		$count = $wpdb->get_var( $sql );
		if($count > 0){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * Determines if given table has been shared with the given site/blog
	 * @return bool true if table is shared or false otherwise
	 */
	function hn_ts_isTableSharedWithBlog($tableIn, $site_id, $blog_id){
		if(!is_multisite()){
			return false;
		}else{
			global $wpdb;
			$sql = "SELECT COUNT(*) FROM `".$wpdb->prefix."ts_container_shared_with_blog` WHERE " .
					" table_name = '$tableIn' AND site_id = ".
					"'$site_id' AND blog_id = '$blog_id'";
			$count = $wpdb->get_var( $sql );
			if($count > 0){
				return true;
			}else{
				return false;
			}
		}
	}

	/**
	 * Retrieves context information
	 * [0]limit -- optional
	 * [1]offset -- optional
	 * @return the selection
	 */
	function hn_ts_select_context($args){
		global $wpdb;

		$limit=$this->hn_ts_getLimitStatement($args[0], $args[1]);

		$sql="SELECT * FROM ".$wpdb->prefix."ts_context $limit;";
		return $wpdb->get_results( $sql );
	}

	/**
	 * Retrieves context information
	 * [2]context_type
	 * [3]limit -- optional
	 * [4]offset -- optional
	 * @return the selection
	 */
	function hn_ts_get_context_by_type($args){
		global $wpdb;
		if(count($args) < 3){
			return $this->missingParameters;
		}

		$limit=$this->hn_ts_getLimitStatement($args[3], $args[4]);

		$sql="SELECT *  FROM ".$wpdb->prefix."ts_context WHERE context_type='$args[2]' $limit;";
		return $wpdb->get_results( $sql );
	}

	/**
	 * Retrieves context information
	 * [2]value
	 * [3]limit -- optional
	 * [4]offset -- optional
	 * @return the selection
	 */
	function hn_ts_get_context_by_value($args){
		global $wpdb;

		if(count($args) < 3){
			return $this->missingParameters;
		}
		$limit=$this->hn_ts_getLimitStatement($args[3], $args[4]);
		$sql="SELECT *  FROM ".$wpdb->prefix."ts_context WHERE value='$args[2]' $limit;";
		return $wpdb->get_results( $sql );
	}

	/**
	 * Retrieves context information
	 * [2]context_type
	 * [3]value
	 * [4]limit -- optional
	 * [5]offset -- optional
	 * @return the selection
	 */
	function hn_ts_get_context_by_type_and_value($args){
		global $wpdb;

		if(count($args) < 4){
			return $this->missingParameters;
		}
		$limit=$this->hn_ts_getLimitStatement($args[4], $args[5]);

		$sql="SELECT *  FROM ".$wpdb->prefix."ts_context WHERE context_type='$args[2]' AND value='$args[3]' $limit;";
		return $wpdb->get_results( $sql );
	}

	/**
	 * Retrieves context information within a time range
	 * [2]start time
	 * [3]end time
	 * [4]limit -- optional
	 * [5]offset -- optional
	 * @return the selection
	 */
	function hn_ts_get_context_within_time_range($args){
		global $wpdb;
		//$sql="SELECT *  FROM wp_ts_context WHERE context_type='$args[2]' AND value='$args[3]'";
		//return $wpdb->get_results( $sql );

		if(count($args) < 4){
			return $this->missingParameters;
		}
		if(count($args) > 5){
			$limit=$this->hn_ts_getLimitStatement($args[4], $args[5]);
		}else{
			$limit="";
		}

		$startTime=$args[2];
		$endTime=$args[3];
		$where="WHERE ";
		if(!(0==strcmp(strtoupper($startTime),"NULL") || 0==strcmp($startTime,""))){
			$where=$where."start_time >= '$startTime' ";

			if(!(0==strcmp(strtoupper($endTime),"NULL") || 0==strcmp($endTime,""))){
				$where=$where."AND 	end_time < '$endTime'";
			}
		}else if(!(0==strcmp(strtoupper($endTime),"NULL") || 0==strcmp($endTime,""))){
			$where=$where."	end_time < '$endTime'";
		}
		if(0==strcmp($where,"WHERE ")){
			$where="";
		}
		return $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."ts_context $where $limit;"	);
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
		if(count($args)> 4){
			return $wpdb->insert( $args[2],
					array('value' => $args[3],'valid_time' => $args[4]) );
		}else if(count($args) == 4){
			$ret = $wpdb->insert( $args[2], array('value' => $args[3]));
			if($ret){
				do_action('hn_ts_replicate_reading', $args[2], $args[3], $args[4]);
			}
			return $ret;
		}else{
			return $this->missingParameters;
		}
	}

	/**
	 * Inserts multiple readings into a data table
	 * Todo: handle write permissions from username and password
	 * 		Or better yet, implement OAuth
	 * 		Also, handle the format param for $wpdb->insert.
	 * 		And also make it more robust!
	 * @param $args is an array in the expected format of:
	 * [0]username
	 * [1]password
	 * [2]table name
	 * [odd]value
	 * [even]timestamp
	 */
	function hn_ts_insert_readings($args){
		global $wpdb;
		$retval = 0;
		$cnt = count($args);
		if(count($args) < 4){
			return "Number of insertions: $retval";
		}
		$readings = array();
		for($i=3; $i+1 < $cnt; $i+=2){
			if(count($args) > $i+1){
				$content = array('value' => $args[$i],'valid_time' => $args[$i+1]);
				if($wpdb->insert( $args[2],$content )){
					$retval++;
					array_push($readings,$content);
				}
			}
			do_action('hn_ts_replicate_readings', $args[2], $readings);
		}
		return "Number of insertions: $retval";

	}

	/**
	 * Inserts a row into the replication table
	 * Todo: handle write permissions from username and password
	 * 		Or better yet, implement OAuth
	 * 		Also, handle the format param for $wpdb->insert.
	 * 		Don't store passwords as plain text -- encrypt them!!!
	 * 		And also make it more robust!
	 * And of course -- data sanitize!
	 * @param $args is an array in the expected format of:
	 * [0]username
	 * [1]password
	 * [2]local table name
	 * [3]remote_user_login
	 * [4]remote_user_pass
	 * [5]remote URL
	 * [6]remote table name
	 * [7]continuous (boolean)
	 * [8]last replication (timestamp)
	 * [9]should copy files (boolean)
	 * [10]external blog id
	 */
	function hn_ts_insert_replication($args){
		global $wpdb;
		if(count($args)>= 9){
			global $current_user;
			$current_user = wp_get_current_user();
			return $wpdb->insert( $wpdb->prefix.'ts_replication',
					array('local_user_id' => $current_user->ID,
				 		'local_table' => $args[2],
							'remote_user_login' => $args[3],
				 		'remote_user_pass' => $args[4],
				 		'remote_url' => $args[5],
				 		'remote_table' => $args[6],
				 		'continuous' => $args[7],
				 		'last_replication' => $args[8],
				 		'copy_files' => $args[9],
				 		'blog_id' => $args[10],
					) );
		}else{
			return $this->missingParameters;
		}
	}

	/**
	 * Adds records to the wp_ts_context table.
	 * @param $args should have 6 parameters:
	 * $username, $password, context_type, context_value, start time (optional), end time(optional)
	 * optional values should be "NULL"
	 * Todo: Sanitise inputs
	 */
	function hn_ts_addContextRecordTimestamped($args){
		global $wpdb;
		if(count($args) != 6){
			return $this->missingParameters;
		}
		$baseVals = array( 	'context_type' => $args[2], 'value' => $args[3]);
		$baseTypes=array( '%s', '%s');

		if(!(0 == strcmp($args[4], "") || 0 == strcmp($args[4], "NULL"))){
			$baseVals['start_time']= $args[4];
			array_push($baseTypes, '%s');
		}

		if(!(0 == strcmp($args[5], "") || 0 == strcmp($args[5], "NULL"))){
			$baseVals['end_time']=$args[5];
			array_push($baseTypes, '%s');
		}

		return $wpdb->insert( $wpdb->prefix.'ts_context',  $baseVals, $baseTypes  );
	}

	/**
	 * Adds records to the wp_ts_context table.
	 * @param String $context_type
	 * @param String $context_value
	 * Todo: Sanitise inputs
	 */
	function hn_ts_addContextRecord($context_type, $context_value){
		global $wpdb;

		return $wpdb->insert(
				$wpdb->prefix.'ts_context',
				array( 	'context_type' => $context_type,
						'value' => $context_value),
				array( '%s', '%s' )
		);
	}

	/**
	 * Updates wp_ts_context records.
	 * @param $args is an array in the expected format of:
	 * [0]username
	 * [1]password
	 * [2]Context type
	 * [3]Value
	 * [4]Start time (optional -- use 'NULL' to exclude)
	 * [5]End time
	 * Todo: Sanitise inputs
	 */
	function hn_ts_updateContextRecord($args){
		global $wpdb;
		if(count($args) != 6){
			return $this->missingParameters;
		}
		$where="";
		if(0!=strcmp(strtoupper($args[4]),"NULL")){
			$where = array( 	'context_type' => $args[2],
					'value' => $args[3],
					'start_time' => $args[4]);
		}else{
			$where = array( 	'context_type' => $args[2],
					'value' => $args[3]);
		}

		return $wpdb->update(
				$wpdb->prefix.'ts_context',  array( 'end_time' => $args[5]), $where,'%s','%s'
		);
	}

	/**
	 * Updates wp_ts_container_shared_with_blog based on an array of items
	 * which should have array elements in the form [yes|no]:[siteId]:[blogId]
	 * The wp_ts_container_shared_with_blog model should be updated to remove any
	 * no entries for the given tablename and add any unpresent yes entries.
	 * @param $tablename is the measurement container table name to insert and remove
	 * records for
	 * To do: make this all happen in a single database transaction
	 */
	function updateWp_ts_container_shared_with_blog($tablename, $responseArray){
		global $wpdb;
		foreach ($responseArray as $response){
			list($answer, $siteId, $blogId) = explode(":",$response);
			if(!strcasecmp("yes",$answer)){
				// Add yes items to database if they aren't already present
				$sql="SELECT COUNT(*) FROM ".$wpdb->prefix."ts_container_shared_with_blog WHERE ".
						"table_name='$tablename' AND ".
						"site_id = '$siteId' AND blog_id = '$blogId';";
				$thecount = $wpdb->get_var( $sql );
				if($thecount <= 0){
					$wpdb->insert(
							$wpdb->prefix.'ts_container_shared_with_blog',
							array( 	'table_name' => $tablename,
									'site_id' => $siteId,
									'blog_id' => $blogId),
							array( '%s', '%s', '%s' )
					);
				}
			} else if(!strcasecmp("no",$answer)){
				// Remove no items from database
				$sql="DELETE FROM ".$wpdb->prefix."ts_container_shared_with_blog WHERE ".
						"table_name='$tablename' AND ".
						"site_id = '$siteId' AND blog_id = '$blogId';";
				$wpdb->query( $sql );
			}
		}
	}

	/**
	 * Records that a file was uploaded. The timestamp is the time the file was last modified prior to upload
	 * Todo: handle write permissions from username and password
	 * 		Or better yet, implement OAuth
	 * 		Also, handle the format param for $wpdb->insert.
	 * 		And also make it more robust!
	 * @param $args is an array in the expected format of:
	 * [0]username
	 * [1]password
	 * [2]table name
	 * [3]File details: [0] Name, [1] Type [3] Bits
	 * [4]timestamp
	 */
	function hn_ts_upload_reading_file($args){
		global $wpdb, $blog_id;

		if(count($args) < 4){
			return $this->missingParameters;
		}
		$args[3]['name']=$args[2].'_'.$args[3]['name'];
		$fileArgs = array($blog_id, $args[0],$args[1],$args[3]);
		$uploadedFile = $this->wpserver->mw_newMediaObject($fileArgs);
		if(is_array($uploadedFile)){
			if(count($args)>4){
				$wpdb->insert( $args[2],
						array('value' => $uploadedFile['url'],'valid_time' => $args[4]) );
			}else{
				$wpdb->insert( $args[2],
						array('value' => $uploadedFile['url']) );
			}
			return $uploadedFile;
		}else{
			return $uploadedFile;
		}

	}
	/**
	 * Records that a file was uploaded. The timestamp is the time the file was last modified prior to upload
	 * Todo: handle write permissions from username and password
	 * 		Or better yet, implement OAuth
	 * 		Also, handle the format param for $wpdb->insert.
	 * 		And also make it more robust!
	 * @param $args is an array in the expected format of:
	 * [0]username
	 * [1]password
	 * [2]table name
	 * [3]Array of File details: [0] Name, [1] Type [3] Bits [4]timestamp
	 */
	function hn_ts_upload_reading_files($args){
		global $wpdb, $blog_id;

		if(count($args) < 4){
			return $this->missingParameters;
		}

		$fileCount = 0;
		foreach($args[3] as $aFile){
			$aFile['name']=$args[2].'_'.$aFile['name'];
			$fileArgs = array($blog_id, $args[0],$args[1],$aFile);
			$uploadedFile = $this->wpserver->mw_newMediaObject($fileArgs);
			if(is_array($uploadedFile)){
				$wpdb->insert( $args[2],
						array('value' => $uploadedFile['url'],'transaction_time' => $aFile['timestamp']) );
				$fileCount++;
			}
		}
		return $fileCount;
	}

	/**
	 * Updates the wp_ts_metadata table row's heartbeat columns
	 * Todo: handle write permissions from username and password
	 * 		Or better yet, implement OAuth
	 * 		Also, handle the format param for $wpdb->insert.
	 * 		Sanitize ipaddress.
	 * 		And also make it more robust!
	 * @param $args is an array in the expected format of:
	 * [0]username
	 * [1]password
	 * [2]table name
	 * [3]ipaddress
	 */
	function hn_ts_update_heartbeat($args){
		global $wpdb, $blog_id;

		$where = array( 'tablename' => $args[2]);
		$date = new DateTime();
		return $wpdb->update(
				$wpdb->prefix.'ts_metadata',  array( 'last_IP_Addr' => $args[3], 'heartbeat_time' => date("Y-m-d H:i:s")), $where,'%s','%s'
		);
	}

	// timestreams interface
	// FIXME - how much is still used by current version?

	function hn_ts_addTimestream($timestreamName, $metadataId)
	{
		global $wpdb;

		// create head
		$wpdb->insert($wpdb->prefix.'ts_head',
				array('rate' => 1),
				array('%d')
		);

		$headId = $wpdb->insert_id;
		global $current_user;
		$current_user = wp_get_current_user();

		// create timestream
		$wpdb->insert($wpdb->prefix.'ts_timestreams',
				array(	'head_id' => $headId,
						'metadata_id' => $metadataId,
						'name' => $timestreamName,
						'user_id' => "$current_user->ID"
				),
				array('%s', '%s', '%s', '%s')
		);

		$timestreamId = mysql_insert_id();
	}

	function hn_ts_updateTimestream($timestreamId, $metadataId)
	{
		global $wpdb;

		$wpdb->update($wpdb->prefix.'ts_timestreams',
				array(
						'metadata_id' => $metadataId,
				),
				array('timestream_id' => $timestreamId)
		);
	}

	function hn_ts_deleteTimestream($timestreamId)
	{
		global $wpdb;

		$timestream = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."ts_timestreams WHERE timestream_id = $timestreamId" );

		if($timestream != null)
		{
			$wpdb->query( "DELETE FROM ".$wpdb->prefix."ts_head WHERE head_id = $timestream->head_id" );
			$wpdb->query( "DELETE FROM ".$wpdb->prefix."ts_timestreams WHERE timestream_id = $timestreamId" );
		}
	}

	/**
	 * @change 01/11/2012 - Modified by JMB to restrict timestreams to those owned by the current user
	 */
	function hn_ts_getTimestreams()
	{
		global $wpdb;
		global $current_user;
		$current_user = wp_get_current_user();
		$results=$wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."ts_timestreams WHERE ".
				"user_id = '$current_user->ID' ORDER BY timestream_id DESC" );
		// echo print_r($results,true);
		return $results;
	}

	function hn_ts_getReadHead($headId)
	{
		global $wpdb;

		return $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."ts_head WHERE head_id = $headId" );
	}

	function hn_ts_getMetadata($metadataId)
	{
		global $wpdb;

		return $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."ts_metadata WHERE metadata_id = $metadataId" );
	}

	function hn_ts_get_timestreams($args)
	{
		global $wpdb;

		return $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."ts_timestreams" );
	}


	// internal interface
	function hn_ts_get_timestreamHead($args)
	{
		// username
		// password
		$timestreamId = $args[2];

		return $this->hn_ts_timestream_update($timestreamId);
	}

	function hn_ts_get_updateTimestreamHead($args)
	{
		// username
		// password
		$timestreamId = $args[2];
		$newHead = $args[3];
		$newStart = $args[4];
		$newEnd = $args[5];
		$newRate = $args[6];

		global $wpdb;

		$timestream = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."ts_timestreams WHERE timestream_id = $timestreamId" );

		if($timestream==null)
		{
			error_log("timestream not found " . $timestreamId);
			return -1;
		}

		$currenttime = date ("Y-m-d H:i:s", $newHead);

		$wpdb->update($wpdb->prefix.'ts_head',
				array(
						'currenttime' => $currenttime,
						'rate' => $newRate,
				),
				array('head_id' => $timestream->head_id)
		);

		$starttime = date ("Y-m-d H:i:s", $newStart);
		$endtime = date ("Y-m-d H:i:s", $newEnd);

		$wpdb->update($wpdb->prefix.'ts_timestreams',
				array(
						'starttime' => $starttime,
						'endtime' => $endtime,
				),
				array('timestream_id' => $timestreamId)
		);

		return 1;
	}


	function hn_ts_get_timestreamData($args)
	{
		$tablename = $args[2];
		$limit = $args[3];
		$offset = $args[4];
		$lastTimestamp = $args[5];

		//error_log($lastTimestamp);

		global $wpdb;

		$where = "";

		if($lastTimestamp)
		{
			$timeStr = date ("Y-m-d H:i:s", $lastTimestamp);
			$where = "WHERE valid_time > \"$timeStr\"";
		}

		$sql = "SELECT * FROM (SELECT * FROM $tablename $where ORDER BY valid_time DESC LIMIT $offset,$limit) AS T1 ORDER BY valid_time ASC";

		//error_log($sql);

		$readings = $wpdb->get_results( $sql );

		for($i = 0; $i < count($readings); $i++)
		{
			$newts = strtotime($readings[$i]->valid_time);
			$readings[$i]->timestamp = $newts;
		}

		//error_log(count($readings));

		return $readings;
	}

	// update head

	// update timestream start / end / datasources


	// external api
	// TODO rename viz
	function hn_ts_ext_get_time()
	{
		global $wpdb;
		$_now = $wpdb->get_var( "SELECT CURRENT_TIMESTAMP" );
		return strtotime($_now);
	}

	function hn_ts_ext_get_timestreams($args)
	{
		global $wpdb;

		return $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."ts_timestreams" );
	}


	function hn_ts_ext_get_timestream_meta($args)
	{
		$timestreamId = $args[2];

		// for specific timestream
		global $wpdb;

		$timestream = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."ts_timestreams WHERE timestream_id = $timestreamId" );

		if($timestream==null)
		{
			error_log("timestream not found " . $timestreamId);
			return new IXR_Error(403, __('Timestream not found.',HN_TS_NAME));
		}

		return $this->hn_ts_getMetadata($timestream->metadata_id);
	}


	function hn_ts_ext_get_timestream_data($args)
	{
		// TODO return current server time for initial request sync

		$timestreamId = $args[2];
		$lastAskTime = $args[3];
		$limit = $args[4];
		// JMB added to allow user to choose the order (ASC or DESC) of the results
		if(count($args) > 5){
			$order=strtoupper($args[5]);
			if(strcmp($order, "ASC")){
				$order = "DESC";
			}
		} else{
			$order = "DESC";
		}

		$this->hn_ts_timestream_update($timestreamId);

		global $wpdb;

		$timestream = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."ts_timestreams WHERE timestream_id = $timestreamId" );

		if($timestream==null)
		{
			error_log("timestream not found " . $timestreamId);
			return new IXR_Error(403, __('Timestream not found.',HN_TS_NAME));
		}

		$head = $this->hn_ts_getReadHead($timestream->head_id);

		if($head==null)
		{
			error_log("head not found " . $timestream->head_id);
			return new IXR_Error(403, __('Head not found.',HN_TS_NAME));
		}

		$metadata = $this->hn_ts_getMetadata($timestream->metadata_id);

		if($metadata==null)
		{
			error_log("metadata not found " . $timestream->metadata_id);
			return new IXR_Error(403, __('Metadata not found.',HN_TS_NAME));
		}

		// how much timestream has elapsed since last ask
		if($head->rate==0)
		{
			// no data, stopped
			return new IXR_Error(403, __('Data not found.',HN_TS_NAME));
		}

		$_now = $wpdb->get_var( "SELECT CURRENT_TIMESTAMP" );
		$now = strtotime($_now);

		//echo "now " . $now . "\n";
		//echo "lastask " . $lastAskTime . "\n";

		$elapsed = ($now - $lastAskTime) * $head->rate;

		//echo "head ct " . $head->currenttime . "\n";
		//echo "elapsed since last ask " . $elapsed . "\n";

		// get data between head->currenttime and head->currenttime - elapsed
		$maxdate = $head->currenttime;
		$mindate = date ("Y-m-d H:i:s", strtotime($head->currenttime) - $elapsed);

		//echo "maxdate " . $maxdate . "\n";
		//echo "mindate " . $mindate . "\n";
		//echo $metadata->tablename . "\n";

		$limitstr = "";

		if($limit!=0)
		{
		/*	if($order == "ASC"){
				$count = $wpdb->get_row("SELECT COUNT FROM $metadata->tablename WHERE valid_time > $mindate AND valid_time <= $maxdate");
				$lt = $count - $limit;
				$limitstr = " LIMIT $lt , $limit";
			}else{
			*/
			$limitstr = " LIMIT 0 , $limit";
			//}
		}

		$ret = $wpdb->get_results($wpdb->prepare("SELECT * FROM $metadata->tablename WHERE valid_time > '$mindate' AND valid_time <= '$maxdate' ORDER BY valid_time DESC $limitstr", ARRAY_N));

		if(!strcmp($order,'ASC')){
			return array_reverse($ret);
		}else return $ret;
	}

	// triggered by viz getting data, update read head at given rate
	function hn_ts_timestream_update($timestreamId)
	{
		global $wpdb;

		$timestream = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."ts_timestreams WHERE timestream_id = $timestreamId" );

		if($timestream==null)
		{
			error_log("timestream not found " . $timestreamId);
			return new IXR_Error(403, __('Timestream not found.',HN_TS_NAME));
		}

		$head = $this->hn_ts_getReadHead($timestream->head_id);

		if($head==null)
		{
			error_log("head not found " . $timestream->head_id);
			return null;
		}

		// TODO ratelimit?
		// TODO rate should be a float

		// update/move read head based on timestream time

		// currenttime = time in data source frame
		// lasttime = real time head last moved
		// distance to move = (now - lasttime) * rate

		$_now = $wpdb->get_var( "SELECT CURRENT_TIMESTAMP" );
		$now = strtotime($_now);

		//echo "head->lasttime " . $head->lasttime . "\n";
		//echo "head->lasttime ut " . strtotime($head->lasttime) . "\n";

		$newcurrent = (($now - strtotime($head->lasttime)) * $head->rate) + strtotime($head->currenttime);

		if(strcmp($timestream->endtime, "0000-00-00 00:00:00")==0)
		{
			$timestream->endtime = "1970-01-01 00:00:00";
		}

		//if(strtotime($timestream->endtime) > 0)
		//	error_log("blaj");

		if(strtotime($timestream->endtime) > 0 && $newcurrent > strtotime($timestream->endtime))
		{
			//error_log("reset to starttime");
			$currenttime = $timestream->starttime;
		}
		else
		{
			$currenttime = date ("Y-m-d H:i:s", $newcurrent);
		}

		$lasttime = date ("Y-m-d H:i:s", $now);
		//echo "now " . $now . "\n";
		//echo "newcur " . $newcurrent . "\n";

		$wpdb->update($wpdb->prefix.'ts_head',
				array(
						'lasttime' => $lasttime,
						'currenttime' => $currenttime,
				),
				array('head_id' => $timestream->head_id)
		);

		$head->lasttime = strtotime($lasttime);
		$head->currenttime = strtotime($currenttime);

		return $head;
	}

	/**
	 * Get Replication record given its id
	 * @param unknown_type $replRow
	 */
	function hn_ts_getReplRow($replRowId){
		global $wpdb;
		return $wpdb->get_row(
				'SELECT * FROM '.$wpdb->prefix.'ts_replication'.
				' WHERE replication_id='.$replRowId);
	}

	/**
	 * Update Replication record given an id and timestamp
	 * @param unknown_type $replRow
	 */
	function hn_ts_updateReplRow($replRowId, $value){
		global $wpdb;

		return $wpdb->update(
				$wpdb->prefix.'ts_replication',
				array( 'last_replication' => $value), array( 'replication_id' => $replRowId),
				'%s','%s');
	}

	/**
	 * Returns a list of all blogs excluding the current one ordered by site id then blog id
	 * Returns NULL for non multisite blogs.
	 */
	function hn_ts_getBlogList(){
		if(!is_multisite()){
			return NULL;
		}
		global $wpdb;
		global $current_user;
		global $wpdb;
		$current_user = wp_get_current_user();
		$blogId = get_current_blog_id();
		$siteId = get_current_site();
		$siteId = $siteId->id;
		return $wpdb->get_results(
				"SELECT blog_id, site_id, domain, path FROM ".$wpdb->prefix."blogs WHERE (blog_id <> $blogId OR site_id <> $siteId) AND deleted = '0' AND spam = '0' AND archived = '0' ORDER BY site_id, blog_id"
		);
	}

	/**
	 * Generates new public and private keys and adds them to the database
	 */
	function hn_ts_addNewAPIKeys(){
		global $wpdb;
		$table = $wpdb->base_prefix.'ts_apikeys';
		global $current_user;
		$current_user = wp_get_current_user();

		$wpdb->insert(
				$table,
				array( 	'publickey' => substr(MD5(microtime()), 0, 10),
						'privatekey' => MD5(microtime()),
						'userid' => $current_user->ID),
				array( '%s', '%s', '%s')
		);
		echo '<h4>Added new keys.</h4>';
	}

	/**
	 * Retrieves API Key information
	 */
	function hn_ts_select_apiKeys(){
		global $current_user;
		global $wpdb;
		$current_user = wp_get_current_user();
		$sql = "SELECT publickey, userid, creation_date FROM `".$wpdb->prefix."ts_apikeys`
		WHERE userid='$current_user->ID' AND revoked=0
		ORDER BY creation_date DESC;";
		return $wpdb->get_results( $sql );
	}

	/**
	 * Retrieves API private key for a given public key
	 * @param $pubkey should be a valid key
	 */
	function hn_ts_revealPrivateAPIKey($pubkey){
		global $current_user;
		global $wpdb;
		$current_user = wp_get_current_user();
		$sql = "SELECT privatekey FROM `".$wpdb->prefix."ts_apikeys`
		WHERE userid='$current_user->ID' AND
		revoked=0 AND publickey='$pubkey';";
		return $wpdb->get_var( $sql );
	}

	function hn_ts_revokeAPIKey($pubkey){
		global $wpdb;
		global $current_user;
		$current_user = wp_get_current_user();
		return $wpdb->update(
				$wpdb->prefix.'ts_apikeys',
				array( 'revoked' => 1),
				array( 	'userid' => $current_user->ID,
						'publickey' => $pubkey)
		);
	}

	function hn_ts_replLock($rowId){
		global $wpdb;
		return $wpdb->query(
				"
					UPDATE ".$wpdb->prefix."ts_replication
					SET mylock = NOW()
					WHERE replication_id = $rowId
					AND (mylock IS NULL OR now( ) - mylock >10)
				"
		);
	}

	function hn_ts_replUnlock($rowId){
		global $wpdb;
		return $wpdb->query(
				"
					UPDATE ".$wpdb->prefix."ts_replication
					SET mylock = NULL
					WHERE replication_id = $rowId
				"
		);
	}

	function hn_ts_removeReplicationRecord(){
		global $wpdb;
		return $wpdb->query($wpdb->prepare(
				"
				DELETE FROM ".$wpdb->prefix."ts_replication
				WHERE replication_id = %d
				", $rowId
				)
		);
	}

	/**
	 * Returns the unit (mime type) from the metadata for a given measurement container
	 * @param $mc is a measurement container table name
	 */
	function hn_ts_getUnitForReplicationTable($mc){
		global $wpdb;
		return $wpdb->get_var($wpdb->prepare("SELECT unit FROM ".$wpdb->prefix."ts_metadata
		INNER JOIN ".$wpdb->prefix."ts_replication ON
		".$wpdb->prefix."ts_metadata.tablename = ".$wpdb->prefix."ts_replication.local_table
		WHERE local_table = '%s'", $mc));
	}
}/*

	$repls = $wpdb->get_results( 	$wpdb->prepare(
			";" )	);
			*/
?>
