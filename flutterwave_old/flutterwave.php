<?php
ini_set( 'display_errors', 1 );
/**
*
*/
class FlutterWave
{
  public $post;
  private $_endpoint;
  protected $_merchant_key;
  protected $_api_key;
  protected $_url;
  protected $database;

  function __construct( )
  {
    $this->_merchant_key  = 'tk_MGCqgzND0h';
    $this->_api_key       = 'tk_UQfn8oYuABFRyp6Tg2iM';
    $this->_url           = 'http://staging1flutterwave.co:8080/pwc/rest/card/mvva/pay';
    require_once 'dbconnect.php';
    $this->database       = new TheDataBase( );
  }

  private function encrypt3Des( $data, $key ){
    //Generate a key from a hash
    $key = md5( utf8_encode( $key ), true );

    //Take first 8 bytes of $key and append them to the end of $key.
    $key .= substr( $key, 0, 8 );

    //Pad for PKCS7
    $blockSize = mcrypt_get_block_size( 'tripledes', 'ecb' );
    $len = strlen( $data );
    $pad = $blockSize - ( $len % $blockSize );
    $data = $data.str_repeat( chr( $pad ), $pad );

    //Encrypt data
    $encData = mcrypt_encrypt( 'tripledes', $key, $data, 'ecb' );

    //return $this->strToHex($encData);

    return base64_encode( $encData );
  }

  public function decrypt3Des( $data, $secret ){
    //Generate a key from a hash
    $key = md5(utf8_encode($secret), true);

    //Take first 8 bytes of $key and append them to the end of $key.
    $key .= substr($key, 0, 8);

    $data = base64_decode($data);

    $data = mcrypt_decrypt('tripledes', $key, $data, 'ecb');

    $block = mcrypt_get_block_size('tripledes', 'ecb');
    $len = strlen($data);
    $pad = ord($data[$len-1]);

    return substr( $data, 0, strlen( $data ) - $pad );
  }

  public function makeDebit( $amount, $cardno, $custid, $cvv, $pin, $bvn, $cardtype, $expirymonth, $expiryyear, $narration, $decrypted )
  {
    $trans_id   = $this->transId( $decrypted );
    $api_trans  = array(
      'table' => 'api_transactions',
      'cols'  =>  array(
        'trans_id',
        'user',
        'pay_id',
        'dir',
        'msg_type',
        'pan',
        'amount',
        'country',
        'currency',
        'expiry_month',
        'expiry_year',
        'narration'
      ),
      'values'      => array(
        ':trans_id' =>  $trans_id,
        ':user'     =>  $decrypted,
        ':pay_id'   =>  $custid,
        ':dir'      =>  'T2F',
        ':msg_type' =>  'REQ',
        ':pan'      =>  $this->maskPan( $cardno ),
        ':amount'   =>  $amount,
        ':country'  =>  'GH',
        ':currency' =>  'GHS',
        ':expiry_month' =>  $expirymonth,
        ':expiry_year'  =>  $expiryyear,
        ':narration' =>  $narration
      )
    );

    $this->database->insert( $api_trans );
    $api_trans[ 'table' ] = 'flw_transactions_logs';
    $this->database->insert( $api_trans );

    $authmodel  = 'NOAUTH';

    if ( $pin != 0 ) {
      $authmodel  =   'PIN';
    }

    if ( $bvn != 0 ) {
      $authmodel  =   'BVN';
    }

    $_url   = $this->_url;
    $_card  = [
      'amount'    => $amount,
      'authmodel' =>  $authmodel,
      'cardno'    => $cardno,
      'currency'  => 'GHS',
      'custid'    => $trans_id,
      'country'   => 'GH',
      'cvv'       => $cvv,
      'pin'       => $pin,
      'bvn'       => $bvn,
      'cardtype'  => $cardtype,
      'expirymonth' => $expirymonth,
      'expiryyear'  => $expiryyear,
      'merchantid'  => $this->_merchant_key,
      'narration'   => $narration,
      'user'      => $decrypted,
      'responseurl' =>  ''
    ];

    // GENERATE MESSAGE FOR LOG FILE
    $message 		=		$this->logMessage( 'ttlr request foreign', $_card );
    // WRITE LOG MESSAGE TO LOG FILE
    $this->intoLog( "$message\n\r" );

    foreach ( $_card as $key => $value ) {
      if ( $key != 'merchantid' ) {
        if ( $key != 'user' ) {
          $_card[ $key ] = $this->encrypt3Des( $value, 'tk_UQfn8oYuABFRyp6Tg2iM' );
        }

      }
    }

    $_data    =   json_encode( $_card );

    $_response =   $this->jsonRequest( $_url, $_data );

    if ( isset( $_response[ 'data' ] ) ) {
      $_response[ 'data' ][ 'extid' ] = $trans_id;
    }

    return $this->isResponsible( $_response );

  }

