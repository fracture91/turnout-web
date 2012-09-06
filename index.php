<?php
	require_once 'config.inc.php';
	
	$tpl_main = new TemplatePower('template/master.html');
	$tpl_main->prepare();
	
	$tpl = new TemplatePower('template/home.html');
	$tpl->prepare();
	$tpl->showUnAssigned(false);
	$tpl_main->assign('content', $tpl->getOutputContent());
	
	$tpl_main->showUnAssigned(false);
	$tpl_main->printToScreen();
?>
