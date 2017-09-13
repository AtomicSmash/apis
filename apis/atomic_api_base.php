<?php

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;

class atomic_api_base {

    public $recordArray = array();
	public $totalRecords = 0;
	public $pageRecords = 0;
	public $resultsPerPage = 15;

	public function __construct($api_details) {
		global $wpdb;

        $this->api_details = $api_details;

		// $this->api_table = $this->api_details['db_table'];

        $this->columns = array(
				'thumbnail'    => 'Thumbnail',
	            'caption'      => 'Caption',
	            'added'      => 'Added'
			);

        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            WP_CLI::add_command( 'APssIs', function(){
                WP_CLI::success( $args[0] . ' ' . $assoc_args['append'] );
            } );
        };

        // $this->setupMenus();
        add_action( 'admin_menu', array( $this, 'setupMenus') );
		// add_action( 'api_hourly_sync',  array($this,'pull' ));
	}

	/**
	 * Setup table for API
	 * @return bool yet reurn isn't used
	 */
	function create_table() {

        echo "asdasd";

	}


	// GET Functions
	public function setupMenus() {

        add_submenu_page("tools.php", $this->api_details['name'].' API', $this->api_details['name'].' API', 'manage_options', 'atomic_apis_instagram', array($this,'apiListPage'));

	}


    public function apiListPage() {

        echo '<div class="wrap">';

			if( isset( $_GET['code'] ) ){

				echo "<h1>Instagram API setup</h1>";

				echo "<p>Please add this <strong>CODE</strong> constant to your config file. The actual access will be in the current url after '#access_token='</p>";

				echo "<pre>";
					echo "define('INSTAGRAM_ACCESS_TOKEN','xxxxxxxx');";
				echo "</pre>";

				echo "<p>Once this is in place, click here to sync <a href='".admin_url('tools.php?page=atomic_apis_instagram&sync=1')."' class='add-new-h2'>Sync Instagram</a></p>";

				// $redirect_url = admin_url('tools.php?page=atomic_apis_instagram&sync=1');

				// echo "<p>YES! We now have an access code!: ".$_GET['code'].". You now need an Access Token! Click here:<br><br>";


				// echo "<p><a href='https://api.instagram.com/oauth/authorize?client_id={$_GET['code']}&redirect_uri={$redirect_url}&scope=basic&response_type=code' class='add-new-h2'>Get access token</a></p>";

			}else if( !defined('INSTAGRAM_ACCESS_TOKEN') ){

				echo "<h1>Instagram API setup</h1>";

				echo "<ol style='font-size:18px;'>";
					echo "<li>First register an Instagram Application <a href='https://www.instagram.com/developer/'>here</a>. Make sure you use the url of this page as the 'Valid redirect URIs:' during registration.</li>";
					echo "<li>Once registered, go into the app and click the 'Security' tab. Uncheck 'Disable implicit OAuth', then Save.</li>";
					echo "<li>At this point you have a Client ID, enter below and hit submit.</li>";
					echo "<li>You will then be forwared to instagram to authorise. Press 'Authorize'.</li>";
				echo "</ol>";

				echo "<form action='https://api.instagram.com/oauth/authorize'>";

					echo "<table class='form-table'><tbody>";
						echo "<tr>";
							echo "<th scope='row'><label for='blogname'>Client ID</label></th>";
							echo "<td><input name='client_id' /></td>";
						echo "</tr>";
					echo "</tbody></table>";

					echo "<input type='submit' class='button button-primary' />";
					echo "<input name='redirect_uri' value='".admin_url('tools.php?page=atomic_apis_instagram&code=1')."' type='hidden' />";
					echo "<input name='scope' value='basic' type='hidden' />";
					echo "<input name='response_type' value='token' type='hidden' />";

				echo "</form>";

			}else{

				if(isset($_GET['sync'])){
					$this->pull();
				};

	            $entries = $this->get();


		    	$placeListTable = new Atomic_Api_List_Table_Instagram($this->columns);

	            echo '<h2>Instagram API <a href="tools.php?page=atomic_apis_instagram&sync=1" class="add-new-h2">Sync</a></h2>';

		    	$placeListTable->prepare_items();

	            $placeListTable->items = $this->recordArray;

	            //$placeListTable->items = $example_data;

				?>
				<form id="items-filter" method="get">
					<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
					<?php
					// Now we can render the completed list table
					$placeListTable->display();
	                ?>
				</form>
				<?php
			}

		echo '</div>';

    }


    public function get($query_args=array()) {


    }



	// public function cronUpdate() {
	// 	$this->pull();
	// }



	/**
	 * Call API for results, then process
	 * @return [type] [description]
	 */
    public function pull() {


	}


	/**
	 * Process return to see if it already exists
	 * @param  array  $entry [description]
	 * @return [type]        [description]
	 */
	public function processEntry($entry=array()) {

		if($this->exist($entry->id) == true){
			return $this->updateEntry($entry);
		}else{
			return $this->insertEntry($entry);
		}

	}

	/**
	 * Check to see if API entry exists
	 * @param  string $id API ID
	 * @return [bool] Returns whether the entry exists
	 */
	public function exist($id = "") {

		global $wpdb;

		$result = $wpdb->get_results ("SELECT id FROM ".$this->api_details['db_table']." WHERE id = '".$id."'");

		if (count ($result) > 0) {
			//$row = current ($result);
			return true;
		} else {
			return false;
		}

	}


    public function insertEntry($entry = array()) {


	}

	public function updateEntry($entry = array()) {


	}

	public function human_elapsed_time($datetime, $full = false) {
	    $now = new DateTime;
	    $ago = new DateTime($datetime);
	    $diff = $now->diff($ago);

	    $diff->w = floor($diff->d / 7);
	    $diff->d -= $diff->w * 7;

	    $string = array(
	        'y' => 'year',
	        'm' => 'month',
	        'w' => 'week',
	        'd' => 'day',
	        'h' => 'hour',
	        'i' => 'minute',
	        's' => 'second',
	    );
	    foreach ($string as $k => &$v) {
	        if ($diff->$k) {
	            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
	        } else {
	            unset($string[$k]);
	        }
	    }

	    if (!$full) $string = array_slice($string, 0, 1);
	    return $string ? implode(', ', $string) . ' ago' : 'just now';
	}

}







