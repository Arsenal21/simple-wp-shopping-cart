<?php
/**
Template Page for the gallery overview

Follow variables are useable :

	$gallery     : Contain all about the gallery
	$images      : Contain all images, path, title
	$pagination  : Contain the pagination content

 You can check the content when you insert the tag <?php var_dump($variable) ?>
 If you would like to show the timestamp of the image ,you can use <?php echo $exif['created_timestamp'] ?>
**/
?>
<?php if (!defined ('ABSPATH')) die ('No direct access allowed'); ?><?php if (!empty ($gallery)) : ?>

<div class="ngg-galleryoverview" id="ngg-gallery-<?php echo esc_attr( $gallery->ID ) ?>">

<?php if ($gallery->show_slideshow) { ?>
	<!-- Slideshow link -->
	<div class="slideshowlink">
		<a class="slideshowlink" href="<?php echo esc_url( $gallery->slideshow_link ) ?>">
			<?php echo esc_html( $gallery->slideshow_link_text ) ?>
		</a>
	</div>
<?php } ?>

<?php if ($gallery->show_piclens) { ?>
	<!-- Piclense link -->
	<div class="piclenselink">
		<a class="piclenselink" href="<?php echo esc_url( $gallery->piclens_link ) ?>">
			<?php esc_html_e('[View with PicLens]','wordpress-simple-paypal-shopping-cart'); ?>
		</a>
	</div>
<?php } ?>
	
	<!-- Thumbnails -->
	<?php foreach ($images as $image) : ?>
	
	<div id="ngg-image-<?php echo esc_attr( $image->pid ) ?>" class="ngg-gallery-thumbnail-box" <?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attribute fragment sanitized by wp_kses_attr(). */ echo WPSC_Utility_Kses::escape_attributes( $gallery->imagewidth, 'div' ) ?> >
		<div class="ngg-gallery-thumbnail" >
			<a href="<?php echo esc_url( $image->imageURL ) ?>" title="" <?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attribute fragment sanitized by wp_kses_attr(). */ echo WPSC_Utility_Kses::escape_attributes( $image->thumbcode, 'a' ) ?> >
				<img title="<?php echo esc_attr( $image->alttext ) ?>" alt="<?php echo esc_attr( $image->alttext ) ?>" src="<?php echo esc_url( $image->thumbnailURL ) ?>" <?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attribute fragment sanitized by wp_kses_attr(). */ echo WPSC_Utility_Kses::escape_attributes( $image->size, 'img' ) ?> />
			</a>
			<span><?php echo do_shortcode($image->caption); ?></span>
		</div>
	</div>
	<?php if ( $gallery->columns > 0 && ++$i % $gallery->columns == 0 ) { ?>
	<br style="clear: both" />
	<?php } ?>
 	<?php endforeach; ?>
 	
	<!-- Pagination -->
    <?php echo wp_kses( $pagination, WPSC_Utility_Kses::wp_kses_post_tags() ) ?>
 	
</div>

<?php endif; ?>