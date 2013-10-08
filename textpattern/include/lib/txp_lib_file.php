<?php

// -------------------------------------------------------------------------------------
// ARTICLE FILE AJAX EVENT FUNCTIONS

	function article_file_add($article=0,$file_id=0) {
	
		global $smarty;
		
		$article = gps('article',$article);
		$file_id = gps('file',$file_id);
		
		if ($article) {
		
			safe_update("textpattern",
				"FileID  = $file_id",
				"ID = $article OR Alias = $article");
		}
		
		/* 
		
		$filename  = fetch("Name","txp_file","id",$file_id);
		$extension = get_file_ext($filename);
		
		$smarty->assign('filename',$filename);
		$smarty->assign('file_id',$file_id);
		$smarty->assign('name',get_file_name($filename,12));
		$smarty->assign('extension',$extension);
		
		echo $smarty->fetch('article/file_name.tpl').'###'.$extension.'###'.$file_id;
		
		exit;
		
		*/
	}

// -------------------------------------------------------------------------------------
	function article_file_remove($article=0) {
	
		global $smarty;
		
		$article = gps('article',$article);
		
		safe_update("textpattern","FileID = -FileID","ID = $article OR Alias = $article");
		
		// exit;
	}
	
?>