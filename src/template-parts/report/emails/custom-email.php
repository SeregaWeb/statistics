<?php

$texts = $args;

$subject      = ! empty( $texts[ 'subject' ] ) ? $texts[ 'subject' ] : 'No Subject';
$project_name = ! empty( $texts[ 'project_name' ] ) ? $texts[ 'project_name' ] : '';
$subtitle     = ! empty( $texts[ 'subtitle' ] ) ? $texts[ 'subtitle' ] : '';
$message      = ! empty( $texts[ 'message' ] ) ? $texts[ 'message' ] : 'No message provided';

?>

<html>
<head>
    <style>
		body { font-family: Arial, sans-serif; }

		.header { background-color: #f2f2f2; padding: 10px; text-align: center; }

		.content { padding: 20px; }

		.footer { background-color: #f2f2f2; padding: 10px; text-align: center; }
    </style>
</head>
<body>
<div class='header'>
    <h2><?php echo $subject; ?></h2>
</div>
<div class='content'>
    <h3><?php echo $project_name; ?></h3>
    <h4><?php echo $subtitle; ?></h4>
    <p><?php echo $message; ?></p>
</div>
<div class='footer'>
    <p>Thank you for reading!</p>
</div>
</body>
</html>
