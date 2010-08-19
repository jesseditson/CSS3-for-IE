<?php

include_once('IECSS3.php'); // Include the IECSS3 class file.

$css = new IECSS3(); // defaults to jQuery, but may also be set to prototype like so: new IECSS3('prototype');

$css->add_styles("styles.css"); // for each of your external stylesheets needing to be processed, add them like this

$css->draw(); // when you are ready to echo the javascript, do it with this command.

?>