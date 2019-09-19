<?
	ED()->addCSS("assets-built/css/screen.css");
	ED()->addJS("assets-built/js/bundle.js");
?>
<!DOCTYPE HTML>
<html class="no-js" lang="en">
	<head>
		<title><? bloginfo( 'name' ); ?><? wp_title( '|' ); ?></title>
		<meta charset="<? bloginfo( 'charset' ); ?>" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<!-- <meta name="theme-color" content="#000000"> -->
		<meta property="og:site_name" content="<?=get_bloginfo('name')?>" />
		<meta property="og:title" content="<? bloginfo( 'name' ); ?><? wp_title( '|' ); ?>">
		<meta property="og:url" content="<?=ED()->siteURL.$_SERVER['REQUEST_URI']?>">
		<meta property="og:type" content="article">
		<!-- <meta property="fb:app_id" content=""> -->
		<!-- <meta property="og:image" content="<?=ED()->themeURL?>/ograph-img.png">
		<meta property="og:image:width" content="1200">
		<meta property="og:image:height" content="630"> -->
		<meta property="og:description" content="<?=get_field('meta_description')?>">
		<meta name="description" content="<?=get_field('meta_description')?>">
		<link rel="pingback" href="<? bloginfo( 'pingback_url' ); ?>" />
		<link rel="shortcut icon" href="<?= get_stylesheet_directory_uri(); ?>/favicon.png"/>
		<? wp_head(); ?>
	</head>
	<body <? body_class(); ?>>
		
		<div id="site-wrapper">
			
			<header id="header">
				
				<?=C()->Site->Header() ?>
				
			</header>
			
			<div id="site-inner" data-page-container="true">
				<div class="wrapper">
					<div class="faux-row">
						<? if($this->showBreadcrumbs): ?>
							Breadcrumbs > Breadcrumbs
						<? endif; ?>
					</div>
				</div>
				<? $this->showContent(); ?>
			</div>
			
			<div id="site-footer">
				<div class="wrapper">
					<div class="row">
						<div class="col-6">
							&copy; Copyright 2015.
						</div>
					</div>
				</div>
			</div>
			
		</div>
		
		<? wp_footer(); ?>
	</body>
</html>