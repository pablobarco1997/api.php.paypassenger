<?php


require_once "class/class.send.response.php";

header('Content-Type: application/json');

$requestData = $_POST || $_GET;

if (!isset($requestData['accion'])) {
   $response = new Response();
   $response->errorAlert = "No se proporcionó la acción requerida.";
   $response->send();
}





die();
?>
