<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" 
			content="width=device-width, initial-scale=1">
		<title>To do</title>
		<link rel="stylesheet"
			href="https://cdnjs.cloudflare.com/ajax/libs/skeleton/2.0.1/skeleton.min.css" >
	</head>
	<body>
		<h1>To Do</h1>
		<div class="container">
<?php

$todos = [];
if ( file_exists( 'todos.json' ) ) {
	$todos = json_decode( 
		file_get_contents( 'todos.json' ) 
	);
}
foreach( $todos as $index => $todo ) {
	echo '<div class="row">';
	echo '<form action="toggle.php" method="post">';
	printf(
		'<input type="hidden" name="id" value="%d">',
		$index
	);
	printf(
	   	'<button type="submit">[%s]</button>',
		$todo->done ? 'X' : ' '
	);
	echo '</form>';
	printf(
		' <span>%s</span>', 
		htmlspecialchars( $todo->name )
	);
	echo '</div>';
}
?>
		</div>
		<form action="add.php" method="post">
			<input type="text" name="new_todo">
			<button type="submit">Add new To-Do</button>
		</form>
	</body>
</html>

