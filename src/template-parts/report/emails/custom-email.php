<?php

$texts = $args;

$subject      = ! empty( $texts[ 'subject' ] ) ? $texts[ 'subject' ] : 'No Subject';
$project_name = ! empty( $texts[ 'project_name' ] ) ? $texts[ 'project_name' ] : '';
$subtitle     = ! empty( $texts[ 'subtitle' ] ) ? $texts[ 'subtitle' ] : '';
$message      = ! empty( $texts[ 'message' ] ) ? $texts[ 'message' ] : '';
$logo         = ! empty( $texts[ 'logo' ] ) ? $texts[ 'logo' ] : '';
?>

<html>
<head>
    <style>
		body { font-family: Arial, sans-serif; }

		.email-container {
			max-width: 600px;
			margin: 20px auto;
			padding: 20px;
			text-align: left;
		}

		.email-logo {
			text-align: left;
			padding: 0 20px 20px;
			max-width: 600px;
			margin: 0 auto;
			border-bottom: 1px solid #ccc;
		}

		.email-logo-image {
			width: 120px;
			height: auto;
		}

		.content { padding: 20px; }

		.content-title {
			margin-top: 0;
			font-size: 26px;
		}
    </style>
</head>
<body class="email-container">

<div class='email-logo'>
	<?php if ( ! empty( $logo ) ): ?>
        <img class='email-logo-image' src='<?php echo $logo; ?>' alt='logo'>
	<?php endif; ?>
</div>

<div class='content'>
    <h3 class="content-title"><?php echo $project_name; ?></h3>
    <h4 style="font-size: 22px;"><?php echo $subtitle; ?></h4>
    <p style="font-size: 18px;"><?php echo $message; ?></p>
</div>
</body>
</html>
