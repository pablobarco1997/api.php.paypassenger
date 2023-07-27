<?php


require_once "class/class.connection.php";
require_once "class/class.send.response.php";
require_once "class/class.user.php";
require_once "controller/access.php";

header('Content-Type: application/json');

$requestData = $_POST;


if (!isset($requestData['accion'])) {
    $response = new Response();
    $response->errorAlert = "No se proporcionó la acción requerida.";
    $response->send();
}


$token = $requestData["token"];
$accion = $requestData["accion"];
$ServerHost = "localhost";



if(access_app() === false){
    $response = new Response();
    $response->errorAlert = "UD no puede acceder a ninguno de los módulos, por favor contactarse con soporte";
    $response->send();
    return false;

}



//print_r($requestData); die();
//se valida el token globalmente
if ($accion !== "autentication"  && (isset($requestData["signNew"]) && $requestData["signNew"] == false)) {
    $db = new db($ServerHost);
    $response = new Response();
    if (!$db->Token($token)) { //si el token es invalido
        $response->errorAlert = "Token invalido Inicie Session de nuevo";
        $response->send();
        return;
    }
}




switch ($accion) {

    case "autentication":
        $db = new db($ServerHost);
        $response = new Response();
        $_user = $requestData["p_user"];
        $_pass = $requestData["p_pass"];
        $autentication = $db->Count("bp_users_profile", " where login = '$_user' and pass = '$_pass' ");
        $str = "select * from bp_users_profile where login = '$_user' and pass = '$_pass' ";
        $fetch = $db->fetchArray($str);
        if ($autentication > 0) //true
        {
            $UserObject = $db->fetchObject($str);
            if($UserObject->estado === 'E'){
                $response->errorAlert = "No puede iniciar sesión. Usuario ha sido eliminado";
            }else{
                $response->success = 1;
                $response->data = $fetch;
            }
        } else
            $response->errorAlert = "Usuario o contraseña invalida";
        $response->send();
        break;


    case "AmountBalance":
        $db = new db($ServerHost);
        $response = new Response();
        $User = new User($db);

        $id = $requestData["id"];
        if ($User->isChildrenAcount($id)) //se valida cuenta hija
        {
            $idParents = $User->getAcountParentId($id);
            $amount = $User->amountUser($idParents);
        } else {
            $amount = $User->amountUser($id);
        }

        $response->success = "ok";
        $response->data = array("amount" => $amount);
        $response->send();
        break;

    case  "TransaccionesListado":

        $db = new db($ServerHost);
        $response = new Response();
        $User = new User($db);
        $id = $requestData["id"];

        $datosTransacciones = [];
        $id = $requestData["id"];
        $recargas = $requestData["creditedOnly"];
        if ($User->isChildrenAcount($id)) //se valida cuenta hija
        {
            $idParents = $User->getAcountParentId($id);
            if ($recargas == "true")
                $type = "D";
            else
                $type = "";

            $datosTransacciones = $User->TransaccionesList($id, $type);
            $response->data = $datosTransacciones;
            $response->success = 'ok';
        } else {
            if ($recargas == "true")
                $type = "D";
            else
                $type = "";
            $datosTransacciones = $User->TransaccionesList($id, $type);
            $response->data = $datosTransacciones;
            $response->success = 'ok';
        }

        $response->send();
        break;

    case "datosPersonales":

        $db = new db($ServerHost);
        $response = new Response();
        $User = new User($db);
        $id = $requestData["id"];
        $ope = $requestData['ope'];
        $datos = $requestData['datos'];

        //print_r($requestData); die();

        if ($ope === 'update') {

            $column = [];
            foreach ($datos as $key => $value) {
                $column[] = array($key, $value);
            }
            $value = $db->tableUpdateRow("bp_users_profile", $column, $id);
            if ($value)
                $response->success = "ok";
            else
                $response->errorAlert = "Ocurrio un error con la operacion " . $ope;
        }

        if ($ope === 'create') {

            $login = $datos['login'];
            $exist = $User->exitsUser($login);

            if ($exist === $login) {
                $response->errorAlert = "usuario ya existe";
                $response->send();
                return;
            }

            $column = [];
            foreach ($datos as $key => $value) {
                $column[] = array($key, $value);
            }
            $value = $db->tableInsertRow("bp_users_profile", $column);
            if ($value)
                $response->success = "ok";
            else
                $response->errorAlert = "Ocurrio un error con la operacion " . $ope;
        }

        $response->send();
        break;


    case  "fetch_card_cd":

        $db = new db($ServerHost);
        $response = new Response();
        $User = new User($db);
        $id = $requestData["id"];
        $value = $db->fetchObject("select * from bp_card_cd  where id_users = $id");
        if (is_object($value)) {
            $response->data = $value;
            $response->success = "ok";
        } else {
        }
        $response->send();
        break;


    case  "newTarjeta":

        $db = new db($ServerHost);
        $response = new Response();
        $User = new User($db);
        $id = $requestData["id"];
        $datos = $requestData["datos"];

        $column = [];
        if (!$User->existTrajetaCredDeb($id)) {
            //create
            $column[] = array('id_users', $id);
            foreach ($datos as $key => $value) {
                if ($key != 'rowid')
                    $column[] = array($key, $value);
            }
            $value = $db->tableInsertRow("bp_card_cd", $column);
            if (!$value) {
                $response->errorAlert = 'ocurrio un error con la operacion crear';
            } else {
                $response->success = "ok";
            }
        } else {
            //update
            $idrow = 0;
            foreach ($datos as $key => $value) {
                if ($key != 'rowid')
                    $column[] = array($key, $value);
                else {
                    if ($key == 'rowid')
                        $idrow = $value;
                }

            }
            $value = $db->tableUpdateRow("bp_card_cd", $column, $idrow);
            if (!$value) {
                $response->errorAlert = 'ocurrio un error con la operacion crear';
            } else {
                $response->success = "ok";
            }
        }

        $response->send();
        break;


    case "fetch_all_account_users":
        $db = new db($ServerHost);
        $response = new Response();
        $User = new User($db);
        $id = $requestData["id"];
        $value = $db->fetchArray("select * from bp_users_profile  where idparent = $id and estado = 'A' ");
        if (count($value) > 0) {
            $response->data = $value;
            $response->success = "ok";
        } else {
            $response->data = [];
            $response->errorAlert = "No se encontraron datos disponibles";
        }


        $response->send();
        break;

    case  "deleUser":
        $db = new db($ServerHost);
        $response = new Response();
        $User = new User($db);
        $id = $requestData["id"];
        $idUserChildren = $requestData["idUserChildren"];
        $value = $db->tableUpdateRow("bp_users_profile", array(
            array("estado", "E")
        ), $idUserChildren);

        if ($value)
            $response->success = "ok";
        else
            $response->errorAlert = "Ocurrio un error con la operacion ";

        $response->send();
        break;


    case  'acreditar':

        $db = new db($ServerHost);
        $response = new Response();
        $User = new User($db);
        $id = $requestData["id"];
        $amount = $requestData["amount"];

        if ($amount == 0) {
            $response->errorAlert = "Monto asignado invalido";
            $response->send();
            return;
        }

        //valida que exita target asociada
        if ($User->fecthCardAcreditDebit($id) != false) {
            $tarjeta = $User->fecthCardAcreditDebit($id);
            $columns = array(
                array("number_card", $tarjeta->card_numer),
                array("amount", $amount),
                array("id_users", $id)
            );
            $a = $db->tableInsertRow("bp_accredit", $columns);
            if ($a) {
                $b = $db->tableInsertRow("bp_transacciones", array(
                    array("address", "Registro de acreditación"),
                    array("amount", $amount),
                    array("type_transaction", "D"),
                    array("id_users", $id),
                    array("id_user_linea", "0"),
                ));
                if (!$b) {
                    $response->errorAlert = 'ocurrrio un error con la operacion consulte con soporte';
                    $response->send();
                    return;
                }
            } else {
                $response->errorAlert = 'ocurrrio un error con la operacion consulte con soporte';
                $response->send();
                return;
            }
        } else {
            $response->errorAlert = 'Tarjeta Invalida';
            $response->send();
            return;
        }

        $response->success = "ok";
        $response->send();
        break;


    case "TransaccionPaymentsClient":

        $db = new db($ServerHost);
        $response = new Response();
        $User = new User($db);
        $id = $requestData["id"];
        $transporte = $requestData['transporte'];


        //se valida si el usuario es hijo
        if ($User->isChildrenAcount($id)) {
            //true hijo
            $idusers = $User->fetch($id)->idparent;
        } else {
            $idusers = $id;
        }


        //false padre
        if ($User->existTransporte($transporte['id']) === false) {
            $response->errorAlert = "Transporte No se encuentra registrado";
            $response->send();
            return false;
        }

        $fetchPasajero = $User->fetch($idusers); //obtengo los datos del pasajero
        $fetchTransporte = $User->fetch($transporte['id']); //obtengo los datos del transportista

        //Tarifas
        if ($fetchPasajero->tipo === 'Normal')
            $AmountPayment = 0.30;
        if ($fetchPasajero->tipo === 'Estudiante' || $fetchPasajero->tipo === 'Tercera Edad' || $fetchPasajero->tipo === 'Discapacidad')
            $AmountPayment = 0.15;
        if (empty($fetchPasajero->tipo) || $fetch->tipo == null)
            $AmountPayment = 0.30;

        $Mycupo = (double)$User->amountUser($idusers);
        if ((double)$Mycupo > 0) {
            if ($Mycupo >= $AmountPayment) {
                //cupo
                $address = 'Ruta ' . $transporte['line'];

                //pasajero
                $ope_p = $User->transaccionClient($fetchPasajero->rowid, "Pago $fetchPasajero->nom $fetchPasajero->tipo $address", (double)$AmountPayment * -1, $fetchTransporte->rowid, "U");
                //transporte
                $ope_t = $User->transaccionClient($fetchTransporte->rowid, "Cobro $fetchPasajero->nom $fetchPasajero->tipo $address", $AmountPayment, $fetchPasajero->rowid, "T");
                if ($ope_p && $ope_t) {
                    $response->success = 1;
                    $response->send();
                } else {
                    $response->errorAlert = "ocurrio un error con la operacion cobro/pago. consulte con soporte";
                    $response->send();
                }
            } else {
                $response->errorAlert = "No tiene cupo";
                $response->send();
            }
        } else {
            $response->errorAlert = "No tiene cupo";
            $response->send();
        }


        $response->send();

        break;

    case "addCuentaBancaria":

        $db = new db($ServerHost);
        $response = new Response();
        $User = new User($db);
        $id = $requestData["id"];
        $banco = $requestData['banco'];

        //se valida si la informacion si encaso ya existe se actualiza la cuenta bancaria
        $bank = $db->fetchObject("select * from bp_bank where id_user = $id and banco = '$banco' limit 1");
        if ($bank) {
            if ($bank->rowid > 0) {
                $resul = $db->tableUpdateRow("bp_bank", array(
                    array("n_account", $requestData['cuenta']),
                    array("banco", $requestData['banco']),
                    array("id_user", $id),
                ), $bank->rowid);
                if ($resul)
                    $response->success = "ok";
                else
                    $response->errorAlert = "Ocurrrio un error con la operacion. Consulte con soporte";
                $response->send();
                return;
                die();
            }
        }

        $resul = $db->tableInsertRow("bp_bank", array(
            array("n_account", $requestData['cuenta']),
            array("banco", $requestData['banco']),
            array("id_user", $id),
        ));

        if ($resul)
            $response->success = "ok";
        else
            $response->errorAlert = "Ocurrrio un error con la operacion. Consulte con soporte";

        $response->send();
        break;

    case "ListadoCuentas":

        $db = new db($ServerHost);
        $response = new Response();
        $User = new User($db);
        $id = $requestData["id"];
        $resul = $db->fetchArray("select * from bp_bank where id_user = " . $id);
        if ($resul) {
            $response->data = $resul;
        } else {
            $response->errorAlert = "No se encontraron registros coincidentes";
        }

        $response->send();
        break;

    case  'bankProcess':

        $db = new db($ServerHost);
        $response = new Response();
        $User = new User($db);
        $id = $requestData["id"];
        $idBank = $requestData["idBank"];

        $CountProcess = $db->Count("bp_bank", "where id_user = $id and process = 1");
        if($CountProcess){
            $response->errorAlert = "Espere un momento porfavor...";
            $response->send();
            die();
        }

        $resul = $db->fetchObject("select * from bp_bank where rowid = " . $idBank . " and id_user = " . $id);
        if ($resul->rowid) {
            if ($resul->rowid && $resul->process == 0) {
                //processo retiro de dinero
                //se genera una trasaccion de retiro
                $amount = (double)$User->amountUser($id);
                if ($amount > 0) { //verificar el saldo a retirar
                    if ($amount >= 10) {
                        $value = $User->transaccionClient($id, "Transferencia de fondos " . $resul->banco, $amount * -1, 0, "R");
                        if ($value) {
                            sleep(3);
                            //processo terminado
                            $db->tableUpdateRow("bp_bank", array(
                                array("process", 0)
                            ), $idBank);
                            $response->success = "ok";
                            $response->send();
                            return;
                        }
                    } else {
                        sleep(3);
                        $response->errorAlert = "Lo sentimos, no podemos procesar su transacción debido a que el monto a debe ser mayor a $10";
                        $response->send();
                        return;
                    }
                } else {
                    sleep(3);
                    $response->errorAlert = "Lo sentimos, no podemos procesar su transacción debido a que el monto a depositar no puede ser 0";
                    $response->send();
                    return;
                }

            } else {
                if ($resul->process === 1) {
                    $response->errorAlert = "Su transacción esta siendo procesada";
                    $response->send();
                    return;
                }
            }
            return;
        } else {
            $response->errorAlert = "Banco selecionado no existe";
            $response->send();
            return;
        }

        $response->errorAlert = "Ocurrio un error con la operaciòn. Consulte con soporte";
        $response->send();
        break;

    case  'ConsultarbankProcess':
        $db = new db($ServerHost);
        $response = new Response();
        $User = new User($db);
        $id = $requestData["id"];
        $resul = $db->Count("bp_bank", "where id_user = $id and process = 1");
        if ($resul) {
            $response->success = "ok";
        } else {

        }
        $response->send();
        break;

}





die();

?>
