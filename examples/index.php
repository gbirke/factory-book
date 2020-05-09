<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" 
			content="width=device-width, initial-scale=1">
		<title>Examples</title>
		<link rel="stylesheet"
			href="https://cdnjs.cloudflare.com/ajax/libs/skeleton/2.0.1/skeleton.min.css" >
	</head>
	<body>
		<h1>Example overview</h1>
		<div class="container">
			<div>The following examples should look and work absolutely identical, only the PHP code is different</div>
			<ul>
<?php
foreach( glob( '[0-9][0-9]_*' ) as $dir ) {
	if ( !is_dir( $dir ) ) {
		continue;
	}
	$index = "$dir/index.php";
	if ( file_exists( "$dir/public" ) ) {
		$index = "$dir/public/index.php";
	}
	printf(
		"<li><a href=\"%s\">%s</a></li>\n",
		htmlspecialchars( $index ),
		htmlspecialchars( $dir )
	);
}
?>
		</ul>
	</body>
</html>

