<?php

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;

class atomic_api_instagram extends atomic_api_base {

	public $recordArray = array();
	public $totalRecords = 0;
	public $pageRecords = 0;
	public $resultsPerPage = 15;

	// Class Constructor
	public function __construct($api_details) {

		global $wpdb;

		parent::__construct( $api_details );

	}

	/**
	 * Setup table for API
	 * @return bool yet reurn isn't used
	 */
	function create_table() {

		wp_schedule_event( time(), 'hourly', 'api_hourly_sync' );

    	global $wpdb;
    	$charset_collate = $wpdb->get_charset_collate();

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );


        $table_name = $this->api_details['db_table'];
        $sql = "CREATE TABLE $table_name (
            id varchar(100) NOT NULL,
            caption text,
            type varchar(30) NOT NULL,
			link varchar(130) NOT NULL,
			size_150 varchar(200) NOT NULL,
			size_320 varchar(200) NOT NULL,
			size_640 varchar(200) NOT NULL,
            added_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP NOT NULL,
            updated_at TIMESTAMP NOT NULL,
            hidden BOOLEAN NOT NULL,
    		UNIQUE KEY id (id)
    	) $charset_collate;";

        dbDelta( $sql );

		return true;

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


		    	$placeListTable = new Atomic_Api_List_Table_Instagram($api_details['columns']);

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
        global $wpdb;

        $default_args['results_per_page'] = $this->resultsPerPage;
        $default_args['page'] = 1;
        $default_args['keyword'] = '';
        $default_args['orderby'] = 'id';
        $default_args['order'] = 'desc';



        // Merge query args with defaults, keeping only items that have keys in defaults
        $query_args = array_intersect_key($query_args + $default_args, $default_args);

        // Pagination
        $this->resultsPerPage = $query_args['results_per_page'];

        $firstResult = (int)($query_args['page']-1) * $this->resultsPerPage;

        $whereSqlLines = array();
        $extra_join = array();
        $groupSql = '';

        $fields = "*";

        $mainSql  = "SELECT " . $fields . " FROM " . $this->api_details['db_table'] . " l " . implode(' ',$extra_join);

        $countSql = "SELECT count(l.question_group_id) FROM " . $this->api_details['db_table'] .  " l ";
        if ($this->resultsPerPage>0) {
            $limitSql = $wpdb->prepare("LIMIT %d,%d ", $firstResult, $this->resultsPerPage);
        } else {
            $limitSql = "";
        }

        //$whereSqlLines[] = $wpdb->prepare("l.somthing='%d'", $query_args['id']);
        //$whereSqlLines[] = "";

        // Text search filter
        if ($query_args['keyword']) {
            $search_terms = explode(' ',$query_args['keyword']);
            foreach ($search_terms as $search_term) {
                if (is_numeric($search_term)) {
                    $innerWhere[] = $wpdb->prepare( "(l.field1 LIKE '%s' OR l.field2 LIKE '%s' OR l.field3 = %d)",
                        '%' . $search_term . '%',
                        '%' . $search_term . '%',
                        $search_term );
                } else {
                    $innerWhere[] = $wpdb->prepare( "(l.field1 LIKE '%s' OR l.field2 LIKE '%s')",
                        '%' . $search_term . '%',
                        '%' . $search_term . '%');
                }
            }
            $whereSqlLines[] = '(' . implode(" OR ", $innerWhere) . ')';
        }

        $whereSql = "";
        if ($whereSqlLines) {
			echo "where triggered";
            $whereSql = 'WHERE ' . implode(' AND ',$whereSqlLines) . ' ';
        }

        // Sort Order
        $orderSql = 'ORDER BY ' . $query_args['orderby'] . ' ' . strtoupper($query_args['order']) . ' ';

        $fullSql = $mainSql . ' ' . $whereSql . ' ' . $groupSql . ' ' . $orderSql . ' ' .$limitSql;

		// echo $whereSql."<br>";
		//echo $fullSql;
		//
		// echo "<pre>";
		// print_r($whereSqlLines);
		// echo "</pre>";



		$wpdb->show_errors();
        $this->recordArray = $wpdb->get_results($fullSql , 'ARRAY_A');
		// $this->recordArray = $wpdb->get_results($fullSql);


		if(count($this->recordArray) > 0){
			foreach($this->recordArray as $key => $tweet){
				$this->recordArray[$key]['human_time_ago'] = $this->human_elapsed_time($this->recordArray[$key]['added_at']);
			}
		}

		return $this->recordArray;

        $this->pageRecords = count($this->recordArray);

		// If we have fewer than the max records on the first page, we can use that as the total
		if ($page=0 && ($this->pageRecords < $this->resultsPerPage)) {
			$this->totalRecords = $this->pageRecords;
		} else {
			// Otherwise we need to work it out
			$this->totalRecords = $wpdb->get_var($countSql . $whereSql);
		}

    }


	/**
	 * Call API for results, then process
	 * @return [type] [description]
	 */
    public function pull() {

		$client = new Client();


		$response = $client->get('https://api.instagram.com/v1/users/self/media/recent', [
		    'query' => [
		        'access_token' => INSTAGRAM_ACCESS_TOKEN
		    ]
		]);

		$results = $response->getBody()->getContents();


		$results = json_decode($results);

		foreach ($results->data as $key => $entry) {

			$this->processEntry($entry);

		};

	}





    public function insertEntry($entry = array()) {

		global $wpdb;
		$wpdb->show_errors();

		$wpdb->insert($this->api_details['db_table'],
			array(
				'id' => html_entity_decode(stripslashes($entry->id), ENT_QUOTES),										// d
				'caption' => html_entity_decode(stripslashes($entry->caption->text), ENT_QUOTES),						// s
				'type' => html_entity_decode(stripslashes($entry->type), ENT_QUOTES),									// s
				'added_at' => date( "Y-m-d H:i:s", $entry->created_time),												// s
				'created_at' => date( "Y-m-d H:i:s", time()),															// s
				'updated_at' => date( "Y-m-d H:i:s", time()),															// s
				'link' => html_entity_decode(stripslashes($entry->link), ENT_QUOTES),									// s
				'size_150' => html_entity_decode(stripslashes($entry->images->thumbnail->url), ENT_QUOTES),				// s
				'size_320' => html_entity_decode(stripslashes($entry->images->low_resolution->url), ENT_QUOTES),		// s
				'size_640' => html_entity_decode(stripslashes($entry->images->standard_resolution->url), ENT_QUOTES),	// s
				'hidden' => 0,																							// d
			),
			array(
				'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d'
			)
		);

		return "added";
	}

	public function updateEntry($entry = array()) {

		global $wpdb;
		$wpdb->show_errors();

		$wpdb->update($this->api_details['db_table'],
			array(
				'caption' => html_entity_decode(stripslashes($entry->caption->text), ENT_QUOTES),						// s
				'type' => html_entity_decode(stripslashes($entry->type), ENT_QUOTES),									// s
				'updated_at' => date( "Y-m-d H:i:s", time()),															// s
				'link' => html_entity_decode(stripslashes($entry->link), ENT_QUOTES),									// s
				'size_150' => html_entity_decode(stripslashes($entry->images->thumbnail->url), ENT_QUOTES),				// s
				'size_320' => html_entity_decode(stripslashes($entry->images->low_resolution->url), ENT_QUOTES),		// s
				'size_640' => html_entity_decode(stripslashes($entry->images->standard_resolution->url), ENT_QUOTES),	// s
			),
			array(
                'id' => $entry->id
            ),
			array(
				'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
			),
			array(
                '%s'
            )
		);

		return "updated";
	}

}


//Need to sort pagination

class Atomic_Api_List_Table_Instagram extends WP_List_Table {

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
