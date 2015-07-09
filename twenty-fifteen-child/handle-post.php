<?php
/**
 * Created by PhpStorm.
 * User: Andre
 * Date: 03-07-2015
 * Time: 09:05
 */

add_action('admin_post_insert_attack','insert_attack');

function haveField($p1){
    return isset($_POST[$p1]);
}

function get_bin_data($file_name, $file_content){
    $extension = pathinfo($file_name, PATHINFO_EXTENSION);
    if($extension == "exe" || $extension == "msi"){
        
    }
    return $file_content;
}

function insert_attack() {
    $message="";
    if(!haveField("name")){
        $message.="You need to insert the name of attack<br>";
    }
    if(!haveField("desc")){
        $message.="You need to insert an description of attack<br>";
    }
    if(!haveField("so")){
        $message.="You need to insert the operative system<br>";
    }
    if(!haveField("act")){
        $message.="You need to insert the attack action<br>";
    }
    try{
        if($message!=""){
            throw new Exception($message);
        }
        if($_POST['act'] == "software"){
            $result = $GLOBALS['wpdb']->insert(
                'attacks',
                array(
                    'name' => $_POST['name'],
                    'description' => $_POST['desc'],
                    'so' => $_POST['so'],
                    'attack_action' => $_POST['act']
                ),
                array(
                    '%s',
                    '%s',
                    '%s',
                    '%s'
                )
            );
            if($result == false || $result == 0)
                throw new Exception("An error occours when trying to insert the attack.");
            $id = $GLOBALS['wpdb']->insert_id;
            $file_type = mysql_real_escape_string($_FILES["soft"]["type"]);
            $file_name = mysql_real_escape_string($_FILES["soft"]["name"]);
            $file_bin_data = get_bin_data($file_name , addslashes(file_get_contents($_FILES["soft"]["tmp_name"])));
            $file_size = strlen($file_bin_data);
            if($file_size > 8388608)
                throw new Exception("File too large. It must have at maximum 8388608 bytes but it has " . $file_size . " bytes.");

            $result = $GLOBALS['wpdb']->insert(
                'software',
                array(
                    'file_type' => $file_type,
                    'file_name' => $file_name,
                    'file_size' => $file_size,
                    'bin_data' => $file_bin_data,
                    'attack_id' => $id
                ),
                array(
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%d'
                )
            );

            if($result == false || $result == 0)
                throw new Exception("An error occours when trying to insert the software.");

        }else{
            $numberFile=$_POST['numberFile']+1;
            for($i=0;$i<$numberFile;$i++){
                if(isset($_POST['file_path'.$i])){

                    if(!isset($_POST['string'.$i]) || $_POST['string'.$i]=="" || $_POST['file_path'.$i]==""){
                        throw new Exception("You need to fill all the file fields<br>");
                    }
                }
            }
            $result = $GLOBALS['wpdb']->insert(
                'attacks',
                array(
                    'name' => $_POST['name'],
                    'description' => $_POST['desc'],
                    'so' => $_POST['so'],
                    'attack_action' => $_POST['act'],
                ),
                array(
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                )
            );
            if($result == false || $result == 0)
                throw new Exception("An error occours when trying to insert the attack.");

            $id=$GLOBALS['wpdb']->insert_id;
            if(!isset($_POST['numberFile']) || $numberFile<0){
                throw new Exception("An system error as ocurred<br>");
            }
            $hadError = false;
            for($i=0;$i<$numberFile;$i++){
                if(isset($_POST['file_path'.$i])){
                    $result = $GLOBALS['wpdb']->insert(
                        'files',
                        array(
                            'file_path' => $_POST['file_path'.$i],
                            'string' => $_POST['string'.$i],
                            'quantity' => 1, //TODO CRIAR CAMPO DE QUANTIDADE NA PARTE DO FORM DO CLIENTE
                            'attack_id' => $id
                        ),
                        array(
                            '%s',
                            '%s',
                            '%d',
                            '%d'
                        )
                    );
                    if($result == false || $result == 0)
                        $hadError = true;
                }
            }

            if($hadError)
                throw new Exception("An error occours when trying to insert the file(s).");
        }

        $value='<div class="success">The attack has been successfully added</div>';
        $_SESSION['hasAddAttack']=$value;
    }catch(Exception $e){
        $value='<div class="error">An error as ocurred: '.$e->getMessage().'</div>';
        $_SESSION['hasErrorAddAttack']=$value;
    }
    header("Location: ../addattack/");
    exit();

}