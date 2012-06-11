<?php
	add_action('admin_menu', 'hn_ts_add_admin_menus');
	
	function hn_ts_add_admin_menus(){
	
		// To do replace administrator with a custom capability
		add_menu_page('Timestreams', 'Timestreams',
				'administrator', __FILE__, 'hn_ts_main_admin_page');
		add_submenu_page(__FILE__, 'Metadata', 'Metadata', 'manage_options',
				__FILE__.'metadata','hn_ts_metadata_admin_page');
		add_submenu_page(__FILE__, 'Context', 'Context', 'manage_options',
				__FILE__.'context','hn_ts_context_admin_page');
	}
	
	function hn_ts_main_admin_page(){
		?>
		<div class="wrap">
			<div id="icon-index" class="icon32"></div>
			<h2>Timestreams</h2>
			<h3>Description</h3>
			<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed bibendum elementum sapien, et porttitor enim faucibus at. Sed ut nulla sed turpis dapibus luctus vel non ante. Nunc adipiscing venenatis dui. Morbi vehicula volutpat ornare. Sed non magna id lectus pretium aliquam vitae at velit. Vestibulum posuere pharetra ornare. Pellentesque quis tortor enim, ac molestie urna. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent dignissim augue et urna egestas a sagittis est bibendum. Donec sagittis congue consectetur. Morbi lacinia erat vitae nisl auctor commodo. Donec ut magna id est pretium laoreet. Aenean vitae auctor ligula. Nulla facilisi. Cras ac lorem lacinia justo molestie aliquam varius id ligula. </p>
			<hr />
			<h3>Data Sources</h3>
			<hr />
			<p />
		</div>
		<?php
	}
	
	function hn_ts_metadata_admin_page(){
		?>
		<div class="wrap">
			<div id="icon-themes" class="icon32"></div>
			<h2>Timestreams - Metadata</h2>
			<h3>Description</h3>
			<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed bibendum elementum sapien, et porttitor enim faucibus at. Sed ut nulla sed turpis dapibus luctus vel non ante. Nunc adipiscing venenatis dui. Morbi vehicula volutpat ornare. Sed non magna id lectus pretium aliquam vitae at velit. Vestibulum posuere pharetra ornare. Pellentesque quis tortor enim, ac molestie urna. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent dignissim augue et urna egestas a sagittis est bibendum. Donec sagittis congue consectetur. Morbi lacinia erat vitae nisl auctor commodo. Donec ut magna id est pretium laoreet. Aenean vitae auctor ligula. Nulla facilisi. Cras ac lorem lacinia justo molestie aliquam varius id ligula. </p>
			<hr />
		<?php
			hn_ts_showMetadataTable();
			hn_ts_addMetadataRecord();
		?>
		</div>
		<?php
	}
	
	function hn_ts_context_admin_page(){
		?>
		<div class="wrap">
			<div id="icon-edit-pages" class="icon32"></div>
			<h2>Timestreams - Context</h2>
			<h3>Description</h3>
			<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed bibendum elementum sapien, et porttitor enim faucibus at. Sed ut nulla sed turpis dapibus luctus vel non ante. Nunc adipiscing venenatis dui. Morbi vehicula volutpat ornare. Sed non magna id lectus pretium aliquam vitae at velit. Vestibulum posuere pharetra ornare. Pellentesque quis tortor enim, ac molestie urna. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent dignissim augue et urna egestas a sagittis est bibendum. Donec sagittis congue consectetur. Morbi lacinia erat vitae nisl auctor commodo. Donec ut magna id est pretium laoreet. Aenean vitae auctor ligula. Nulla facilisi. Cras ac lorem lacinia justo molestie aliquam varius id ligula. </p>
			<hr />
		<?php
			hn_ts_showContextTable();
			hn_ts_addContextRecord();
		?>
		</div>
		<?php
	}
?>