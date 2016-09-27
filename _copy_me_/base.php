<?
	ED()->addCSS("assets-built/css/screen.css");
	ED()->addJS("assets-src/js/bundle.js");
?>
<!DOCTYPE HTML>
<html class="no-js" lang="en">
	<head>
		<title><? bloginfo( 'name' ); ?><? wp_title( '|' ); ?></title>
		<meta charset="<? bloginfo( 'charset' ); ?>" />
	  	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="pingback" href="<? bloginfo( 'pingback_url' ); ?>" />
		<link rel="shortcut icon" href="<?= get_stylesheet_directory_uri(); ?>/favicon.png"/>
		<? wp_head(); ?>
	</head>
	<body <? body_class(); ?>>
		
		<div id="site-wrapper">
			
			<header id="header">
				
				<div class="wrapper">
					<div class="row">
						<div class="col-8">
							<h1><a href="/" title="Go back to the homepage"><span>Website</span></a></h1>
						</div>
						<div class="col-4">
							<nav>
								<div class="main-menu">
									<? wp_nav_menu(); ?>
								</div>
							</nav>
						</div>
					</div>
					<div class="faux-row header-bottom"></div>
				</div>
				
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