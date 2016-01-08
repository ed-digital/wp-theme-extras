<h2><?=get_the_title()?></h2>
<? if($this->isNewsPost): ?>
	<p>Posted <?=date("j F Y", strtotime($post->post_date)); ?></p>
<? endif; ?>
<? the_content(); ?>