  private function jsonRequest( $url, $data )
  {
    $_headers   =   array(
      'Content-Type: application/json'
    );

    $data = json_decode( $data, true );

    if ( isset( $data[ 'user' ] ) ) {
      $_the_user = $data[ 'user' ];
      unset( $data[ 'user' ] );
    }

    $data = json_encode( $data );

    $_curl  = curl_init( );
    curl_setopt( $_curl, CURLOPT_URL, $url );
    curl_setopt( $_curl, CURLOPT_RETURNTRANSFER, TRUE );
    curl_setopt( $_curl, CURLOPT_HEADER, FALSE );
    curl_setopt( $_curl, CURLOPT_POST, TRUE );
    curl_setopt( $_curl, CURLOPT_SSL_VERIFYPEER, FALSE );
    curl_setopt( $_curl, CURLOPT_SSL_VERIFYHOST, FALSE );
    curl_setopt( $_curl, CURLOPT_HTTPHEADER, $_headers );
    curl_setopt( $_curl, CURLOPT_POSTFIELDS, $data );

    if ( $_resp = curl_error( $_curl ) ) {
      return $_resp;
    } else {
      $response = json_decode( curl_exec( $_curl ), true );
      $response[ 'user' ] = $_the_user;
      return $response;

    }

  }

  private function isResponsible( $response )
  {
    // INSERT INTO DB LOG
    $theData = array(
      'table' => 'flw_transactions_logs',
      'cols'  =>  array(
        'dir',
        'msg_type',
        'response_code',
        'response_message',
        'otp_trans_id',
        'trans_reference',
        'response_token',
        'trans_status'
      ),
      'values'  =>  array(
        ':dir'  =>  'F2T',
        ':msg_type' =>  'RSP',
        ':response_code'  => ( isset( $_response[ 'data' ] ) ) ? $response[ 'data' ][ 'responsecode' ] : '',
        ':response_message' =>  ( isset( $_response[ 'data' ] ) ) ? $response[ 'data' ][ 'responsemessage' ] : '',
        ':otp_trans_id' =>  ( isset( $_response[ 'data' ] ) ) ? $response[ 'data' ][ 'otptransactionidentifier' ] : '',
        ':trans_reference'  =>  ( isset( $_response[ 'data' ] ) ) ? $response[ 'data' ][ 'transactionreference' ] : '',
        ':response_token' =>  ( isset( $_response[ 'data' ] ) ) ? $response[ 'data' ][ 'responsetoken' ] : '',
        ':trans_status' =>  $response[ 'status' ]
      )
    );

    $this->database->insert( $theData );

    // GENERATE MESSAGE FOR LOG FILE
    $message 		=		$this->logMessage( 'ttlr response foreign', $response );
    // WRITE LOG MESSAGE TO LOG FILE
    $this->intoLog( "$message\r\n" );

    if ( isset( $response[ 'data' ] ) && isset( $response[ 'status' ] ) ) {
      //  check for successful transactions
      $_status      =   strtolower( $response[ 'status' ] );
      if ( $_status != 'success' ) {
        return 300;
      } elseif ( $_status   =   'success') {  // if the transaction was successful
        $_data      =   $response[ 'data' ];
        if ( $_data[ 'responsecode' ] == '00' && $_data[ 'responsemessage' ] == 'Successful' ) {

          return array(
            'status'  =>  'success',
            'data'    =>  array(
              'code'  =>  100,
              'description' =>  'Payment made successfully',
              'extid '=>  $_data[ 'extid' ]
            )
          ); // successful

        } elseif ( $_data[ 'responsecode' ] == '2' && $_data[ 'responsemessage' ] == 'Declined' ) {

          return array(
            'status'  =>  'success',
            'data'    =>  array(
              'code'  =>  101,
              'description' =>  'Payment declined',
            )
          ); // transaction Declined

        } elseif ( $_data[ 'responsecode' ] == 'RR' ) {
          return array(
            'status'  =>  'success',
            'data'    =>  array(
              'code'  =>  102,
              'description' =>  $_data[ 'responsemessage' ]
            )
          );  // Invalid expiry date entered
        } elseif ( $_data[ 'responsecode' ] == '7' ) {

          return array(
            'status'  =>  'success',
            'data'    =>  array(
              'code'  =>  301,
              'description' =>  'Card cannot be proccessed'
            )
          ); // for cards that cannot be processed

        } else {
          return array(
            'status'  =>  'success',
            'data'    =>  array(
              'code'  =>  $_data[ 'responsecode' ],
              'description' =>  $_data[ 'responsemessage' ]
            )
          );
        }

      }

    } else {
      // unknown response message
      return array(
        'status'  =>  'fail',
        'data'    =>  array(
          'code'  =>  900,
          'description' =>  'Request could not be processed.'
        )
      );

    }
  }

