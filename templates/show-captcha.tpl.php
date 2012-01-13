<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head profile="http://gmpg.org/xfn/11">
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
	<title><?php _e('WordPress &raquo; Mollom CAPTCHA test', MOLLOM_I8N); ?></title>
	<link rel="stylesheet" href="<?php get_bloginfo('siteurl'); ?>/wp-admin/css/install.css" type="text/css" />
	<style media="screen" type="text/css">
		html { background: #f1f1f1; }
		
		body {
			background: #fff;
			color: #333;
			font-family: "Lucida Grande", "Lucida Sans Unicode", Tahoma, Verdana, sans-serif;
			margin: 2em auto 0 auto;
			width: 700px;
			padding: 1em 2em;
			-webkit-border-radius: 12px;
			font-size: 62.5%;
		}

		a { color: #2583ad; text-decoration: none; }

		a:hover { color: #d54e21; }

		h2 { font-size: 16px; }

		p {
			padding-bottom: 2px;
			font-size: 1.3em;
			line-height: 1.8em;
		}	

		h1 {
			border-bottom: 1px solid #dadada;
			clear: both;
			color: #666666;
			font: 24px Georgia, "Times New Roman", Times, serif;
			margin: 5px 0 0 -4px;
			padding: 0;
			padding-bottom: 7px;
		}

		#error-page {
			margin-top: 50px;
		}

		#error-page p {
			font-size: 14px;
			line-height: 1.6em;
		}
		
		#error-page p.message {
			border: 1px solid #d91f1f;
			background: #f88b8b;
			padding: 0 0 0 5px;
		}
      </style>
</head>
<body id="error-page">
<h1><?php _e('Mollom CAPTCHA', MOLLOM_I18N); ?></h1>
<p><?php _e('This blog is protected by <a href="http://mollom.com">Mollom</a> against spam. Mollom is unsure whether your comment was spam or not. Please complete this form by typing the text in the image in the input box. Additionally, you can also listen to a spoken version of the text.', MOLLOM_I18N); ?></p>
<p><?php _e('You want Mollom also on your own Wordpress blog? Register with <a href="http://mollom.com">Mollom</a>, download and install <a href="http://wordpress.org/extend/plugins/wp-mollom">the plugin</a>!', MOLLOM_I18N); ?></p>
</body>
</html>
