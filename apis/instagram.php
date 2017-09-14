<?php

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use \WeDevs\ORM\Eloquent\Facades\DB;
use Illuminate\Database\Capsule\Manager as Capsule;

global $wpdb;


// $db = \WeDevs\ORM\Eloquent\Database::instance();
// class qb_answers extends Model {}
//
// $user_answers = qb_answers::select('id', 'answer', 'question_id', 'seconds', 'flagged')
//             ->where('user_id', '=', $user_id)
//             ->where('test_id', '=', $test_id)
//             ->where('test_complete', '=', NULL)
//             ->get()
//             ->toArray();


class atomic_api_instagram extends atomic_api_base {

	public $recordArray = array();
	public $totalRecords = 0;
	public $pageRecords = 0;
	public $resultsPerPage = 15;

	// Class Constructor
	public function __construct($api_details) {

		parent::__construct( $api_details );

		// $this->create_table();

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


	            $records = $this->get();


				// echo "<pre>";
				// print_r($entries);
				// echo "</pre>";

				// die('step 1');


		    	$placeListTable = new Atomic_Api_List_Table($this->columns);

	            echo '<h2>Instagram API <a href="tools.php?page=atomic_apis_instagram&sync=1" class="add-new-h2">Sync</a></h2>';

		    	$placeListTable->prepare_items();

	            $placeListTable->items = $records;

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

	function create_table() {

    	global $wpdb;

		$this->capsule_connect();

		try {
			Capsule::schema()->create(
				$wpdb->prefix.'api_instagram',
				function ($table) {

					$table->string('id',100);
					$table->text('caption');
					$table->string('type',30);
					$table->string('link',130);
					$table->string('size_150',200);
					$table->string('size_320',200);
					$table->string('size_640',200);
					$table->timestamp('added_at')->nullable();
					$table->timestamp('updated_at')->nullable();
					$table->timestamp('created_at')->nullable();
					$table->boolean('hidden');

					$table->unique('id');
				}
			);
		} catch (\Exception $e) {
			echo "Unable to create table: {$e->getMessage()}";
		}

		return true;

	}


    public function get($query_args=array()) {

		$records = DB::table('api_instagram')->orderBy('id', 'desc')->take(10)->get();

		if(count($records) > 0){
			foreach($records as $key => $record){
				$record->human_time_ago = $this->human_elapsed_time($record->added_at);
			}
		}

		// Convert object to Array
		$records = array_map(function($val){
		    return json_decode(json_encode($val), true);
		}, $records);


		return $records;

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

		// global $wpdb;
		// $wpdb->show_errors();
		//
		// $wpdb->insert($this->api_details['db_table'],
		// 	array(
		// 		'id' => html_entity_decode(stripslashes($entry->id), ENT_QUOTES),										// d
		// 		'caption' => html_entity_decode(stripslashes($entry->caption->text), ENT_QUOTES),						// s
		// 		'type' => html_entity_decode(stripslashes($entry->type), ENT_QUOTES),									// s
		// 		'added_at' => date( "Y-m-d H:i:s", $entry->created_time),												// s
		// 		'created_at' => date( "Y-m-d H:i:s", time()),															// s
		// 		'updated_at' => date( "Y-m-d H:i:s", time()),															// s
		// 		'link' => html_entity_decode(stripslashes($entry->link), ENT_QUOTES),									// s
		// 		'size_150' => html_entity_decode(stripslashes($entry->images->thumbnail->url), ENT_QUOTES),				// s
		// 		'size_320' => html_entity_decode(stripslashes($entry->images->low_resolution->url), ENT_QUOTES),		// s
		// 		'size_640' => html_entity_decode(stripslashes($entry->images->standard_resolution->url), ENT_QUOTES),	// s
		// 		'hidden' => 0,																							// d
		// 	),
		// 	array(
		// 		'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d'
		// 	)
		// );

		$records = DB::table('api_instagram')->insert([
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
        ]);


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
