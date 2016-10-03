<?php
error_reporting( E_ALL );

require_once 'flutterwave.php';
$flutterwave  =   new FlutterWave( );
$resp         =   $flutterwave->makeDebit(  );
var_dump( $resp );
  ?>
