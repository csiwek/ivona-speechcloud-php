<?php
require 'IvonaClient.php';
$ivona = new IvonaClient();

$text = "hola hola amigo";
header('Content-type: audio/mpeg');
echo $ivona->get($text, array('Language' => 'es-ES', 'VoiceName'=>'Conchita'))
?>