  private function logMessage( string $header, array $data )
  {
    $_message = date( 'Y-m-d H:i:s' ).' | '.strtoupper( $header )."\r\n";
    foreach ( $data as $key => $value ) {

      if ( is_array( $value ) ) {
        $_message .= date( 'Y-m-d H:i:s' ).' | '."$key :\r\n";
        foreach ($value as $key => $val) {
          $_message .= date( 'Y-m-d H:i:s' ).' | '."$key	:	$val\r\n";
        }

      } else {

        if ( $key == "cardno" ) {
          $_len   = strlen( $value );
          $_len   = $_len - 8;
          $_start = substr( $value, 0, 4 );
          $_end   = substr( $value, -4 );
          $_counter = 1;
          $_mid     = 'X';

          while ( $_counter < $_len ) {
            $_mid .= 'X';
            $_counter++;
          }
          $value = $_start.$_mid.$_end;

        } elseif ( $key == 'cvv' || $key == 'pin' ) {
          $_len   = strlen( $value );
          $_counter = 1;
          $value  = 'X';

          while ( $_counter < $_len ) {
            $value .= 'X';
            $_counter++;
          }

        }

        $_message .= date( 'Y-m-d H:i:s' ).' | '."$key	:	$value\r\n";
      }
    }

    return $_message."\r\n";
  }

  private function intoLog( $message )
  {
    $_fwrite  =   file_put_contents( 'ttlrapi.txt', $message, FILE_APPEND );
    return $_fwrite;
  }

  private function maskPan( $pan )
  {
    $_len   = strlen( $pan );
    $_len   = $_len - 8;
    $_start = substr( $pan, 0, 4 );
    $_end   = substr( $pan, -4 );
    $_counter = 1;
    $_mid     = 'X';

    while ( $_counter < $_len ) {
      $_mid .= 'X';
      $_counter++;
    }
    return $_start.$_mid.$_end;
  }

  private function transId( $user )
  {
    return $extid  = substr( $user, 0, 3 )."-".substr( str_shuffle( '1029384756' ), 1, 8 );
  }


}

?>
