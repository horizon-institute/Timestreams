<?php

/**
 * Functions to display replication table content
 * Author: Jesse Blum (JMB)
 * Date: 2012
 * To do: Add search and edit functionality
 */

	/**
	 * Enqueue the javascript
	 */
	function hn_ts_doReplicationJSSccript($hook){
		global $hn_ts_admin_page_repl;

		if($hook != $hn_ts_admin_page_repl){
			return;
		}

		wp_enqueue_script('hn_ts_ajax_repl', plugin_dir_url(HN_TS_VIEWS_DIR).'js/hn_ts_ajax.js', array('jquery'));
		wp_localize_script('hn_ts_ajax_repl', 'hn_ts_ajax_repl_vars', array(
			'hn_ts_ajax_repl_nonce' => wp_create_nonce('hn_ts_ajax_repl-nonce')
		));
	}
	add_action('admin_enqueue_scripts', 'hn_ts_doReplicationJSSccript');

	/**
	 * Displays the success or failure of an attempt at table replication
	 */
	function hn_ts_ajax_repl_get_replication_results(){
		if(!isset($_POST["hn_ts_ajax_repl_nonce"]) || !wp_verify_nonce($_POST["hn_ts_ajax_repl_nonce"], 'hn_ts_ajax_repl-nonce')){
			die('Failed permissions check.');
		}
		if(!isset($_POST["hn_ts_ajax_repl_id"])){
			die('');
		}else{
			//echo hn_ts_replicate_full($_POST["hn_ts_ajax_repl_id"]);
			echo hn_ts_replicate_partial($_POST["hn_ts_ajax_repl_id"]);
		}
		die();
	}
	add_action('wp_ajax_hn_ts_get_replication_results','hn_ts_ajax_repl_get_replication_results');

	/**
	 * Displays replication table.
	 * To do: Complete pagination functionality.
	 */
	function hn_ts_showReplicationTable(){
		?>
		<h3>Replication Table</h3>
		<table class="widefat">
			<thead>
				<tr>
					<th><?php _e('id',HN_TS_NAME); ?></th>
					<th><?php _e('local table',HN_TS_NAME); ?></th>
					<th><?php _e('remote user',HN_TS_NAME); ?></th>
					<th><?php _e('remote url',HN_TS_NAME); ?></th>
					<th><?php _e('remote table',HN_TS_NAME); ?></th>
					<th><?php _e('continuous',HN_TS_NAME); ?></th>
					<th><?php _e('copy files',HN_TS_NAME); ?></th>
					<th><?php _e('last replication',HN_TS_NAME); ?></th>
					<th><?php _e('Replicate Now',HN_TS_NAME); ?></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th><?php _e('id',HN_TS_NAME); ?></th>
					<th><?php _e('local table',HN_TS_NAME); ?></th>
					<th><?php _e('remote user',HN_TS_NAME); ?></th>
					<th><?php _e('remote url',HN_TS_NAME); ?></th>
					<th><?php _e('remote table',HN_TS_NAME); ?></th>
					<th><?php _e('continuous',HN_TS_NAME); ?></th>
					<th><?php _e('copy files',HN_TS_NAME); ?></th>
					<th><?php _e('last_replication',HN_TS_NAME); ?></th>
					<th><?php _e('Replicate Now',HN_TS_NAME); ?></th>
				</tr>
			</tfoot>
			<tbody>
			<?php
			$db = new Hn_TS_Database();
			global $wpdb;
			global $current_user;
			$current_user = wp_get_current_user();
			$rows = $db->hn_ts_select($wpdb->prefix.'ts_replication'.
					" WHERE local_user_id = $current_user->ID");
			//echo("numrows");
			//var_dump($rows);
			if($rows){
				global $pagenow;
				$screen = get_current_screen();
				//<td></td>
				foreach ( $rows as $row ){
					$rowString = "<tr>
						<td>$row->replication_id</td>
						<td><a href=\"".$pagenow.
							"?page=timestreams/admin/interface.phpdatasources&table=
							$row->local_table\">$row->local_table</a></td>
						<td>$row->remote_user_login</td>
						<td>$row->remote_url</td>
						<td>$row->remote_table</td>";
						if ($row->continuous){
							$input="<td><input type=\"checkbox\" name=\"continuous\" checked=\"checked\" disabled=\"disabled\" />";
						}else{
							$input="<td><input type=\"checkbox\" name=\"continuous\" />";
						}
						$rowString .= $input;
						if ($row->copy_files){
							$input="<td><input type=\"checkbox\" name=\"hn_ts_copy_files\" checked=\"checked\" disabled=\"disabled\" />";
						}else{
							$input="<td><input type=\"checkbox\" name=\"hn_ts_copy_files\" />";
						}
						$rowString .= $input.
						"<td><div id=\"hn_ts_last_repl-$row->replication_id\">".
						"$row->last_replication</td></div><td>";
						if($row->continuous){
							$form="<form id=\"doReplicationform\" name=\"doReplicationForm\" method=\"POST\" action=\"\">
									<input id=\"hn_ts_rpl_submit\"
									type=\"submit\"
									name=\"rpl.$row->replication_id\"
									class=\"button-secondary\"
									value=\"Replicate\"
									disabled=\"disabled\"
									/>
							</form>";
						}else{
							$form="<form id=\"doReplicationform\" name=\"doReplicationForm\" method=\"POST\" action=\"\">
								<input id=\"hn_ts_rpl_submit\"
								type=\"submit\"
								name=\"rpl.$row->replication_id\"
								class=\"button-secondary\"
								value=\"Replicate\"
								/>
							</form>";
						};
						$rowString = $rowString.$form."<img id=\"hn_ts_rpl_loading-$row->replication_id\" src=\"".admin_url('/images/wpspin_light.gif')."\"
								class=\"waiting\" style=\"display:none;\" />
						</td>
					</tr>";
					echo $rowString;
				}
			}?>
			</tbody>
		</table>
		<div class="tablenav">
			<div class="tablenav-pages">
				<span class="displaying-num"><?php _e('Displaying ',HN_TS_NAME); ?><?php echo count($rows);?><?php _e(' of ',HN_TS_NAME); ?><?php echo count($rows);?></span>
				<span class="page-numbers current">1</span>
				<?php //<a href="#" class="page-numbers">2</a> ?>
				<?php //<a href="#" class="next page-numbers">&raquo;</a> ?>
			</div>
		</div>
		<hr />
		<?php

	}

