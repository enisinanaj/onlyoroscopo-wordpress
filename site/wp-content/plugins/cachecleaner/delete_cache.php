<?php

$ch = curl_init();

// ... or an array of options
echo "setting options";
curl_setopt_array($ch, array(
	CURLOPT_URL => '10.0.0.4',
	CURLOPT_CUSTOMREQUEST, "PURGE",
	CURLOPT_FOLLOWLOCATION => true,
	CURLOPT_RETURNTRANSFER => 1
));

// execute
$output = curl_exec($ch);

// free
curl_close($ch);
var_dump($output);