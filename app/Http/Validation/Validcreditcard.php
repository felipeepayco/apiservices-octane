<?php namespace App\Http\Validation;

class Validcreditcard
{
  var $number;
  var $type;
  var $errno;

  function Validcreditcard()
  {
    if(!defined('PHP_CREDITCARD_CLASS'))
    {   
        define('PHP_CREDITCARD_CLASS', 1);
        define('UNKNOWN', 0);
        define('MASTERCARD', 1);
        define('VISA', 2);
        define('AMEX', 3);
        define('DINNERS', 4);
        define('DISCOVER', 5);
        define('ENROUTE', 6);
        define('JCB', 7);

        define('CC_OK', 0);
        define('CC_ECALL', 1);
        define('CC_EARG', 2);
        define('CC_ETYPE', 3);
        define('CC_ENUMBER', 4);
        define('CC_EFORMAT', 5);
        define('CC_ECANTYPE', 6); 
    }
      $this->number = 0;
      $this->type = 'UNKNOWN';
      $this->errno = 'CC_OK';
  }

  /*
   * check method
   *   return true or false
   */

  function check($cardnum)
  {
    $this->number = $this->_strtonum($cardnum);

    if(!$this->detectType($this->number))
    {
      $this->errno = 'CC_ETYPE';
      return false;
    }
    if($this->type=="AMEX"){
      return true;
    }
    if($this->mod10($this->number))
    {
      $this->errno = 'CC_ENUMBER';
      return false;
    }

    return true;
  }

  /*
   * detectType method
   *   returns card type in number format
   */

  function detectType($cardnum = 0)
  {
    if($cardnum)
      $this->number = $this->_strtonum($cardnum);
    if(!$this->number)
    {
      $this->errno = 'CC_ECALL';
      return 'UNKNOWN';
    }

    if(preg_match("/^5[1-5]\d{14}$/", $this->number))
      $this->type = 'MASTERCARD';

    else if(preg_match("/^4(\d{12}|\d{15})$/", $this->number))
      $this->type = 'VISA';

    else if(preg_match("/^3[47]\d{13}$/", $this->number))
      $this->type = 'AMEX';

    else if(preg_match("/^[300-305]\d{11}$/", $this->number) ||
      preg_match("/^3[68]\d{12}$/", $this->number))
      $this->type = 'DINNERS';

    else if(preg_match("/^6011\d{12}$/", $this->number))
      $this->type = 'DISCOVER';

    else if(preg_match("/^2(014|149)\d{11}$/", $this->number))
      $this->type = 'ENROUTE';

    else if(preg_match("/^3\d{15}$/", $this->number) ||
      preg_match("/^(2131|1800)\d{11}$/", $this->number))
      $this->type = 'JCB';

    if(!$this->type)
    {
      $this->errno = 'CC_ECANTYPE';
      return 'UNKNOWN';
    }

    return $this->type;
  }

  /*
   * detectTypeString
   *   return string of card type
   */

  function detectTypeString($cardnum = 0)
  {
    if(!$cardnum)
    {
      if(!$this->type)
        $this->errno = 'CC_EARG';
    }
    else
      $this->type = $this->detectType($cardnum);

    if(!$this->type)
    {
      $this->errno = 'CC_ETYPE';
      return NULL;
    }

    switch($this->type)
    {
      case 'MASTERCARD':
        return "MASTERCARD";
      case 'VISA':
        return "VISA";
      case 'AMEX':
        return "AMEX";
      case 'DINNERS':
        return "DINNERS";
      case 'DISCOVER':
        return "DISCOVER";
      case 'ENROUTE':
        return "ENROUTE";
      case 'JCB':
        return "JCB";
      default:
        $this->errno = 'CC_ECANTYPE';
        return NULL;
    }
  }

  /*
   * getCardNumber
   *   returns card number, only digits
   */

  function getCardNumber()
  {
    if(!$this->number)
    {
      $this->errno = CC_ECALL;
      return 0;
    }

    return $this->number;
  }

  /*
   * errno method
   *   return error number
   */

  function errno()
  {
    return $this->errno;
  }

  /*
   * mod10 method - Luhn check digit algorithm
   *   return 0 if true and !0 if false
   */

  function mod10($cardnum)
  {
    try{
      for($sum=0, $i=strlen($cardnum)-1; $i >= 0; $i--)
      {
        $sum += $cardnum[$i];
        $doubdigit = "".(2 * $cardnum[--$i]);
        for($j = strlen($doubdigit)-1; $j >= 0; $j--)
          $sum += $doubdigit[$j];
      } 
      return $sum % 10; 
    }
    catch(\Exception $ex){
      return 1;
    }
    
  }

  /*
   * resetCard method
   *   clear only cards information
   */

  function resetCard()
  {
    $this->number = 0;
    $this->type = 0;
  }

  /*
   * strError method
   *   return string error
   */

  function strError()
  {
    switch($this->errno)
    {
      case 'CC_ECALL':
        return "Invalid call for this method";
      case 'CC_ETYPE':
        return "Invalid card type";
      case 'CC_ENUMBER':
        return "Invalid card number";
      case 'CC_EFORMAT':
        return "Invalid format";
      case 'CC_ECANTYPE':
        return "Cannot detect the type of your card";
      case 'CC_OK':
        return "Success";
    }
  }
  /*
   * _strtonum private method
   *   return formated string - only digits
   */

  function _strtonum($string)
  {
   return filter_var($string, FILTER_SANITIZE_NUMBER_INT);
  }
}
?>