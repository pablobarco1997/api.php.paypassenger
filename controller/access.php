<?php


class  Access
{
    public function __construct($db)
    {
        $this->db = $db;
    }

    public function verify()
    {
        $obj = $this->db->fetchObject("SELECT status as estado FROM access limit 1");
        if ($obj->estado === 1)
            return true;
        else
            return false;
    }
}


?>



