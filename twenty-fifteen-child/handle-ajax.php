<?php

/*
	AS -> AttackSimulator
*/
require_once("ConnectSSH.php");

global $jsonDATA;
const INVALID_ARGUMENTS = "Invalid request arguments.";
const MISSING_ARGUMENTS = "Request arguments missing.";
const REQUEST_GENERAL_ERROR = "Error during the request.";
const LINUX_BREAK = "\n";
const WINDOWS_BREAK = "\r\n";
const DOWNLOAD_FILE_FULL_LINK = "http://localhost/wordpress/wp-content/themes/AttackSimulatorP/twenty-fifteen-child/handle-get.php?attack_id=";


function hooks(){
    add_action( 'wp_ajax_downloadFile', 'get_downloadfile', 1);
    add_action( 'wp_ajax_remotely', 'remotely', 1);
}

/**
 * TODO
 * @return answer for the client.
 */
function remotely(){ //TODO TEST IT IN LINUX
    $jsonString = get_attacksRequested();
    if( $jsonString == null)
        wp_send_json_error(REQUEST_GENERAL_ERROR);
    $json['attacks'] = json_decode($jsonString);
    $json['ip'] = check_getParameterOrSendErrorMSG('ip');
    $json['username'] = check_getParameterOrSendErrorMSG('username');
    $json['password'] = check_getParameterOrSendErrorMSG('password');

    $con = new ConnectSSH($json['ip'], $json['username'], $json['password']); //TODO PERGUNTAR STOR SE Não tem que ter timeout.
    try{
        if(!$con->connect())
            wp_send_json_error($con->get_error_msg());

        if(count($json['attacks']) == 0)
            wp_send_json_error(INVALID_ARGUMENTS);

        $attacks_cmd = null;
        $download_files = null;
        if(array_key_exists("windows", $json['attacks'])){
            $attacks_cmd = $json['attacks']->{'windows'};
        }

        else if(array_key_exists("linux", $json['attacks'])){
            $attacks_cmd .= $json['attacks']->{'linux'};
        }

        else wp_send_json_error(INVALID_ARGUMENTS);

        if($attacks_cmd == null)
            wp_send_json_error(INVALID_ARGUMENTS);

        $data = $con->exec($attacks_cmd);
        if($data == false)
            wp_send_json_error($con->get_error_msg());
        wp_send_json_success("Remote access finished with success!");
    }catch(Exception $ex){
        wp_send_json_error(REQUEST_GENERAL_ERROR);
    }
}
/**
 * TODO
 * @return answer for the client.
 */
function get_downloadfile(){
    $jsonMSG = get_attacksRequested();
    if( $jsonMSG == null)
        wp_send_json_error(REQUEST_GENERAL_ERROR);
    else wp_send_json_success($jsonMSG);
}

//--------------- AUXILIARY METHODS -------------------
//

/**
 * @param $attacksID - Array of IDs of the attacks that we want to check if have any software associated.
 * @return string|null - string with the commands needed to be executed to download the files or NULL if there are no attacks with software associated.
 */
function get_soft_command_for_download($attacksID, $LINK){
    $soft_urls = null;
    foreach($attacksID as $id){
        $software = get_softFromAttackID($id);
        if($software != null)
            $soft_urls .= $LINK . $id . ";";
    }
    return $soft_urls;
}

function get_filesFromAttackID($ID)
{
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM files WHERE attack_id=" . $ID, OBJECT);
}

function get_softFromAttackID($ID)
{
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM software WHERE attack_id=" . $ID, OBJECT);
}

function get_linuxAttacksFromID($attacksID)
{
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM attacks WHERE id IN (" . implode(",", $attacksID) . ") AND LCASE(os)='linux'", OBJECT);
}

function get_windowsAttacksFromID($attacksID)
{
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM attacks WHERE id IN (" . implode(",", $attacksID) . ") AND LCASE(os)='windows'", OBJECT);
}

function remove_slashes($string){
    $string = str_replace('\"', '"', $string);
    $string = str_replace("\'", "'", $string);
    $string = str_replace("\\\\", "\\", $string);
    return $string;
}

/**
 * @param $attacksID - Array.
 * @return Array - To be used on SQL queries.
 */
