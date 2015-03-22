<?php
namespace Civi\Cxn\Rpc\Exception;

use Exception;

/**
 * Class UserErrorException
 *
 * phpseclib reports errors via user_error(). When running as a server, we
 * often want to catch these so that we can send a well-formed response.
 *
 * @package Civi\Cxn\Rpc\Exception
 */
class UserErrorException extends \Exception {

  public static function adapt($callable) {
    $errors = array();

    set_error_handler(function ($errno, $errstr, $errfile, $errline, $errcontext) use (&$errors) {
      if (!(error_reporting() & $errno)) {
        return;
      }
      if ($errno & (E_USER_ERROR | E_USER_NOTICE)) {
        $errors[] = array($errno, $errstr, $errfile, $errline);
      }
    }, E_USER_ERROR | E_USER_NOTICE);

    $e = NULL;
    try {
      $result = call_user_func($callable);
    }
    catch (\Exception $e2) {
      $e = e2;
    }

    restore_error_handler();

    if ($e) {
      throw $e;
    }

    if (!empty($errors)) {
      $msg = '';
      foreach ($errors as $error) {
        $msg .= $error[1] . "\n";
      }
      throw new UserErrorException($msg);
    }

    return $result;
  }

}
