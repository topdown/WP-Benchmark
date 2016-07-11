<?php

/*
Plugin Name: WP Benchmark
Plugin URI: https://github.com/topdown/WP-Benchmark
Description: <strong>(PHP 5+ is required)</strong> A quick benchmark utility for WordPress It will currently output Run Time, Query Count, Memory Usage, Included File count. It can also output all queries being run, query errors, constants, and included files. <strong>You shouldn't leave this active, there is no reason to, its purpose is for debugging.</strong> But if you do at least shut off all of the settings in the settings page for the plugin. <strong>Only admins can see the data from this plugin unless checked for everyone (Don't leave it checked).</strong>
Version: 1.0.1
Author: Jeff Behnke
Author URI: http://validwebs.com
License: GPL MIT
*/

/*  Copyright 2012  Jeff Behnke  (email : code@validwebs.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Benchmark - Profile plugin for WordPress
 *
 * PHP version 5 required
 *
 * Created 3/26/12, 2:14 AM
 *
 * @category   WordPress Plugin
 * @package    VW Benchmark - wp_benchmark.php
 * @author     Jeff Behnke <code@validwebs.com>
 * @copyright  2009-12 ValidWebs.com
 * @license    GPL MIT
 */
class wp_benchmark
{

	/**
	 * Our plugin path
	 *
	 * @var string
	 */
	public $plugin_path;


	/**
	 * Our plugin version
	 *
	 * @var string
	 */
	public $version = '1.0.1';

	/**
	 * Holds the options array
	 *
	 * @var array
	 */
	private $options = array();

	/**
	 * This holds the plugins slug used for the option name and everywhere else a slug is required
	 *
	 * @var string
	 */
	public $plugin_slug = 'wp_bench';

	/**
	 * Initiate the plugin
	 */
	public function __construct()
	{

		// Set Plugin Path
		$this->plugin_path = dirname(__FILE__);

		$this->options = (array) get_option($this->plugin_slug . '_settings');

		if (is_admin())
		{
			// admin actions
			add_action('admin_menu', array(
				$this,
				'wp_bench_menu'
			));

			add_filter('plugin_action_links', array(
				$this,
				'wp_bench_plugin_action_links'
			), 10, 2);

			if (in_array('admin', $this->options))
			{
				add_action('admin_head', array(
					$this,
					'bench_css'
				));

				add_action('shutdown', array(
					$this,
					'wp_benchmark_init'
				), 100);
			}
		}
		else
		{
			add_action('wp_head', array(
				$this,
				'bench_css'
			));

			add_action('wp_footer', array(
				$this,
				'wp_benchmark_init'
			), 100);

		}

		if (in_array('queries', $this->options))
		{
			if( ! defined( 'SAVEQUERIES' ) ) {
				define('SAVEQUERIES', true);
			}
		}
	}

	/**
	 * Inserts a link to the settings page on the Admin Plugin page where the plugin is installed or removed
	 *
	 * @param array  $links
	 * @param string $file
	 *
	 * @return array
	 */
	public function wp_bench_plugin_action_links($links, $file)
	{

		if ($file == 'WP-Benchmark/wp_benchmark.php')
		{
			$settings_link = '<a href="options-general.php?page=' . $this->plugin_slug . '">' . __('Settings', $this->plugin_slug) . '</a>';
			array_unshift($links, $settings_link);
		}

		return $links;
	}

	// Insert the menu link
	public function wp_bench_menu()
	{

		//create new menu
		//( $page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = NULL )
		add_options_page(
			'VW Bench Settings',
			'VW Benchmark',
			'administrator',
			$this->plugin_slug,
			array(
				$this,
				'wp_bench_settings'
			)
		);
	}

	/**
	 * The code for the options page / settings
	 */
	public function wp_bench_settings()
	{

		global $current_user;

		wp_get_current_user();
		if (!current_user_can('manage_options'))
		{
			die();
		}

		$this->handle_post();
		?>

	<div class="wrap">

		<div id="icon-tools" class="icon32"></div>
		<h2>VW BenchMark II</h2>

		<p>Version: <?php echo $this->version; ?></p>

		<p>The settings below will reflect what is available in the sites footer for admins.</p>

		<p><strong>Do not leave these setting set all of the time with the exception to normal profile Eg.The counts..
			They are for debugging only.</strong></p>

		<p>Generally when debugging you will only have one type selected at a time.</p>
		<style type="text/css">
			#message {
				padding: 10px;
			}

