<?php 

/**
 * Default formats for things like dates or currencies should go here
 * The format may be different in PHP and JS
 *
 * 	TODO: define more config settings!
 **/

return 
[
	'general' => 
	[
		'dataSet' => [
			'requireName' => false
		]
	],
	
	'export' => 
	[
		'cookie' => '_sid',
		'printView' => 
		[
			'tablesUrl' => '/monkeyTablesPrintView.php',
			'reportUrl' => '/monkeyReportsPrintView.php'
		]
	],

	'service' => 
	[
		'html2pdf' => 'localhost:8088'
	],

	'businessPaper' =>
	[
		'paperSize' => 
		[
			'name' 	=> 'A4'
		],
		'orientation' => 'landscape',
		'margin' => 1,
		'units' => 'cm'
	],

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
	],

    'format' =>
    [
        'currency_symbol_prepend' => true
    ]
];