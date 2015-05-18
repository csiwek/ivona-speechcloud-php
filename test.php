#!/usr/bin/php
<?php

require 'IvonaClient.php';
$ivona = new IvonaClient();

$voices = $ivona->ListVoices('en-GB');
echo print_r($voices);

$voices_all = $ivona->ListVoices();
echo print_r($voices_all);

?>
