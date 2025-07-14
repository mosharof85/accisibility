<?php
/**
 * Displays the head section of the theme
 *
 * @package Lambda
 * @subpackage Frontend
 * @since 0.1
 *
 * @copyright (c) 2015 Oxygenna.com
 * @license http://wiki.envato.com/support/legal-terms/licensing-terms/
 * @version 1.59.23
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>" />
        <meta content="width=device-width, initial-scale=1.0" name="viewport">
        <link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" /><?php

        $site_icon_id = get_option('site_icon');
        if ($site_icon_id === '0' || $site_icon_id === false) {
            oxy_favicons();
        }

        wp_head(); ?>
    </head>
    <body <?php body_class(); ?>>
        <div class="pace-overlay"></div>
        <?php

        ob_start();
        oxy_create_nav_header();
        $output = ob_get_contents();
        ob_end_clean();

        $pattern = '/<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="\.main-navbar">/i';
        $replacement = '<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".main-navbar" aria-label="Toggle navigation">';
        $content = preg_replace($pattern, $replacement, $output);
        echo $content;

        ?>
        <div id="content" role="main">