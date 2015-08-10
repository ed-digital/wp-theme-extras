<?
	$section = ED()->getSection();
	
	if(!$section) return;
	
	$siblings = get_pages(array(
		"parent" => $section->ID
	));
	
	if(!count($siblings)) return;
	
	array_unshift($siblings, $section);
?>
<div class="sidebar-menu">
	<ul>
		<? foreach($siblings as $item): ?>
			<li><a href="<?=get_permalink($item->ID)?>"><?=$item->post_title?></a></li>
		<? endforeach; ?>
	</ul>
</div>