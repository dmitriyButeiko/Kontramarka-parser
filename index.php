<html>
<head>
	<meta charset="UTF-8">
</head>
<body>
<?php  
	
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

	require_once "vendor/autoload.php";
	require_once "includes/DbHelper.php";
	require_once "parser/KontramarkaParser.php";
	


	$parser = KontramarkaParser::getParser();
	$dbHelper = new DbHelper($parser);
	
	$dbHelper->createInitialTables();
	$dbHelper->updateTables($parser);
?>
</body>
</html>