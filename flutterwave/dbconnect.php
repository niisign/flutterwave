<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 1 );
/**
 *
 */
class TheDataBase
{
  protected $host;
  protected $database;
  protected $username;
  protected $password;
  protected $engine;

  function __construct( )
  {
    $this->host = '160.153.16.64';
    $this->database = 'thetellerapi';
    $this->username = 'thetellerapi';
    $this->password = 'thetellerapi';
    $this->engine   = 'mysql';
  }

  public function connection( )
  {
    try {
      return $_pdo = new PDO( "mysql:dbname=ttlrapitest", 'apimaster', 'Mas7erP@ss' );
    } catch ( PDOException $e ) {
      return "Error: ".$e->getMessage( );
    }
  }

  public function insert( array $data )
  {
    // check if all required data have been set
    if ( !isset( $data[ 'table' ] ) ) {
      // if the table name is not defined
      return "Error: table name not set!";
    } else {
      // assign the table name to the _table variable
      $_table = $data[ 'table' ];
    }

    // check if the table columns have been defined
    if ( !isset( $data[ 'cols' ] ) ) {
      // if the column names have not been defined
      return "Error: Column names not set!";
    } else {
      // assign the column names to the _col variable
      $_cols = $data[ 'cols' ];
    }

    // check if the values to insert have been defined
    if ( !isset( $data[ 'values' ] ) ) {
      // if values to insert have not been defined
      return "Error: Values not set!";
    } else {
      // assign defined values to the _values variable
      $_values = $data[ 'values' ];
    }

    // at this point all is set
    // create sql query

    // check if $_cols is an array
    if ( is_array( $_cols ) ) {
      $cols = '';
      $count  = 1;
      foreach ( $_cols as $key => $value ) {
        $cols   .=  $value;

        if ( $count < count( $_cols ) ) {
          $cols   .=  ', ';
          $count++;
        }
      }

    } else {
      $cols = $_cols;
    }

    // check if _values is an array
    if ( is_array( $_values ) ) {
      $val = '';
      $count = 1;
      foreach ( $_values as $key => $value ) {
        $val .= $key;

        if ( $count < count( $_values ) ) {
          $val .= ', ';
          $count++;
        }
      }
    } else {
      $val = $_values;
    }

    // prepare insert statment
    $_query = "INSERT INTO $_table ( $cols ) VALUES( $val )";
    $_pdo   = $this->connection( );
    $_stmt  = $_pdo->prepare( $_query );
    return $_stmt->execute( $_values );
  }

  public function select( array $data )
  {
    // check if all required data have been set
    if ( !isset( $data[ 'table' ] ) ) {
      // if the table name is not defined
      return "Error: table name not set!";
    } else {
      // assign the table name to the _table variable
      $_table = $data[ 'table' ];
    }

    // check if the table columns have been defined
    if ( !isset( $data[ 'cols' ] ) ) {
      // if the column names have not been defined
      return "Error: Column names not set!";
    } else {
      // assign the column names to the _col variable
      $_cols = $data[ 'cols' ];
    }

    // check if the values to insert have been defined
    if ( !isset( $data[ 'values' ] ) ) {
      // if values to insert have not been defined
      return "Error: Values not set!";
    } else {
      // assign defined values to the _values variable
      $_values = $data[ 'values' ];
    }

    // at this point all is set
    // create sql query

    if ( is_array( $_cols ) ) {
      $count  = 1;
      $col    = 'WHERE ';
      foreach ( $_cols as $key => $value ) {
        $col  .=  "$value=:$value";

        if ( $count < count( $_cols ) ) {
          $col  .=  ' AND ';
          $count++;
        }
      }

    } else {
       $col = '';
    }

    // write select query
    $_query = "SELECT * FROM $_table $col";
    $_pdo   =  $this->connection( );
    $_stmt  = $_pdo->prepare( $_query );
    $records  = $_stmt->execute( $_values );
    return $_stmt->fetchAll( PDO::FETCH_ASSOC );
  }
}

// $thedata = array(
//   'table' => 'merchants',
//   'cols'  =>  array(
//     'merchantid',
//     'api_key'
//   ),
//   'values'  =>  array(
//     ':merchantid' =>  'mahama',
//     ':api_key'    =>  'BisfL2g0bERMzatj'
//   )
// );

// $thedata  = array(
//   'table' => 'flw_transactions_logs',
//   'cols'  =>  array(
//     'trans_id',
//     'user',
//     'pay_id',
//     'dir',
//     'msg_type',
//     'pan',
//     'amount',
//     'country',
//     'currency',
//     'expiry_month',
//     'expiry_year',
//     'response_code',
//     'response_message',
//     'otp_trans_id',
//     'trans_reference',
//     'response_token',
//     'trans_status',
//     'narration'
//   ),
//   'values'      => array(
//     ':trans_id' =>  '102938475689',
//     ':user'     =>  'niisign',
//     ':pay_id'   =>  '0929384757',
//     ':dir'      =>  'T2F',
//     ':msg_type' =>  'REQ',
//     ':pan'      =>  '1111111111111111',
//     ':amount'   =>  10.50,
//     ':country'  =>  'GH',
//     ':currency' =>  'GHS',
//     ':expiry_month' =>  '05',
//     ':expiry_year'  =>  '19',
//     ':response_code'  => 'TT',
//     ':response_message' =>  'TEST RESPONSE',
//     ':otp_trans_id' =>  'TEST RESPONSE',
//     ':trans_reference'  =>  'TEST RESPONSE',
//     ':response_token' =>  'TEST RESPONSE',
//     ':trans_status' =>  'TEST RESPONSE',
//     ':narration' =>  'lets just make up something'
//   )
// );

// $theData = array(
//   'table' => 'flw_transactions_logs',
//   'cols'  =>  array(
//     'tran_id',
//     'user',
//     'pay_id',
//     'pan',
//     'amount',
//     'currency',
//     'country',
//     'dir',
//     'msg_type',
//     'response_code',
//     'response_message',
//     'otp_trans_id',
//     'trans_reference',
//     'response_token',
//     'trans_status'
//   ),
//   'values'  =>  array(
//     ':dir'  =>  'F2T',
//     ':msg_type' =>  'RSP',
//     ':response_code'  => 'TT',
//     ':response_message' =>  'TEST RESPONSE',
//     ':otp_trans_id' =>  'TEST RESPONSE',
//     ':trans_reference'  =>  'TEST RESPONSE',
//     ':response_token' =>  'TEST RESPONSE',
//     ':trans_id' =>  'TEST RESPONSE',
//     ':user' =>  'TEST RESPONSE',
//     ':pay_id' =>  'TEST RESPONSE',
//     ':pan'  =>  'TEST RESPONSE',
//     ':amount' =>  00.00,
//     ':currency' =>  'TT',
//     ':country'  =>  'TT',
//     ':trans_status' =>  'TEST RESPONSE'
//   )
// );
// $database = new TheDataBase( );
// // // $connect = $database->connection( );
// $select   = $database->insert( $thedata );
// var_dump( $select );
