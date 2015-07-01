<?php

/*
	AS -> AttackSimulator
*/


global $jsonDATA;
const INVALID_ARGUMENTS = "Invalid request arguments.";
const MISSING_ARGUMENTS = "Request arguments missing.";
const REQUEST_GENERAL_ERROR = "Error during the request.";



function hooks(){
    add_action( 'wp_ajax_downloadFile', 'get_downloadfile', 1);
    add_action( 'wp_ajax_remotely', 'remotely', 1);
}

/**
 * TODO
 * @return answer for the client.
 */
function remotely(){
    $jsonString = get_attacksRequested();
    if( $jsonString == null)
        wp_send_json_error(REQUEST_GENERAL_ERROR);
    $json['attacks'] = json_decode($jsonString);
    $json['ip'] = check_getParameterOrSendErrorMSG('ip');
    $json['username'] = check_getParameterOrSendErrorMSG('username');
    $json['password'] = check_getParameterOrSendErrorMSG('password');


//wp_send_json_success("IP: " . $json['ip'] . " USERNAME: " . $json['username'] . " Password: " . $json['password']);


    connectSsh($json['username'], 'sh ~/linux.sh', $json['ip']);
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

function get_filesFromAttackID($ID)
{
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM files WHERE attack_id=" . $ID, OBJECT);
}

function get_linuxAttacksFromID($attacksID)
{
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM attacks WHERE id IN (" . implode(",", $attacksID) . ") AND LCASE(so)='linux'", OBJECT);
}

function get_windowsAttacksFromID($attacksID)
{
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM attacks WHERE id IN (" . implode(",", $attacksID) . ") AND LCASE(so)='windows'", OBJECT);
}


function connectSsh($username, $command, $ip){

    // $execout=exec('ssh root@192.168.217.132 "/home/jails/jails-start-j2.sh" ',$output1,$result);
    $execout=exec('ssh ' . $username .'@'.$ip.' "'.$command.'" ',$output1,$result);
    if($execout !=0){
        wp_send_json_success("SSH connection succeeded!");
    }
    else{
        wp_send_json_error("SSH connection error. You must check if you have ssh connection enable.");
    }



    return $execout;
}

function get_attacksRequested(){
    try{
        $attacksID = check_getParameterOrSendErrorMSG('attacks');

        if(!is_array($attacksID) || count($attacksID) == 0)
            wp_send_json_error(INVALID_ARGUMENTS);

        $win_attacks = get_windowsAttacksFromID($attacksID);
        $jsonMESSAGE = [];
        if(count($win_attacks) > 0)
            $jsonMESSAGE['windows'] = get_windows_file_text($win_attacks);

        $lin_attacks = get_linuxAttacksFromID($attacksID);
        if(count($lin_attacks) > 0)
            $jsonMESSAGE['linux'] = get_linux_file_text($lin_attacks);

        return json_encode($jsonMESSAGE);
    }catch(Exception $ex){
    }
    return null;
}

function get_windows_file_text($win_attacks){
    $text = "@echo off\n";
    $needBreak = false;
    foreach($win_attacks as $a){
        $files = get_filesFromAttackID($a->id);
        $length = count($files);
        if($length > 0){
            if($needBreak) //USED JUST TO FORMAT THE FILE
                $text .= "\n";
            else $needBreak = true;

            foreach($files as $f){
                $text .= "@echo " . $f->string . " >> " . $f->file_path . "\n";
                $length -= 1;
            }
        }
    }
    $text .= "exit";
    return $text;
}

function get_linux_file_text($lin_attacks){
    $text = "";
    $needBreak = false;
    foreach($lin_attacks as $a){
        $files = get_filesFromAttackID($a->id);
        $length = count($files);
        if($length > 0){
            if($needBreak) //USED JUST TO FORMAT THE FILE
                $text .= "\n";
            else $needBreak = true;

            foreach($files as $f){
                $text .= "echo '" . $f->string . "' >> '" . $f->file_path . "';";
                $length -= 1;
                if($length > 0)
                    $text .= "\n";
            }
        }
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
}catch(Exeption $ex){
    wp_send_json_error("Ocorreu um erro na transformação de parêmtros.");
}

?>