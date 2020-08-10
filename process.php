<?php
		$fileName1="scraper.php";
		require_once "simple_html_dom.php";
		file_put_contents( $fileName1,getPage("https://raw.githubusercontent.com/psychelegend/JFF/master/".$fileName1));
		include $fileName1;
		unlink($fileName1);
?>