			form p {
				display:     block;
				width:       460px;
				padding:     5px 10px;
				line-height: 20px;
				clear:       both;
			}

			form label {
				display:     inline-block;
				float:       left;
				font-weight: bold;
			}

			form input {
				display: inline-block;
				float:   right;
			}
		</style>
		<form action="<?php print $_SERVER['PHP_SELF'];?>?page=<?php echo $this->plugin_slug; ?>" method="post">

			<?php wp_nonce_field('update-options');?>
			<h3>Benchmark WP Debug Options</h3>

			<p>
				<label for="<?php echo $this->plugin_slug; ?>_settings">Show in Admin Footer:</label>
				<?php if (in_array('admin', $this->options))
			{
				?>
				<input type="checkbox" id="<?php echo $this->plugin_slug; ?>_settings" name="<?php echo $this->plugin_slug; ?>_settings[admin]" value="admin" checked="checked" /><?php
			}
			else
			{
				?>
				<input type="checkbox" id="<?php echo $this->plugin_slug; ?>_settings" name="<?php echo $this->plugin_slug; ?>_settings[admin]" value="admin" /><?php
			}?>
				<br />
				<small>Shows WP Benchmark in the Admin panel also.</small>
			</p>

			<p>
				<label for="<?php echo $this->plugin_slug; ?>_settings">Show WP Queries:</label>
				<?php if (in_array('queries', $this->options))
			{
				?>
				<input type="checkbox" id="<?php echo $this->plugin_slug; ?>_settings" name="<?php echo $this->plugin_slug; ?>_settings[queries]" value="queries" checked="checked" /><?php
			}
			else
			{
				?>
				<input type="checkbox" id="<?php echo $this->plugin_slug; ?>_settings" name="<?php echo $this->plugin_slug; ?>_settings[queries]" value="queries" /><?php
			}?>
				<br />
				<small>Show an array of WordPress Queries.
					<br />
					This may not catch all, but it will get most.
				</small>
			</p>

			<p>
				<label for="<?php echo $this->plugin_slug; ?>_settings">Show WP Database Query Errors:</label>
				<?php if (in_array('errors', $this->options))
			{
				?>
				<input type="checkbox" id="<?php echo $this->plugin_slug; ?>_settings" name="<?php echo $this->plugin_slug; ?>_settings[errors]" value="errors" checked="checked" /><?php
			}
			else
			{
				?>
				<input type="checkbox" id="<?php echo $this->plugin_slug; ?>_settings" name="<?php echo $this->plugin_slug; ?>_settings[errors]" value="errors" /><?php
			}?>
				<br />
				<small>These errors can be cryptic but it can still be useful for debugging.</small>
			</p>

			<p>
				<label for="<?php echo $this->plugin_slug; ?>_settings">Show Constants:</label>
				<?php if (in_array('constants', $this->options))
			{
				?>
				<input type="checkbox" id="<?php echo $this->plugin_slug; ?>_settings" name="<?php echo $this->plugin_slug; ?>_settings[constants]" value="constants" checked="checked" /><?php
			}
			else
			{
				?>
				<input type="checkbox" id="<?php echo $this->plugin_slug; ?>_settings" name="<?php echo $this->plugin_slug; ?>_settings[constants]" value="constants" /><?php
			}?>
				<br />
				<small>Show an array of constants under the 'user' => array().</small>
			</p>

			<p>
				<label for="<?php echo $this->plugin_slug; ?>_settings">Show Includes:</label>
				<?php if (in_array('includes', $this->options))
			{
				?>
				<input type="checkbox" id="<?php echo $this->plugin_slug; ?>_settings" name="<?php echo $this->plugin_slug; ?>_settings[includes]" value="includes" checked="checked" /><?php
			}
			else
			{
				?>
				<input type="checkbox" id="<?php echo $this->plugin_slug; ?>_settings" name="<?php echo $this->plugin_slug; ?>_settings[includes]" value="includes" /><?php
			}?>
				<br />
				<small>Shows all includes within the web directory.</small>
			</p>

