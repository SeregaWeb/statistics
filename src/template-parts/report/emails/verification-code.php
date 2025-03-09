<?php
$logo    = get_field_value( $args, 'logo' );
$name    = get_field_value( $args, 'name' );
$code    = get_field_value( $args, 'code' );
$contact = get_field_value( $args, 'contact' );
?>

<html>
<head>
    <style>
		body {
			font-family: Arial, sans-serif;
			line-height: 1.6;
			color: #333;
			padding: 0;
		}

		.email-container {
			max-width: 600px;
			margin: 20px auto;
			padding: 20px;
			text-align: left;
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

		.text-danger {
			color: rgb(255, 72, 0);
		}

		.email-body-text {
			margin: 0;
			line-height: 2;
		}

		.email-logo {
			text-align: left;
			padding: 0 20px;
			max-width: 600px;
			margin: 0 auto;
		}

		.email-logo-image {
			width: 120px;
			height: auto;
		}

		.text {
			color: #000000;
			margin: 0;
		}

		.separator {
			max-width: 600px;
			margin: 20px auto 10px;
		}
    </style>
</head>
<body>
<div class='email-logo'>
	<?php if ( ! empty( $logo ) ): ?>
        <img class='email-logo-image' src='<?php echo $logo; ?>' alt='logo'>
	<?php endif; ?>
</div>

<hr class='separator'>

<div class='email-container'>
    <div class='email-body'>
		<?php if ( ! empty( $name ) ): ?>
            <strong class='email-body-text'>Dear <?php echo $name; ?>,</strong>
		<?php endif; ?>
        <p class='email-body-text'> To complete your login, enter the following verification code:</p>
		<?php if ( ! empty( $code ) ): ?>
            <strong class='email-body-text'> <?php echo $code; ?></strong>
		<?php endif; ?>
        <p class='email-body-text'>This code will expire in 15 minutes.</p>
		<?php if ( ! empty( $contact ) ): ?>
            <p class='email-body-text'>
                If you were not expecting this code, <a class='text-danger'
                                                        href='mailto:<?php echo $contact; ?>'>contact</a> the
                administrator immediately.</p>
		<?php endif; ?>
        <br>
        <p class='email-body-text'>Thank you.</p>
    </div>
</div>
</body>
</html>