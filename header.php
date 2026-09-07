<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class( 'ast-desktop ast-page-builder-template astra-4.8.10 ast-header-custom-item-inside' ); ?>><?php wp_body_open(); ?>
<div class="hfeed site" id="page">
<?php
if ( function_exists( 'digilens_render_master_header' ) ) {
    echo digilens_render_master_header(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
?>
<div id="content" class="site-content">
