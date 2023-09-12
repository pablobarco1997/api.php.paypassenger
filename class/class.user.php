<?php

class User
{


    public function __construct($db)
    {
        $this->db = $db;
    }


    public function fetch($id)
    {
        $response = $this->db->fetchObject("select * from bp_users_profile where rowid  = $id ");
        if ($response)
            return $response;
        else
            return false;
    }

    public function existTransporte($id)
    {
        $response = $this->db->Count("bp_users_profile", " where  rowid = $id and type_users = 'T' ");
        if ($response)
            return $response;
        else
            return false;
    }

    public function isChildrenAcount($id)
    {
        $response = $this->db->Count("bp_users_profile", " where  rowid = $id and idparent != 0");
        if ($response)
            return true;
        else
            return false;
    }

    //obtenego el id padre
    public function getAcountParentId($id)
    {
        $response = $this->db->fetchObject("select idparent from bp_users_profile where rowid  = $id and idparent != 0");
        if ($response)
            return $response->idparent;
        else
            return false;
    }

    //obtenego monto balance
    public function amountUser($id)
    {
        $response = $this->db->fetchObject("select ifnull(round(sum(amount), 2), 0) as amount from bp_transacciones where id_users = $id");
        return $response->amount;
    }


    public function TransaccionesList($id, $type = "")
    {
        if ($type === 'D')
            $where = "   and type_transaction = 'D' ";
        else
            $where = " ";
        $response = $this->db->fetchArray("select * from bp_transacciones where id_users = $id $where order by rowid desc;");
        return $response;
    }

    public function existTrajetaCredDeb($id)
    {
        $response = $this->db->fetchObject("select count(*) as exits from bp_card_cd  where id_users = $id; ");
        return $response->exits == 0 ? false : true;
    }

    public function exitsUser($login)
    {
        $response = $this->db->fetchObject("select login  from bp_users_profile  where login = '$login'; ");
        if ($response == false)
            return "";
        else
            return $response->login;
    }

    public function fecthCardAcreditDebit($iduser)
    {
        $response = $this->db->fetchObject("select * from bp_card_cd where id_users = $iduser");
        if ($response != false)
            return $response;
        else
            return false;
    }

    public function transaccionClient($id, $desc, $amount = 0, $id_user_linea = 0, $type)
    {
        return $this->db->tableInsertRow("bp_transacciones", array(
            array("address", $desc),
            array("amount", $amount),
            array("type_transaction", $type),
            array("id_user_linea", $id_user_linea),
            array("id_users", $id),
        ));
    }




}


?>