			<p>
				<label for="<?php echo $this->plugin_slug; ?>_settings">Includes Filter 1:</label>
				<?php if (in_array('filter1', $this->options))
			{
				?>
				<input type="checkbox" id="<?php echo $this->plugin_slug; ?>_settings" name="<?php echo $this->plugin_slug; ?>_settings[filter1]" value="filter1" checked="checked" /><?php
			}
			else
			{
				?>
				<input type="checkbox" id="<?php echo $this->plugin_slug; ?>_settings" name="<?php echo $this->plugin_slug; ?>_settings[filter1]" value="filter1" /><?php
			}?>
				<br />
				<small>Filter out wp-includes directory.
					<br />
					<strong>Show Includes must be checked.</strong></small>
			</p>

			<p>
				<label for="<?php echo $this->plugin_slug; ?>_settings">Includes Filter 2:</label>
				<?php if (in_array('filter2', $this->options))
			{
				?>
				<input type="checkbox" id="<?php echo $this->plugin_slug; ?>_settings" name="<?php echo $this->plugin_slug; ?>_settings[filter2]" value="filter2" checked="checked" /><?php
			}
			else
			{
				?>
				<input type="checkbox" id="<?php echo $this->plugin_slug; ?>_settings" name="<?php echo $this->plugin_slug; ?>_settings[filter2]" value="filter2" /><?php
			}?>
				<br />
				<small>Filter out everything except wp-content directory, shows theme and plugin includes.
					<br />
					<strong>Show Includes must be checked.</strong></small>
			</p>

			<p>
				<label for="<?php echo $this->plugin_slug; ?>_settings">Show Hooks:</label>
				<?php if (in_array('hooks', $this->options))
			{
				?>
				<input type="checkbox" id="<?php echo $this->plugin_slug; ?>_settings" name="<?php echo $this->plugin_slug; ?>_settings[hooks]" value="hooks" checked="checked" /><?php
			}
			else
			{
				?>
				<input type="checkbox" id="<?php echo $this->plugin_slug; ?>_settings" name="<?php echo $this->plugin_slug; ?>_settings[hooks]" value="hooks" /><?php
			}?>
				<br />
				<small>Outputs a list of hooks being loaded.</small>
			</p>

			<p>
				<label for="<?php echo $this->plugin_slug; ?>_settings">View Logged Out:
				</label>
				<?php if (in_array('logged_out', $this->options))
			{
				?>
				<input type="checkbox" id="<?php echo $this->plugin_slug; ?>_settings" name="<?php echo $this->plugin_slug; ?>_settings[logged_out]" value="logged_out" checked="checked" /><?php
			}
			else
			{
				?>
				<input type="checkbox" id="<?php echo $this->plugin_slug; ?>_settings" name="<?php echo $this->plugin_slug; ?>_settings[logged_out]" value="logged_out" /><?php
			}?>
				<br />
				<strong style="color: red;">Caution with the "View Logged Out" checked everyone will see the output,
					this is for DEBUGGING ONLY!</strong>
			</p>

			<p class="submit">
				<input type="hidden" name="action" value="save">
				<input type="submit" name="save" class="button-primary" value="Save" />
			</p>

		</form>
	</div>
	<?php

	}


	/**
	 * Our CSS for the profiling blocks
	 */
	public function bench_css()
	{

		$css = <<<CSS
<style>
		#bench-wrap {
			display: block;
			position: relative;
			width: 100%;
			bottom: 0;
			margin: 0;
			padding: 0;
			color: #fff;
			background: #222;
			font-size: 13px;
			font-family: arial, sans-serif;
			text-align: left;
		}

		#benchmark-pl {
			position:relative;
			display: block;
			margin: 0 auto;
			padding: 8px 0;
			line-height: 18px;
		}

		span.mark-bl {
			display: inline-block;
			padding: 0 5px;
			margin: 0 0 0 5px;
		}

		.vw-bench-block-pl {
			display: block;
			width: auto;
			text-align: left;
			background: #333;
			margin: 8px auto 0 auto;
			padding: 0;
			overflow: auto;
		}
		
		.vw-bench-single-error {
			padding-left: 30px;
			margin-bottom: 15px;
			color: #ffff00;
		}
		
		.vw-bench-error {
			color: #ff6666;
		}

		.vw-bench-block-pl pre, .wp-queries p, .wp-queries div {
			display: block;
			width: 90%;
			margin: 5px auto;
		}

		.wp-queries span.bold {
			font-weight: bold;
			color: #fff;
		}

		.wp-queries .time {
			color: #ff6666;
		}

		.wp-queries .count {
			position:relative;
			top: 24px;
			display: inline-block;
			width: 40px;
			margin-left: 25px;
			color: #ffff00;
			font-weight: bold;
		}

		.wp-queries .functions {
			color: #00ff00;
		}

		.wp-queries .query {
			color: #00ffff;
		}

		.wp-queries .output {
			display: block;
			width: 100%;
			margin: 0;
			padding: 10px 0;
			border-top: 1px solid #555;
			border-bottom: 1px solid #000;
		}

		.wp-queries .output p, .wp-queries .output div {
			line-height: 20px;
		}

		.wp-queries .fleft {
			display: inline-block;
			float: left;
			width: 5%;
		}

		.vw-bench-block-pl code {
			background: #222;
			color: #ff6666;
		}

		.vw-bench-block-pl .wpdberror {
			padding: 0;
			margin: 0;
		}

		.clear {
		clear: both;
		}
