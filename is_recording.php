<?php

require_once("config.php");

$ps = `$PS | $GREP -i $IA | $WC -l`;

if( $ps > 2 ) {
    echo "1";
    return 1;
} else {
    echo "0";
    return 0;
}

?>
