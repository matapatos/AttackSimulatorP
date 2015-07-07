<?php
/**
 * Created by PhpStorm.
 * User: Andre
 * Date: 03-07-2015
 * Time: 14:14
 */

class ConnectSSH {

    private $host;
    private $auth_username;
    private $auth_password;
    private $port = 22;
    private $connection;
    private $error_msg;

    function __construct($host, $auth_username, $auth_password) {
        $this->host = $host;
        $this->auth_username = $auth_username;
        $this->auth_password = $auth_password;
    }

    public function connect(){
        if (!($this->connection = ssh2_connect($this->host, $this->port))) {
            $this->error_msg = "Cannot connect to server";
            return false;
        }
        if( !ssh2_auth_password( $this->connection, $this->auth_username, $this->auth_password ) ) {
            $this->error_msg = "Authorization failed !";
            return false;
        }
        return true;
    }

    public function exec($cmd){
        if (!($stream = ssh2_exec($this->connection, $cmd))) {
            $this->error_msg = "SSH command failed";
            return false;
        }
        /*stream_set_blocking($stream, true);
        $data = "";
        while ($buf = fread($stream, 4096)) {
            $data .= $buf;
        }
        fclose($stream);
        return $data;*/
        return true;
    }

    public function get_error_msg(){
        return $this->error_msg;
    }

    public function disconnect(){
        $this->exec('echo "EXITING" && exit;');
        $this->connection = null;
    }

    function __destruct() {
        $this->disconnect();
    }
}