//Need to sort pagination

class Atomic_Api_List_Table extends WP_List_Table {

	// function __construct($columns = array()){
	//
    //     $this->columns = $columns;
	//
    //     parent::__construct( array(
	// 		'singular'  => 'item',  //singular name of the listed records
	// 		'plural'    => 'items', //plural name of the listed records
	// 		'ajax'      => false    //does this table support ajax?
	// 	) );
	//
	// }

	//Setup column defaults
	function column_default($item, $column_name){
        switch( $column_name ) {
			// case 'tweet':
            // case 'added_at':
            // case 'user_location':
            // return $item[ $column_name ];
			case 'thumbnail':
				return "<a href='".$item[ 'link' ]."' target='_blank'><img src='".$item[ 'size_150' ]."' /></a>";
			case 'caption':
				return $item[ $column_name ];
			case 'added':
				return $item[ 'human_time_ago' ];
			default:
    			return $item[ $column_name ]; //Show the whole array for troubleshooting purposes
        }
	}


	/**
	 * Prepare the items for the table to process
	 */
	function prepare_items() {
        //Get api items from Atomic_Api_Entry_List

        // $columns = $this->columns;
        // $hidden = array();
        // $sortable = array();
        // $this->_column_headers = array($columns, $hidden, $sortable);

		$columns = $this->get_columns();
		$hidden = array();
		// $sortable = $this->get_sortable_columns();
		$sortable = array();

		// Get the data
		$data = $this->table_data();
		// usort( $data, array( &$this, 'sort_data' ) );

		$items_per_page = 100;
		$currentPage = $this->get_pagenum();
		$total_items = count($data);

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $items_per_page
		) );

		// $data = array_slice( $data, ( ($currentPage - 1 ) * $items_per_page ), $items_per_page );
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items = $data;

	}

	/**
	 * Override the parent columns method. Defines the columns to use in your listing table
	 *
	 * @return Array $columns, the array of columns to use with the table
	 */
	function get_columns() {

		$columns = array(
			'thumbnail'    => 'Thumbnail',
            'caption'      => 'Caption',
            'added'      => 'Added'
		);

		return $columns;

	}

}
