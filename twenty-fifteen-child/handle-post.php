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
        "You need to insert the attack action<br>";
    }
    if($GLOBALS['wpdb']->get_row('SELECT * FROM attacks WHERE name ="'.$_POST["name"].'"')!=null){
        $message.="Already exists a attack with that name, please select another name!";
    }
    try{
        if($message!=""){
            throw new Exception($message);
        }
        if($_POST['act'] == "software"){
            $file_type = mysql_real_escape_string($_FILES["soft"]["type"]);
            $file_name = mysql_real_escape_string($_FILES["soft"]["name"]);
            $file_bin_data = addslashes(file_get_contents($_FILES["soft"]["tmp_name"]));
            $file_size = $_FILES["soft"]["size"];
            if($file_size > 104857600 || $file_size<=0)
                throw new Exception("File too large. It must have at maximum 104857600B/100MB but it has " . $file_size . " bytes.");
            $result = $GLOBALS['wpdb']->insert(
                'attacks',
                array(
                    'name' => $_POST['name'],
                    'description' => $_POST['desc'],
                    'os' => $_POST['so'],
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
            if($result == false || $result == 0){
                $messsage=$GLOBALS['wpdb']->show_errors();
                $GLOBALS['wpdb']->delete('attacks', array('id' => $id));
                throw new Exception("An error occours when trying to insert the software.".$message);
            }

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
                    'os' => $_POST['so'],
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
                $GLOBALS['wpdb']->delete( 'attacks', array( 'id' => $id ) );
                throw new Exception("An system error as ocurred<br>");
            }
            $quantity=1;
            for($i=0;$i<$numberFile;$i++){
                if(isset($_POST['file_path'.$i])){
                    if(isset($_POST['quant'.$i]) && is_numeric($_POST['quant'.$i]) && $_POST['quant'.$i]>0 && $_POST['quant'.$i]<PHP_INT_MAX ){
                        $quantity=$_POST['quant'.$i];
                    }
                    $result = $GLOBALS['wpdb']->insert(
                        'files',
                        array(
                            'file_path' => $_POST['file_path'.$i],
                            'string' => $_POST['string'.$i],
                            'quantity' => $quantity,
                            'attack_id' => $id
                        ),
                        array(
                            '%s',
                            '%s',
                            '%d',
                            '%d'
                        )
                    );
                    
                    if($result == false || $result == 0){
                        $GLOBALS['wpdb']->delete( 'files', array( 'attack_id' => $id ) );
                        $GLOBALS['wpdb']->delete( 'attacks', array( 'id' => $id ) );
                        throw new Exception("An error occours when trying to insert the file(s).".$GLOBALS['wpdb']->last_error);
                    }
                    
                }
            }
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