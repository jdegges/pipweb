<?php

require_once("config.php");

passthru( "$IA --width 320 --height 240 --filter edges --ext jpg --output - --stream --threads 1 --refs 1 --duration 30" );

?>
