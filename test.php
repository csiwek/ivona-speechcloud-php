<?php


$a  = '{
    "Voice" : {
        "Name" : "string",
        "Language" : "string",
        "Gender" : "string"
    }
}';

$aa = json_decode ($a);

echo print_r($aa);