function get_safe_array($attacksID){
    for($i = 0; $i < count($attacksID); $i++){
        $attacksID[$i] = mysql_real_escape_string($attacksID[$i]);
    }
    return $attacksID;
}
/**
 * The JSON created here it's the strings of the executable file.
 * @return JSON string on success OR false on failure
 */
function get_attacksRequested(){
    try{
        $attacksID = check_getParameterOrSendErrorMSG('attacks');

        if(!is_array($attacksID) || count($attacksID) == 0)
            wp_send_json_error(INVALID_ARGUMENTS);

        $safe_attacksID = get_safe_array($attacksID);
        $win_attacks = get_windowsAttacksFromID($safe_attacksID);
        $jsonMESSAGE = [];
        if(count($win_attacks) > 0){
            $jsonMESSAGE['windows'] = get_windows_attack_text($win_attacks);
            $jsonMESSAGE['win_attacks_id'] = $win_attacks;
        }

        $lin_attacks = get_linuxAttacksFromID($safe_attacksID);
        if(count($lin_attacks) > 0){
            $jsonMESSAGE['linux'] = get_linux_attack_text($lin_attacks);
            $jsonMESSAGE['lin_attacks_id'] = $lin_attacks;
        }

        return json_encode($jsonMESSAGE);
    }catch(Exception $ex){
    }
    return false;
}
/**
 * @param $attack_id - ID of the attack that the files are associated.
 * @param $text - Current text from the executable file.
 * @param $needBreak - Boolean to check if it's needed a paragraph.
 * @return string - $text + files text associated with the attack, if any. If not only return $text.
 */
function get_windows_files_text($attack_id, $text){
    $files = get_filesFromAttackID($attack_id);
    $length = count($files);
    if($length > 0) {
        foreach ($files as $f) {
            $quant = $f->quantity;
            for($i = 0; $i < $f->quantity; $i++) {
                $text .= "@echo " . remove_slashes($f->string) . " >> " . remove_slashes($f->file_path);
                $quant -= 1;
                if($quant > 0)
                    $text .= WINDOWS_BREAK;
            }
            $length -= 1;
            if ($length > 0)
                $text .= WINDOWS_BREAK;
        }
    }
    return $text;
}
/**
 * It always open the files with the default program associated with that kind of files.
 *
 * @param $attack_id - ID of the attack that the software is/are associated.
 * @param $text - Current text from the executable file.
 * @param $needBreak - Boolean to check if it's needed a paragraph.
 * @return string - $text + software text associated with the attack, if any. If not only return $text.
 */
function get_windows_soft_text($attack_id, $text){
    $software = get_softFromAttackID($attack_id);
    $length = count($software);
    if($length > 0){
        foreach($software as $s){
            //bitsadmin /transfer AttackSimulator http://localhost/wordpress/wp-content/themes/AttackSimulatorP/twenty-fifteen-child/handle-get.php?attack_id=22 "%cd%\first.sh"
            $text .= "bitsadmin /transfer AttackSimulator \"" . DOWNLOAD_FILE_FULL_LINK . $attack_id . "\" \"%cd%\\" . $s->file_name . "\"" . WINDOWS_BREAK;
            $text .= "CALL \"" . $s->file_name . "\"";
            $length -= 1;
            if($length > 0)
                $text .= WINDOWS_BREAK;
        }
    }
    return $text;
}
/**
 * @param $win_attacks - windows attacks to get the text for the executable file.
 * @return string - Final text for windows executable file.
 */
function get_windows_attack_text($win_attacks){
    $text = "@echo off";
    foreach($win_attacks as $a){
        $text .= WINDOWS_BREAK . "::" . $a->name . WINDOWS_BREAK;
        $text = get_windows_files_text($a->id, $text);
        $text = get_windows_soft_text($a->id, $text);
    }
    $text .= WINDOWS_BREAK . "exit";
    return $text;
}
/**
 * @param $attack_id - ID of the attack that the is associated with that files.
 * @param $text - Current text from the executable file.
 * @return string - $text + files text associated with the attack, if any. If not only return $text.
 */
