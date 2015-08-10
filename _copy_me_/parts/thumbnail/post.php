<?
	$image = get_field("featured_image", $post->ID);
	$url = get_permalink($post->ID);
?>
<article>
	<div class="post-thumbnail post">
		<div class="image">
			<a href="<?=$url?>"><img src="<?=$image['sizes']['post-thumb']?>"></a>
		</div>
		<div class="info">
			<div class="date">
				Posted <?=date("j F Y", strtotime($post->post_date));?>
			</div>
			<div class="title">
				<?=$post->post_title?>
			</div>
			<div class="options">
				<a href="<?=$url?>">More Details &rarr;</a>
			</div>
		</div>
	</div>
</article>