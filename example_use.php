<?php
// Base API Class
require 'APIBaseClass.php';

require 'open311Api.php';

$new = new open311Api();

echo $new->get_service_list();

// This is broken. The example url on web page is broken.

echo $new->get_service_definition('033');

// pass numbers as strings to avoid problems

echo $new->get_service_requests();

echo $new->get_service_request('123456');

// hard to test post

// Debug information
die(print_r($new).print_r(get_object_vars($new)).print_r(get_class_methods(get_class($new))));
