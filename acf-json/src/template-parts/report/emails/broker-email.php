<?php

$data = $args;

$email_project = ( isset( $data[ 'project_email' ] ) && $data[ 'project_email' ] )
	? "<p class='text'>Email: <a href='" . $data[ 'project_email' ] . "'>" . $data[ 'project_email' ] . "</a></p>" : "";

$phone_project = ( isset( $data[ 'project_phone' ] ) && $data[ 'project_phone' ] )
	? "<p class='text'>Phone: " . $data[ 'project_phone' ] . "</p>" : "";


?>
<html>
<head>
    <style>
		body {
			font-family: Arial, sans-serif;
			line-height: 1.6;
			color: #333;
			margin: 0;
			padding: 0;
		}

		.email-container {
			max-width: 600px;
			margin: 20px auto;
			padding: 20px;
			background-color: #f9f9f9;
			text-align: center;
		}

		.email-header {
			font-size: 20px;
			font-weight: bold;
			color: #000000;
			max-width: 600px;
			margin: 12px auto 20px;
			text-align: center;

		}

		.email-body {
			font-size: 16px;
			color: #444;
		}

		.email-footer {
			max-width: 600px;
			margin: 20px auto;
			font-size: 14px;
			color: #777;
			text-align: left;
		}

		.email-logo {
			text-align: center;
		}

		.email-logo-image {
			width: 180px;
			height: auto;
		}

		.text {
			color: #000000;
			margin: 0;
		}

    </style>
</head>
<body>

<div class='email-logo'>
    <img class='email-logo-image' src="<?php echo $data[ 'logo' ]; ?>" alt="logo">
</div>

<div class='email-header'><?php echo $data[ 'subject' ]; ?></div>

<div class='email-container'>
    <div class='email-body'>
        <p><?php echo $data[ 'text' ]; ?></p>
    </div>
</div>
<div class='email-footer'>
	<?php echo $email_project . $phone_project; ?>
</div>
</body>
</html>