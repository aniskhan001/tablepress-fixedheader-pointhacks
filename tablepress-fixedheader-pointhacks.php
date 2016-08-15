<?php

/*

Plugin Name: TablePress Extension: Point Hacks FixedHeader
Original Plugin URI: https://tablepress.org/extensions/datatables-fixedheader/
Description: TablePress Extension to add the DataTables FixedHeader functionality for Point Hacks site

Version: 1.0

Author: Anisuzzaman Khan
Author URI: https://aniskhan001.me/

Original Author: Tobias Bäthge
Original Author URI: https://tobias.baethge.com/

*/



/*

 * See http://datatables.net/extensions/fixedheader/

 */



/*

 * Shortcode:

 * [table id=123 datatables_fixedheader=top|bottom|both datatables_fixedheader_offsettop=60 /]

 */



// Prohibit direct script loading.

defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );


// Init TablePress_DataTables_FixedHeader.

add_action( 'tablepress_run', array( 'TablePress_DataTables_FixedHeader', 'init' ) );


/**

 * TablePress Extension: DataTables FixedHeader

 * @author Tobias Bäthge

 * @since 1.3

 */

class TablePress_DataTables_FixedHeader {



	/**

	 * Plugin slug.

	 *

	 * @var string

	 * @since 1.3

	 */

	protected static $slug = 'tablepress-datatables-fixedheader';



	/**

	 * Plugin version.

	 *

	 * @var string

	 * @since 1.3

	 */

	protected static $version = '1.0';


	/**

	 * Initialize the plugin by registering necessary plugin filters and actions.

	 *

	 * @since 1.3

	 */

	public static function init() {

		add_filter( 'tablepress_shortcode_table_default_shortcode_atts', array( __CLASS__, 'shortcode_table_default_shortcode_atts' ) );

		add_filter( 'tablepress_table_js_options', array( __CLASS__, 'table_js_options' ), 10, 3 );

		add_filter( 'tablepress_datatables_parameters', array( __CLASS__, 'datatables_parameters' ), 10, 4 );

	}


	/**

	 * Add "datatables_fixedheader" and related parameters to the [table /] Shortcode.

	 *

	 * @since 1.3

	 *

	 * @param array $default_atts Default attributes for the TablePress [table /] Shortcode.

	 * @return array Extended attributes for the Shortcode.

	 */

	public static function shortcode_table_default_shortcode_atts( $default_atts ) {

		$default_atts['datatables_fixedheader'] = '';

		$default_atts['datatables_fixedheader_offsettop'] = 0;

		return $default_atts;

	}



	/**

	 * Pass configuration from Shortcode parameters to JavaScript arguments.

	 *

	 * @since 1.3

	 *

	 * @param array  $js_options     Current JS options.

	 * @param string $table_id       Table ID.

	 * @param array  $render_options Render Options.

	 * @return array Modified JS options.

	 */

	public static function table_js_options( $js_options, $table_id, $render_options ) {

		$js_options['datatables_fixedheader'] = strtolower( $render_options['datatables_fixedheader'] );

		$js_options['datatables_fixedheader_offsettop'] = absint( $render_options['datatables_fixedheader_offsettop'] );



		// Change parameters and register files if the header or footer are fixed.

		if ( '' !== $js_options['datatables_fixedheader'] ) {

			// Scrolling must be turned off for the FixedHeader functionality.

			$js_options['datatables_scrollx'] = false;

			$js_options['datatables_scrolly'] = false;



			// Convert the "both" shortcut to "top" and "bottom".

			$js_options['datatables_fixedheader'] = str_replace( 'both', 'top,bottom', $js_options['datatables_fixedheader'] );



			// Register the JS files.

			$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

			$url = plugins_url( "js/dataTables.fixedHeader{$suffix}.js", __FILE__ );
			// $url = plugins_url( "js/dataTables.fixedHeader.js", __FILE__ );

			wp_enqueue_script( self::$slug, $url, array( 'tablepress-datatables' ), self::$version, true );



			// Add the common filter that adds JS for all calls on the page.

			if ( ! has_filter( 'tablepress_all_datatables_commands', array( __CLASS__, 'all_datatables_commands' ) ) ) {

				add_filter( 'tablepress_all_datatables_commands', array( __CLASS__, 'all_datatables_commands' ) );

			}

		}



		return $js_options;

	}



	/**

	 * Evaluate JS parameters and convert them to DataTables parameters.

	 *

	 * @since 1.3

	 *

	 * @param array  $parameters DataTables parameters.

	 * @param string $table_id   Table ID.

	 * @param string $html_id    HTML ID of the table.

	 * @param array  $js_options JS options for DataTables.

	 * @return array Extended DataTables parameters.

	 */

	public static function datatables_parameters( $parameters, $table_id, $html_id, $js_options ) {

		// Bail out early if neither header nor footer are fixed.

		if ( '' === $js_options['datatables_fixedheader'] ) {

			return $parameters;

		}



		/*

		 * Construct the DataTables FixedHeader config parameter.

		 * We use strpos() instead of string comparison for BC reasons, as previous versions supported a value like "top,left,right,bottom".

		 */

		$parameters['fixedHeader'] = array();

		// The header only needs to be set if changing the default of true (i.e. if it's not in the Shortcode parameter).

		if ( false === strpos( $js_options['datatables_fixedheader'], 'top' ) ) {

			$parameters['fixedHeader'][] = '"header":false';

		}

		// The footer only needs to be set if changing the default of false (i.e. if it's in the Shortcode parameter).

		if ( false !== strpos( $js_options['datatables_fixedheader'], 'bottom' ) ) {

			$parameters['fixedHeader'][] = '"footer":true';

		}

		// Possibly add an offset to the header.

		if ( 0 !== $js_options['datatables_fixedheader_offsettop'] ) {

			$parameters['fixedHeader'][] = '"headerOffset":' . absint( $js_options['datatables_fixedheader_offsettop'] );

		}

		$parameters['fixedHeader'] = '"fixedHeader":{' . implode( ',', $parameters['fixedHeader'] ) . '}';



		return $parameters;

	}





	/**

	 * Add jQuery code that adds the necessary CSS for the Extension, instead of  loading that CSS from a file on all pages.

	 *

	 * @since 1.3

	 *

	 * @param array $commands The JS commands for the DataTables JS library.

	 * @return array Modified JS commands for the DataTables JS library.

	 */

	public static function all_datatables_commands( $commands ) {

		$commands = "$('head').append('<style>.ph-fixedHeader-wrap{position:fixed;top:0;overflow:hidden;}</style>');\n" . $commands;

		return $commands;

	}



} // class TablePress_DataTables_FixedHeader