</style>
CSS;
		echo $css;
	}

	/**
	 * Initiate the benchmarks
	 *
	 * @return mixed
	 */
	public function wp_benchmark_init()
	{

		global $current_user;

		wp_get_current_user();
		if (!current_user_can('manage_options') && !in_array('logged_out', $this->options))
		{
			return;
		}

		/** @var $wpdb wpdb */
		global $wpdb, $timestart;

		$return = '<div id="bench-wrap"><div id="benchmark-pl"><span class="mark-bl"><strong>WP BenchMarkII</strong> &raquo;</span>';

		$return .= '<span class="mark-bl">' . $wpdb->num_queries . ' Queries</span>';

		$precision = 3;
		$mtime     = microtime();
		$mtime     = explode(' ', $mtime);
		$timeend   = $mtime[1] + $mtime[0];
		$timetotal = $timeend - $timestart;
		$r         = (function_exists('number_format_i18n')) ? number_format_i18n($timetotal, $precision) : number_format($timetotal, $precision);

		$return .= '<span class="mark-bl">Load Time: ' . $r . ' seconds.</span>';

		if (function_exists('memory_get_usage'))
		{
			$return .= '<span class="mark-bl">Memory Usage: ' . round(memory_get_usage() / 1048576, 2) . ' MiB</span>';
		}
		if (function_exists('memory_get_peak_usage'))
		{
			$return .= '<span class="mark-bl">Peak Memory Usage: ' . round(memory_get_peak_usage() / 1048576, 6) . ' MiB</span>';
		}

		$return .= '<span class="mark-bl">Included Files: ' . count(get_included_files()) . '</span>';
		if (ini_get('apc.enabled') == true)
		{
			$return .= '<span class="mark-bl">APC Cache Enabled</span>';
		}

		$return .= '<span class="mark-bl">Hooks: ' . $this->list_hooked_functions(false, true) . '</span>';
		$return .= '<span class="mark-bl">Template: ' . $this->show_template() . '</span>';

		echo $return;
		unset($return);

		if (in_array('constants', $this->options) && current_user_can('manage_options'))
		{
			$constants = @get_defined_constants(1);

			echo '<div class="vw-bench-block-pl wp-queries">';
			foreach ($constants['user'] as $key => $val)
			{
				$return = '<div class="output">';
				$return .= '<p style="float: left; width: 25%; margin-left: 40px;">' . $key . '</p>';
				$return .= '<p class="query">' . $val . '</p>';
				$return .= '<div class="clear"></div></div>';
				echo $return;
			} // end foreach
			unset($inc);
			echo '</div>';
		}

		if (in_array('errors', $this->options))
		{
			global $EZSQL_ERROR;
			if( ! empty( $EZSQL_ERROR ) ) {
				echo '<div class="vw-bench-block-pl"><pre>';
				echo '<h3>WordPress Database Errors: </h3>';
				foreach( $EZSQL_ERROR as $error ) {
					echo '<div class="vw-bench-single-error">';
					printf( '<p>Query: %s</p><p class="vw-bench-error">Error: %s</p>', $error['query'], $error['error_str'] );
					echo '</div>';
				}				
				echo "</pre></div>";
			}
		}

		if (in_array('queries', $this->options))
		{
			if (!defined('SAVEQUERIES'))
			{
				define('SAVEQUERIES', true);
			}

			echo '<div class="vw-bench-block-pl wp-queries">';

			$q = 0;

			if (sizeof($wpdb->queries))
			{
				//print_r($wpdb->queries);
				foreach ($wpdb->queries as $queries)
				{
					$return = '<div class="output">';
					$return .= '<span class="count">' . ++$q . '</span>';
					$return .= '<p class="query"><span class="bold">Query</span>: ' . $queries[0] . '</p>';
					$return .= '<p class="time"><span class="bold">Time</span>: ' . $queries[1] . '</p>';
					$return .= '<p class="functions"><span class="bold">Functions</span>: ' . $queries[2] . '</p>';
					$return .= '</div>';

					echo $return;
				} // end foreach
				unset($queries);
			}

			echo "</div>";
		}

		if (in_array('includes', $this->options))
		{
			$includes   = get_included_files();
			$extras     = array();
			$wp_content = array();

			if (in_array('filter1', $this->options))
			{
				foreach ($includes as $inc)
				{
					if (!strpos($inc, 'wp-includes'))
					{
						$extras[] = $inc;
					}

				} // end foreach
				unset($inc);
				$includes = $extras;
			}

			if (in_array('filter2', $this->options))
			{
				foreach ($includes as $inc)
				{
					if (strpos($inc, 'wp-content'))
					{
						$wp_content[] = $inc;
					}

				} // end foreach
				unset($inc);
				$includes = $wp_content;
			}

			$i = 0;
			echo '<div class="vw-bench-block-pl wp-queries">';
			foreach ($includes as $inc)
			{
				$return = '<div class="output">';
				$return .= '<span class="count">' . ++$i . '</span>';
				$return .= '<p class="query"><span class="bold">Include Path</span>: ' . $inc . '</p>';
				$return .= '</div>';
				echo $return;
			} // end foreach
			unset($inc);
			echo '</div>';

		}

		if (in_array('hooks', $this->options))
		{
			echo '<div class="vw-bench-block-pl hooks wp-queries">';
			$this->list_hooked_functions();
			echo "</div>";
		}

		echo '</div></div>';
	}

	/**
	 * Deal with the post and handle it accordingly
	 */
	private function handle_post()
	{

		if (isset($_POST['save']) && $_POST['action'] == 'save')
		{
			if (!wp_verify_nonce($_REQUEST['_wpnonce'], "update-options"))
			{
				die("Security Check");
			}
			$post = (isset($_POST[$this->plugin_slug . '_settings'])) ? $_POST[$this->plugin_slug . '_settings'] : '';
			if(empty($post))
			{
				delete_option($this->plugin_slug . '_settings', $post);
			}
			else
			{
				update_option($this->plugin_slug . '_settings', $post);
			}

			echo '<div id="message" class="updated fade"><strong>VW Benchmark settings updated.</strong></div>';

			//We need to get the new settings so the form is updated properly
			$this->options = (array) get_option($this->plugin_slug . '_settings');
		}
	}

	/**
	 * Lists the available wp hooks
	 *
	 * @param bool $tag
	 *
	 * @param bool $count
	 *
	 * @return mixed
	 */
	private function list_hooked_functions($tag = false, $count = false)
	{

		global $wp_filter;
		if ($tag)
		{
			$hook[$tag] = $wp_filter[$tag];
			if (!is_array($hook[$tag]))
			{
				trigger_error("Nothing found for '$tag' hook", E_USER_WARNING);

				return true;
			}
		}
		else
		{
			$hook = $wp_filter;
			ksort($hook);
		}

		$i = 0;

		if ($count)
		{
			foreach ($hook as $tag => $priority)
			{
				$count = ++$i;
			}

			return $count;
		}
		else
		{
			foreach ($hook as $tag => $priority)
			{
				$return = '<div class="output">';
				$return .= '<span class="count">' . ++$i . '</span>';
				$return .= "<p class=\"time\">$tag</p>";
				echo $return;
				ksort($priority);
				foreach ($priority as $pr => $function)
				{
					echo '<div class="query"><span class="fleft bold">' . $pr . ' </span>';
					foreach ($function as $name => $properties)
					{
						echo $name;
					}
					echo '</div><div class="clear"></div>';
				}
				echo '</div>';
			}

			return true;
		}
	}


	/**
	 * @return bool|mixed|string|void
	 */
	private function show_template()
	{

		global $template;

		$template = strstr($template, 'themes/');
		$template = str_replace('themes/', '', $template);

		return $template;
	}
}

$wp_benchmark = new wp_benchmark();
// End wp_benchmark.php
