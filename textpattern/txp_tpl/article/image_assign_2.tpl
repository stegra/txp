<html>
<head>
	<title>{$txt_select_image}</title>
	<script language="Javascript">
		{if $id eq 'NULL'}opener.document.article.new_article_image.value = '{$image}';{/if}
		opener.makeIFrame('index.php?event=article&step=image_view&nohead=1&image={$image}','{$image_type}');
		opener.toggleImagesLink('added','{$id}','{$txt_images}');
		opener.document.article['show_images'].value = 'yes';
		window.close();
	</script>
</head>
</html>
