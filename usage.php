<?php

include_once('IECSS3.php'); // Include the IECSS3 class file.

$css = new IECSS3(); // defaults to jQuery, but may also be set to prototype like so: new IECSS3('prototype');
					// You can also change the library by using the set_library() method, either calling 'jQuery' or 'prototype' as the only argument.

$css->set_root("includes/stylesheets"); // if your stylesheets are not in the same directory as the script running IECSS3, set a relative root using the set_root method.

$css->add_styles("styles.css"); // for each of your external stylesheets needing to be processed, add them like this. The add_styles method accepts either a single string, or an array of styles.

$css->draw(); // when you are ready to echo the javascript, do it with this command. You may also optionally set the styles and draw them at the same time, passing in either an array or a string to the draw method with the names of your stylesheets.

// That's it. All of these methods are optional, but the script won't do anything unless you at least:
	// - Instantiate the IECSS3 Class
	// - Call the draw method with at least one stylesheet.
?>