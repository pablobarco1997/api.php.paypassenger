<?php


class db
{

    /**
     * PASS REMOTO
     * USER: B07
     * PASS: 123
     * URL: ec2-52-15-181-14.us-east-2.compute.amazonaws.com
     */


    private $servername = "";
    private $DataBase = "";
    private $username = "";
    private $password = "";

    public function __construct($servername = "")
    {
        $hostAws = 'ec2-52-15-181-14.us-east-2.compute.amazonaws.com';
        $userName = "root";
        $passName = "";
        $this->DataBase = "sch_bus_payments";
        if (empty($servername) || $servername === "localhost" && $_SERVER["SERVER_NAME"] == 'ec2-52-15-181-14.us-east-2.compute.amazonaws.com') {
            //Localhost
            $this->servername = "localhost";
            $this->username = $userName;
            $this->password = $passName;
        } else {
            //Remoto
            $this->servername = $hostAws;
            $this->username = "";
            $this->password = "";
        }

    }

    public function tableInsertRow($name, $colunmValues = array())
    {
        try {
            $_colunm = array();
            $_values = array();
            if (count($colunmValues) > 0) {
                foreach ($colunmValues as $value) {
                    $_colunm[] = $value[0];
                    $_values[] = "'$value[1]'";
                }
                $str = "INSERT INTO $name (" . implode(',', $_colunm) . ") VALUES (" . implode(",", $_values) . ")";
                $result = $this->query($str);
                return $result;
            }
            return null;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function tableUpdateRow($name, $colunmValues = array(), $id = "")
    {
        $_values = array();
        if (count($colunmValues) > 0) {
            foreach ($colunmValues as $value) {
                $_col = $value[0];
                $_val = $value[1];
                $_values[] = "$_col = '$_val'";
            }
            if (is_numeric($id)) {
                $str = "UPDATE $name SET  " . implode(',', $_values) . " WHERE rowid = $id";
                //print_r($str); die();
                $res = $this->query($str);
                if ($res === 1)
                    return true;
                else
                    return $res;
            } else {
                return false;
            }
        }
        return null;
    }

    public function fetchArray($query = "")
    {
        $db = $this->open();
        $fetch = $db->query($query)->fetch_all(MYSQLI_ASSOC);
        $db->close();
        return $fetch;
    }

    public function Count($tableJoin, $where = "")
    {
        $db = $this->open();
        $str = "select count(*) as count_number from $tableJoin $where";
        $object = $db->query($str);
        $db->close();
        if ($object && $object->num_rows > 0) {
            return $object->fetch_object()->count_number;
        } else {
            return 0;
        }
    }

    public function query($query = "")
    {
        $db = $this->open();
        $response = $db->query($query);
        $mysql_error = $db->error;
        $db->close();
        if ($mysql_error)
            return $mysql_error;
        else
            return $response;
    }

    public function quote($params = "")
    {
        $db = $this->open();
        $response = $db->escape_string($params);
        $db->close();
        return $response;
    }

    public function fetchObject($query = "")
    {
        $db = $this->open();
        $obj = new stdClass();
        $response = $db->query($query);
        if ($response && $response->num_rows > 0)
            $obj = $response->fetch_object();
        else {
            $db->close();
            return false;
        }
        $db->close();
        return $obj;
    }

    private function open()
    {
        require_once "class.send.response.php";
        $response = new Response();
        $mysql = new mysqli($this->servername, $this->username, $this->password, $this->DataBase, 3306);
        if (!empty($mysql->connect_error)) {
            $response->errorAlert = "Error de conexión con la base de datos ". $mysql->connect_error;
            $response->send();
            die();
        } else
            $mysql->set_charset("utf8");
        return $mysql;
    }

    public function Token($token = "")
    {
        $db = $this->open();
        $response = $db->query("SELECT count(*) as valid FROM bp_users_profile where concat(login,pass)  = '" . $token . "' ");
        if ($response) {
            if ($response->fetch_object()->valid != 0) {
                $db->close();
                return true;
            }
        } else {
            $db->close();
            return false;
        }
    }
}


?>