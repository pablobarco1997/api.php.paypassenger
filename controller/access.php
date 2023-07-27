

<?php


function access_app(){
    require_once "../class/class.connection.php";
    $db = new db("localhost");
    $obj =  $db->fetchObject("SELECT status as estado FROM access limit 1");
    if($obj->estado === 1)
        return true;
    else
        return false;
}


?>



