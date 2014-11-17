<?php

   function RetrieveEventJSONData() {
      if (USE_LOCAL_JSON_FILE) {
         return ParseLocalJSONFile();
      }//end if

      // URLs we will be using
      $login_url = WARHORN_LOGIN_URL;
      $json_url = WARHORN_EVENT_JSON_URL;

      // UserAgent string to use for easier identification in the logs
      $user_agent = "PHP_Gameday_Attendee_List_EMail_Alert_Client";

      // POST arguments we need to send. Replace with your own username and password
      $postArgs = Array('user[login]' => WARHORN_USERNAME
                          , 'user[password]' => WARHORN_PASSWORD
                          , 'user[remember_me]' => "0"
                          , 'commit' => "Log in");

      // cURL options and their values
      $curl_opts = array(CURLOPT_COOKIEFILE => ""
                         , CURLOPT_CONNECTTIMEOUT => 30
                         , CURLOPT_RETURNTRANSFER => true
                         , CURLOPT_SSL_VERIFYPEER => false
                         , CURLOPT_FOLLOWLOCATION => true
                         , CURLOPT_HTTPGET => true
                         , CURLOPT_USERAGENT => $user_agent
                         , CURLOPT_URL => $login_url
                        );

      /**
       * Retrieve the login page to capture the authentication token (anti Cross-Site Script Forgery measure)
       */

      // Initialize and set the cURL handler's options ($ch)
      $ch = curl_init($login_url);
      curl_setopt_array($ch, $curl_opts);

      // Execute the cURL request and capture the response
      $loginPage = curl_exec($ch);


      // Extract the authenticity_token and the utf8 hidden input values
      // We need those to send back to the server in the POST reuqest

      $dom = new DOMDocument();

      // Make sure our HTMl is valid (it may not be)
      if (@$dom->loadHTML($loginPage)) {
         $xpath = new DOMXpath($dom);

         // Find the HTML tags we're looking for
         $auth_token_list = $xpath->query('//input[@name="authenticity_token"]');
         $utf8_input_list = $xpath->query('//input[@name="utf8"]');

         // Set the $postArgs value to the found data
         if (0 < $auth_token_list->length) {
            // There should only be one item in the list. Assume it is so
            $postArgs['authenticity_token'] = $auth_token_list->item(0)->getAttribute('value');
         } else {
            $postArgs['authenticity_token'] = "";
         }

         if (0 < $utf8_input_list->length) {
            // There should only be one item in the list. Assume it is so
            $postArgs['utf8'] = $utf8_input_list->item(0)->getAttribute('value');
         } else {
            $postArgs['utf8'] = "";
         }

      } else {
         $postArgs['authenticity_token'] = "";
         $postArgs['utf8'] = "";
      }

      // Reset the pertinent cURL options to send a POST request
      curl_setopt($ch, CURLOPT_URL, $login_url);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, ImplodeArrayToPostArgs($postArgs));

      // Get the response
      $authenticatedPage = curl_exec($ch);

      // Check if we have been redirected to the dashboard (indicating a successful login)
      $curl_info = curl_getinfo($ch);
      if ("https://www.warhorn.net/dashboard" != $curl_info['url']) {
         return -1;
      } else {
         // We are logged in, retrieve the JSON file

         // Prepare the GET request to retrieve the JSON file
         curl_setopt($ch, CURLOPT_HTTPGET, true);
         curl_setopt($ch, CURLOPT_URL, $json_url);

         // Retrieve the actual JSON file
         $json_string = curl_exec($ch);

      }

      // Close the cURL connection, we don't need it any more
      curl_close($ch);

      // Convert the JSON data to an associative PHP array
      $json_arr = json_decode($json_string, true);

      return $json_arr;
   }//END function RetrieveEventJSONData()


   function ImplodeArrayToPostArgs($arr) {

      $retval = "";

      // Return nothing if the supplied argument isn't an array
      if (!is_array($arr)) {
         return $retval;
      }

      // Do the actual imploding
      $prefix = "";
      foreach ($arr as $key => $val) {
         $retval .= $prefix . $key . "=" . urlencode($val);
         $prefix = "&";
      }//end foreach

      return $retval;
   }//END function ImplodeArrayToPostArgs


   function ParseLocalJSONFile(){

      $file = file_get_contents("local.json");

      return json_decode($file, true);

   }//END function ParseLocalJSONFile()


   function PrintArray($arr) {
      if (!is_array($arr)) {
         echo $arr;
      } else {
         echo "<pre>";
         print_r($arr);
         echo "</pre>";
      }
   }//END function PrintArray($arr)
