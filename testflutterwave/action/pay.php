<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 0 );

if ( isset( $_POST[ 'submit' ] ) ) {
  if ( isset( $_POST[ 'pin' ] ) && $_POST[ 'pin' ] === '' ) {
    unset( $_POST[ 'pin' ] );
  }

  if ( isset( $_POST[ 'bvn' ] ) && $_POST[ 'bvn' ] === '' )  {
    unset( $_POST[ 'bvn' ] );
  }

  require_once( '../../test/flutterwave.php');
  $payment  = new FlutterWave( );
  $response = $payment->makeDebit(
  $_POST[ 'amount' ],
  $_POST[ 'cardno' ],
  str_shuffle( '1029384756784' ),
  $_POST[ 'cvv' ],
  $pin = ( isset( $_POST[ 'pin' ] ) ) ? $_POST[ 'pin' ] : '',
  $bvn = ( isset( $_POST[ 'bvn' ] ) ) ? $_POST[ 'bvn' ] : '',
  '',
  $_POST[ 'expirymonth' ],
  $_POST[ 'expiryyear' ],
  $_POST[ 'narration' ],
  'WebTest'
);
var_dump( $response );
}
