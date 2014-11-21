<?php
namespace WarhornGamedayTools;

require_once("classes/PHPMailerAutoload.php");
require_once("constants.php");
require_once("database.php");

class EMail {

   private $_php_mailer;

   function __construct() {
      // Configure PHPMailer
      $mailer = new \PHPMailer();

      if (EMAIL_USE_SMTP) {
         $mailer->isSMTP();
         $mailer->Host = EMAIL_HOSTNAME;
         $mailer->Port = EMAIL_PORT;

         // Authentication
         $mailer->SMTPAuth = EMAIL_USE_AUTHENTICATION;
         if ($mailer->SMTPAuth) {
            $mailer->Username = EMAIL_USERNAME;
            $mailer->Password = EMAIL_PASSWORD;
         }//end if

         // Encryption
         if (EMAIL_USE_ENCRYPTION) {
            $mailer->SMTPSecure = EMAIL_ENCRYPTION_TYPE;
         }//end if
      }//end if

      $mailer->From = EMAIL_FROM_ADDRESS;
      if ("" != EMAIL_FROM_NAME) {
         $mailer->FromName = EMAIL_FROM_NAME;
      }//end if

      $this->_php_mailer = $mailer;
   }//END function __construct()

   public function addToEMailAddress($address, $name = "") {
      if ("" != $name) {
         $this->_php_mailer->addAddress($address, $name);
      } else {
         $this->_php_mailer->addAddress($address);
      }
   }//END public function addToEMailAddress($address, $name = "")

   public function setSubject($subject) {
      $this->_php_mailer->Subject = $subject;
   }//END public function setSubect($subject)

   public function setBody($body, $isHTML = false, $altBody = "") {
      if ($isHTML) {
         $this->_php_mailer->isHTML($isHTML);
         $this->_php_mailer->Body = $body;
         $this->_php_mailer->AltBody = $altBody;
      } else {
         $this->_php_mailer->Body = $body;
      }//end else
   }//END public function setBody($body)

   public function send() {
      return $this->_php_mailer->send();
   }//END public function send()

   public function setDebugLevel($dbgLevel) {
      $this->_php_mailer->SMTPDebug = $dbgLevel;
   }
}//END class EMail

$mailer = new EMail();
