<?php
/**
 * Created by PhpStorm.
 * User: Andre
 * Date: 01-07-2015
 * Time: 18:00
 */
const INVALID_ARGUMENTS = "Invalid request arguments.";
const MISSING_ARGUMENTS = "Request arguments are missing.";
const REQUEST_GENERAL_ERROR = "Error while downloading the file.";

require_once('../../../../wp-admin/admin-post.php');

function get_software_from_attack($attackID)
{
    global $wpdb;
    return $wpdb->get_row("SELECT DISTINCT * FROM software WHERE attack_id=" . $attackID, OBJECT);
}

function download_software($attack_id){
    try{
        $software = get_software_from_attack($attack_id);
        if($software == null)
            throw new Exception(REQUEST_GENERAL_ERROR);

        $file_type = $software->file_type;
        $file_name = $software->file_name;
        $file_size = $software->file_size;
        $content = $software->bin_data;
        header("Content-Type: " . $file_type);
        header("Content-Length: " . $file_size);
        header("Content-Disposition: attachment; filename=" . $file_name);
        echo $content;

    }catch (Exception $ex){
        die($ex->getMessage());
    }
}

if(is_user_logged_in()){
    $attack_id = $_GET['attack_id'];
    if(empty($attack_id))
        die(MISSING_ARGUMENTS);
    $attack_id = mysql_real_escape_string($attack_id);
    download_software($attack_id);
}
else die("0");



