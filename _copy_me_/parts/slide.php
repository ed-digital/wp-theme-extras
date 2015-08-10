<?
	$title = $title ? $title : $item->post_title;
	$image = $image ? $image : get_field("featured_image", $item->ID);
?>
<div class="slide type-<?=$item->post_type?>">
	<a href="<?=get_permalink($item->ID)?>">
		<div class="post-type-label">
			<? if($item->post_type == "post"): ?>
				Featured Article
			<? elseif($item->post_type == "event"): ?>
				Featured Event
			<? endif; ?>
		</div>
		<div class="image" style="background-image: url('<?=$image['sizes']['slider']?>');"></div>
		<div class="info">
			<div class="title"><?=$title;?></div>
			<? if($item->post_type == "post"): ?>
				Posted <?=date("j F Y", strtotime($item->post_date))?>
			<? elseif($item->post_type == "event"): ?>
				<?=get_event_date_text($item)?>
			<? endif; ?>
		</div>
	</a>
</div>