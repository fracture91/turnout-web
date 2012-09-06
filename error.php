<?php
	require_once 'lib/TemplatePower.class.php';
	//session_destroy();
	
	$tpl = new TemplatePower('template/master.html');
	$tpl->assignInclude('content', 'template/error.html');
	$tpl->prepare();
	$tpl->showUnAssigned(false);
	$tpl->printToScreen();
?>
