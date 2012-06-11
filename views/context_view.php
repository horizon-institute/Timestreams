<?php
	function hn_ts_showContextTable(){
		?>
		<h3>Context Table</h3>
		<table class="widefat">
			<thead>
				<tr>
					<th>id</th>
					<th>Context Type</th>
					<th>Value</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th>id</th>
					<th>Context Type</th>
					<th>Value</th>
				</tr>
			</tfoot>
			<tbody>
				
			<?php 
			$db = new Hn_TS_Database();
			$rows = $db->hn_ts_select_context();
			if($rows){
				foreach ( $rows as $row )
				echo "<tr>
				<td>$row->context_id</td>
				<td>$row->name 	</td>
				<td>$row->value</td>
				</tr>";
			}?>
			</tbody>
		</table>
		<div class="tablenav">
			<div class="tablenav-pages">
				<span class="displaying-num">Displaying <?php echo count($rows);?> of <?php echo count($rows);?></span>
				<span class="page-numbers current">1</span>
				<?php //<a href="#" class="page-numbers">2</a> ?>
				<?php //<a href="#" class="next page-numbers">&raquo;</a> ?>
			</div>
		</div>
		<hr />
		<?php
	}
?>