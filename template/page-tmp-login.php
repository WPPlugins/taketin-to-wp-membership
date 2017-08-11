<?php
/**
* Template Name: TAKETIN_ログイン
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
$separator = is_rtl() ? ' &rsaquo; ' : ' &lsaquo; ';
?>
<!DOCTYPE html>
	<!--[if IE 8]>
		<html xmlns="http://www.w3.org/1999/xhtml" class="ie8" <?php language_attributes(); ?>>
	<![endif]-->
	<!--[if !(IE 8) ]><!-->
		<html xmlns="http://www.w3.org/1999/xhtml" lang="ja">
	<!--<![endif]-->
	<head>
		<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
		<title><?php echo get_bloginfo( 'name', 'display' ) . $separator; ?><?php the_title(); ?></title>
		<?php 
			wp_enqueue_style( 'login' );
			do_action( 'login_head' );
		?>
		<style type="text/css">
		<!--
		.login h1 { margin-bottom: 30px; }
		-->
		</style>
	</head>
	<body class="login login-action-login wp-core-ui  locale-ja">
		<div id="login">
			<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
				<header class="article-header">
					<h1 class="page-title entry-title"><?php echo get_bloginfo( 'name', 'display' ); ?></h1>
				</header>
				<section class="entry-content cf" itemprop="articleBody">
					<?php the_content(); ?>
				</section>
			<?php endwhile; endif; ?>
		</div>
		<div class="clear"></div>
	</body>
	</html>
