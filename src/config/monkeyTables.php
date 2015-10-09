<?php 

/**
 * Default formats for things like dates or currencies should go here
 * The format may be different in PHP and JS
 *
 * 	TODO: define more config settings!
 **/

return 
[
	'date' =>
	[
		'displayDate' => 
	    [
	        'js'    => 'DD.MM.YYYY',
	        'php'   => 'd.m.Y'
	    ],

	    'displayDateShort' => 
	    [
	        'js'    => 'DD MM',
	        'php'   => 'd m'
	    ],

	    'dbDate' =>
	    [
	        'js'    => 'YYYY-MM-DD',
	        'php'   => 'Y-m-d'
	    ],

	    'dateAndTime' =>
	    [
	    	'php' => 'Y-m-d H:i:s'
	    ]	
	]
];