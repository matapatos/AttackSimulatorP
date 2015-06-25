<?php

/*
	AS -> AttackSimulator
*/

class AS_Ajax_Handler {
	/**
	 * Created for assign all the custom ajax links.
	 */
	private $function_name = "";
	private $request = [];
	const INVALID_ARGUMENTS = "Invalid request arguments.";
	const MISSING_ARGUMENTS = "Request arguments missing.";

	 function __construct($request) {
       $this->$request = $request;
   	}

	public function hooks(){
		add_action( 'wp_ajax_instructions', array( $this, 'get_instructions'));
		add_action( 'wp_ajax_downloadFile', array( $this, 'get_downloadfile'));
		add_action( 'wp_ajax_remotly', array( $this, 'remotly'));
	}

	/**
	 * TODO
	 * @return answer for the client.
	 */
	public function remotly(){
		if( ! wp_verify_nonce( $_POST['attacks'], 'remotly' ) ){
	        wp_send_json_error("Not enougth arguments for the request.");
	    }

   		die("Remotly");
	}
	/**
	 * TODO
	 * @return answer for the client.
	 */
	public function get_downloadfile(){

		$this->function_name = 'get_downloadfile';
		
		$data = $this->request;
		$this->check_hasParameter($data);

		$attacksID = $data['attacks'];
		$this->check_hasParameter($attacksID);

	    $safe_attacksID = mysql_real_escape_string($attacksID);

	    if(is_array($safe_attacksID) || count($safe_attacksID) <= 0)
	    	wp_send_json_error($this::INVALID_ARGUMENTS);

        $attacks = get_attacksByID($safe_attacksID);

        die('IT WORKS: ' .  $attacks);

	    // IMPORTANT: don't forget to "exit"
	}
	/**
	 * TODO
	 * @return answer for the client.
	 */
	public function get_instructions(){
		if( ! wp_verify_nonce( $_POST['attacks'], 'get_instructions' ) ){
	        wp_send_json_error("Not enougth arguments for the request.");
	    }

	    $attacksID = $_POST['attacks'];
	 
	    // IMPORTANT: don't forget to "exit"
	    exit;
	}

	//--------------- AUXILIARY METHODS -------------------

	private function get_attacksByID($attacksID){
		global $wpdb;
    	return $wpdb->get_results( 'SELECT * FROM attacks WHERE id IN (' . $attacksID .')', OBJECT);
	}

	private function check_hasParameter($parameter){
		if( ! wp_verify_nonce( $parameter, $this->function_name ))
	        wp_send_json_error($this::MISSING_ARGUMENTS);
	}

}



?>