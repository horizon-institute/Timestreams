<?php
	/**
	 * HTML form and processing code to add a Timestream 
	 * @change 01/11/2012 - Modified by JMB to only display datasources 
	 * owned by the current user or current blog
	 */
	function hn_ts_addTimestream()
	{
		$db = new Hn_TS_Database();
		//$datasources = $db->hn_ts_select('wp_ts_metadata');
		$datasources =  $db->hn_ts_select_viewable_metadata();
		
		?>
		<h3><?php _e('Create a new Timestream',HN_TS_NAME); ?></h3>			
			<form id="timestreamform" method="post" action="">
				<table class="form-table">
					<tr valign="top">
			        <th scope="row"><?php _e('Timestream Name',HN_TS_NAME); ?></th>
			        <td><input type="text" name="timestream_name" />
			        </td>
			        <th scope="row"><?php _e('Measurement Container',HN_TS_NAME); ?></th>
					<td>
						<select name="timestream_data" >
						<?php
						foreach($datasources as $meta)
						{
							if(!isset($meta->friendlyname) || $meta->friendlyname == ""){
								$meta->friendlyname = "unnamed";
							}
							echo "<option value=\"" . $meta->metadata_id . "\">" . "container: " . $meta->friendlyname . " | type: " . $meta->measurement_type . " | table: " . $meta->tablename . "</option>";
						}
						?>
						</select>
			        </td>
			        </tr>
			    </table>
			    
			    <p class="submit">
			    <input type="hidden" name='command' value='add'/>
			    <input type="submit" class="button-primary" value="<?php _e('Create Timestream',HN_TS_NAME) ?>" />
			    </p>
			
			</form>
			<hr />
		<?php
				
		if(isset($_POST["command"]))
		{
			$db = new Hn_TS_Database();
					
			if(!strcmp($_POST["command"], "add"))
			{
				if(isset($_POST["timestream_name"]) && isset($_POST["timestream_data"]))
				{
					$db->hn_ts_addTimestream($_POST["timestream_name"], $_POST["timestream_data"]);
				}
			}
			else if(!strcmp($_POST["command"], "update"))
			{
				if(isset($_POST["timestream_id"]) && isset($_POST["timestream_data"]))
				{
					$db->hn_ts_updateTimestream($_POST["timestream_id"], $_POST["timestream_data"]);
				}		
			}
			else if(!strcmp($_POST["command"], "delete"))
			{
				if(isset($_POST["timestream_id"]))
				{
					$db->hn_ts_deleteTimestream($_POST["timestream_id"]);
				}		
			}
		}
	}
?>