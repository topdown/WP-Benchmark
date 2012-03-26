<?php
/**
 * Benchmark - Profile plugin for WordPress
 *
 * PHP version 5 required
 *
 * Created 3/26/12, 2:14 AM
 *
 * @category   WordPress Plugin
 * @package    VW Benchmark - vw_benchmark.php
 * @author     Jeff Behnke <code@validwebs.com>
 * @copyright  2009-12 ValidWebs.com
 * @license    GPL MIT
 */

/*
Plugin Name: VW Benchmark
Plugin URI: https://github.com/topdown/WP-Benchmark
Description: <strong>(PHP 5+ is required)</strong> A quick benchmark utility for WordPress It will currently output Run Time, Query Count, Memory Usage, Included File count. It can also output all queries being run, query errors, constants, and included files. <strong>You shouldn't leave this active, there is no reason to, its purpose is for debugging.</strong> But if you do at least shut off all of the settings in the settings page for the plugin. <strong>Only admins can see the data from this plugin unless checked for everyone (Don't leave it checked).</strong>
Version: 1.0.0
Author: Jeff Behnke 
Author URI: http://validwebs.com
License: GPL MIT
 */
class vw_benchmark
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
	public $version = '1.0.0';

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
	public $plugin_slug = 'vw_bench';

	/**
	 * Initiate the plugin
	 */
	public function __construct()
	{

		// Set Plugin Path
		$this->plugin_path = dirname(__FILE__);

		if (is_admin())
		{
			// admin actions
			add_action('admin_menu', array(
				$this,
				$this->plugin_slug . '_menu'
			));

			add_filter('plugin_action_links', array(
				$this,
				$this->plugin_slug . '_plugin_action_links'
			), 10, 2);

			add_action('init', array(
				$this,
				'github_updater_init'
			));
		}
		else
		{
			add_action('wp_head', array(
				$this,
				'bench_css'
			));

			add_action('wp_footer', array(
				$this,
				$this->plugin_slug . 'mark_init'
			), 100);
		}

		$this->options = (array) get_option($this->plugin_slug . '_settings');

		if (in_array('queries', $this->options))
		{
			define('SAVEQUERIES', true);
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
	public function vw_bench_plugin_action_links($links, $file)
	{
		if ($file == 'vw_benchmark/vw_benchmark.php')
		{
			$settings_link = '<a href="options-general.php?page=' . $this->plugin_slug . '">' . __('Settings', $this->plugin_slug) . '</a>';
			array_unshift($links, $settings_link);
		}
		return $links;
	}

	// Insert the menu link
	public function vw_bench_menu()
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
				$this->plugin_slug . '_settings'
			)
		);
	}

	/**
	 * The code for the options page / settings
	 */
	public function vw_bench_settings()
	{
		global $current_user;

		get_currentuserinfo();
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
		<style type="text/css" scoped="">
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
				<label for="<?php echo $this->plugin_slug; ?>_settings">OutPut Text Color:</label>

				&nbsp;&nbsp;&nbsp;#
				<input type="text" id="<?php echo $this->plugin_slug; ?>_settings" name="<?php echo $this->plugin_slug; ?>_settings[color]" value="<?php echo $this->options['color']; ?>" />
				<br />
				<small>Just the HTML color code eg. ffffff for white.</small>
			</p>

			<p>
				<label for="<?php echo $this->plugin_slug; ?>_settings">Pre Block Background Color:</label>
				&nbsp;&nbsp;&nbsp;#
				<input type="text" id="<?php echo $this->plugin_slug; ?>_settings" name="<?php echo $this->plugin_slug; ?>_settings[bgcolor]" value="<?php echo $this->options['bgcolor']; ?>" />
				<br />
				<small>Just the HTML color code eg. ffffff for white.</small>
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
				<small>Filter out everything except wp-content directory.
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

		$color   = (isset($this->options['color'])) ? $this->options['color'] : 'ffffff';
		$bgcolor = (isset($this->options['bgcolor'])) ? $this->options['bgcolor'] : '000000';

		$css = <<<CSS
		<style type="text/css">
		#benchmark-pl {
			display: block;
			width: 1000px;
			margin: 10px auto;
			padding:5px;
			text-align: center;
			color: #$color;
			line-height: 24px;
		}
		.vw-bench-block-pl {
			display: block;
			width: 100%;
			padding: 10px;
			text-align: left;
			background: #$bgcolor;
			margin: 5px auto;
			overflow: auto;
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
	public function vw_benchmark_init()
	{
		global $current_user;

		get_currentuserinfo();
		if (!current_user_can('manage_options') && !in_array('logged_out', $this->options))
		{
			return;
		}

		/** @var $wpdb wpdb */
		global $wpdb, $timestart;

		echo '<div id="benchmark-pl"><strong>VW BenchMarkII</strong> &bull; ';

		echo $wpdb->num_queries . ' Queries &bull; ';

		$precision = 3;
		$mtime     = microtime();
		$mtime     = explode(' ', $mtime);
		$timeend   = $mtime[1] + $mtime[0];
		$timetotal = $timeend - $timestart;
		$r         = (function_exists('number_format_i18n')) ? number_format_i18n($timetotal, $precision) : number_format($timetotal, $precision);

		echo "Load Time: " . $r . ' seconds.';

		if (function_exists('memory_get_usage'))
		{
			echo " &bull; Memory Usage: " . round(memory_get_usage() / 1048576, 2) . " MiB<br />";
		}
		if (function_exists('memory_get_peak_usage'))
		{
			echo "Peak Memory Usage: " . round(memory_get_peak_usage() / 1048576, 6) . " MiB<br />";
		}

		echo "Included Files: " . count(get_included_files());
		if (ini_get('apc.enabled') == true)
		{
			echo ' &bull; APC Cache Enabled';
		}

		if (in_array('constants', $this->options) && current_user_can('manage_options'))
		{
			$constants = @get_defined_constants(1);
			echo '<div class="vw-bench-block-pl"><pre>';
			print_r($constants['user']);
			echo '</pre></div>';
		}

		if (in_array('errors', $this->options))
		{
			$wpdb->show_errors();
			echo '<div class="vw-bench-block-pl"><pre>';
			print_r($wpdb->print_error());
			echo "</pre></div>";
		}

		if (in_array('queries', $this->options))
		{
			define('SAVEQUERIES', true);

			echo '<div class="vw-bench-block-pl">
			<pre>';
			print_r($wpdb->queries);
			echo "</pre></div>";
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

			echo '<div class="vw-bench-block-pl">
			<pre>';
			print_r($includes);
			echo '</pre></div>';

		}

		if (in_array('hooks', $this->options))
		{
			echo '<div class="vw-bench-block-pl hooks">';
			$this->list_hooked_functions();
			echo "</div>";
		}

		echo '</div>';
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

			update_option($this->plugin_slug . '_settings', $_POST[$this->plugin_slug . '_settings']);

			echo '<div id="message" class="updated fade"><strong>VW Benchmark settings updated.</strong></div>';

			//We need to get the new settings so the form is updated properly
			$this->options = (array) get_option($this->plugin_slug . '_settings');
		}
	}

	/**
	 * Lists the available wp hooks
	 *
	 * @param bool $tag
	 * @return mixed
	 */
	private function list_hooked_functions($tag = false)
	{
		global $wp_filter;
		if ($tag)
		{
			$hook[$tag] = $wp_filter[$tag];
			if (!is_array($hook[$tag]))
			{
				trigger_error("Nothing found for '$tag' hook", E_USER_WARNING);
				return;
			}
		}
		else
		{
			$hook = $wp_filter;
			ksort($hook);
		}
		echo '<pre>';
		$i = 1;
		foreach ($hook as $tag => $priority)
		{
			echo "<br />=========================================================================================<br />" . $i++ . ". \t<strong style='color: red'>$tag</strong><br />=========================================================================================<br />";
			ksort($priority);
			foreach ($priority as $priority => $function)
			{
				echo "\t" . $priority;
				foreach ($function as $name => $properties) echo "\t$name<br />";
			}
		}
		echo '</pre>';
		return;

	}

	public function github_updater_init()
	{
		include_once('updater.php');

		define('WP_GITHUB_FORCE_UPDATE', true);

		if (is_admin())
		{ // note the use of is_admin() to double check that this is happening in the admin

			$config = array(
				'slug' => plugin_basename(__FILE__),
				'proper_folder_name' => 'WP-Git-Status',
				'api_url' => 'https://api.github.com/repos/topdown/WP-Git-Status/',
				'raw_url' => 'https://raw.github.com/topdown/WP-Git-Status/master',
				'github_url' => 'https://github.com/topdown/WP-Git-Status/',
				'zip_url' => 'https://github.com/topdown/WP-Git-Status/zipball/master',
				'sslverify' => true,
				'requires' => '3.0',
				'tested' => '3.3',
			);

			new WPGitHubUpdater($config);

		}
	}
}

$vw_benchmark = new vw_benchmark();
// End vw_benchmark.php