function get_linux_files_text($attack_id, $text){
    $files = get_filesFromAttackID($attack_id);
    $length = count($files);
    if($length > 0){
        foreach($files as $f){
            $quant = $f->quantity;
            for($i = 0; $i < $f->quantity; $i++){
                $text .= "echo '" . remove_slashes($f->string) . "' >> '" . remove_slashes($f->file_path) . "';";
                $quant -= 1;
                if($quant > 0)
                    $text .= LINUX_BREAK;
            }
            $length -= 1;
            if($length > 0)
                $text .= LINUX_BREAK;
        }
    }
    return $text;
}
/**
 * @param $haystack - String where we wanna search for the $needle at the end.
 * @param $needle - Substring to be searched at the end of $haystack
 * @return bool - True if $needle == "" or $haystack contains $needle at the end. False if $haystack doesn't contains $needle.
 */
function endsWith($haystack, $needle) {
    // search forward starting from end minus needle length characters
    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
}
/**
 * SUPPORTED FORMATS:
 *  SH, DEB, TAR.GZ, TAR.BZ2, the rest it's opened with default program.
 *
 * @param $attack_id - ID of the attack that the software is/are associated.
 * @param $text - Current text from the executable file.
 * @return string - $text + software text associated with the attack, if any. If not only return $text.
 */
function get_linux_soft_text($attack_id, $text){
    $software = get_softFromAttackID($attack_id);
    $length = count($software);
    if($length > 0){
        $text .= "hasWget=$(which wget)" . LINUX_BREAK .
                 "if [ -z \"\$hasWget\" ]; then" . LINUX_BREAK .
                    "apt-get install wget" . LINUX_BREAK .
                 "fi" . LINUX_BREAK; //TODO WGET DOESNT SUPPORT FEDORA LINUX.
        foreach($software as $s){
            $file_name = $s->file_name;
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION); //GET FILE EXTENSION

            $text .= "wget -O \"" . $file_name . "\" \"" . DOWNLOAD_FILE_FULL_LINK . $attack_id . "\"" . LINUX_BREAK; //TODO IT ONLY ACCEPTS A ATTACK TO HAVE ONE SOFTWARE ASSOCIATED.
            if($file_extension == "sh")
                $text .= "sh \"./" . $file_name . "\"";
            else if($file_extension == "deb")
                $text .= "dpkg -i \"./" . $file_name . "\"";
            else if(endsWith($file_name, ".tar.gz")){
                $text .= "tar xvzf \"" . $file_name . "\"" . LINUX_BREAK; //EXTRACT FROM PACKAGE
                $text .= "./configure" . LINUX_BREAK; //CONFIGURE
                $text .= "make" . LINUX_BREAK; //PREPARE
                $text .= "make install"; //INSTALL
            }
            else if(endsWith($file_name, ".tar.bz2")){
                $text .= "tar xvjf \"" . $file_name . "\"" . LINUX_BREAK; //EXTRACT FROM PACKAGE
                $text .= "./configure" . LINUX_BREAK; //CONFIGURE
                $text .= "make" . LINUX_BREAK; //PREPARE
                $text .= "make install"; //INSTALL
            }
            else $text .= "xdg-open \"" . $file_name . "\""; //DEFAULT OPENNING PROGRAM. NOT SURE IF WORKS OK!!
            $length -= 1;
            if($length > 0)
                $text .= LINUX_BREAK;
        }
    }
    return $text;
}

/**
 * @param $lin_attacks - linux attacks to get the text for the executable file.
 * @return string - Final text for linux executable file.
 */
function get_linux_attack_text($lin_attacks){
    $text = "#!/bin/bash";
    foreach($lin_attacks as $a){
        $text .= LINUX_BREAK . "#" . $a->name . LINUX_BREAK;
        $text = get_linux_files_text($a->id, $text);

        $text = get_linux_soft_text($a->id, $text);
    }
    return $text;
}

/**
 * [check_getParameterOrSendErrorMSG description]
 * @param  [String] $key -> Key to check if exists in the user request.
 * @return Value of the key OR Send error msg to user
 */
function check_getParameterOrSendErrorMSG($key){
    if(empty($key))
        wp_send_json_error(INVALID_ARGUMENTS);
    global $jsonDATA;
    if(empty( $jsonDATA->{$key} ))
        wp_send_json_error(MISSING_ARGUMENTS);

    return $jsonDATA->{$key};
}


try{
    $data = str_replace("\\", "", $_REQUEST['data']);
    $jsonDATA = json_decode(json_decode(json_encode($data)));
    if(empty($jsonDATA))
        wp_send_json_error(MISSING_ARGUMENTS);
    hooks();
}catch(Exception $ex){
    wp_send_json_error("Ocorreu um erro na transformação de parêmtros.");
}

?>