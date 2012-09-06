<?php
	require_once 'config.inc.php';

	$tpl_main = new TemplatePower('template/master.html');
	$tpl_main->prepare();
	
	// see if the login form was submitted
	if( isset($_POST['login'])) {
		$user = stripslashes($_POST['username']);
		$pass = stripslashes($_POST['password']);
		$goto = $_POST['redirect'];
		
		if($_AUTH->login($user, $pass)) {
			include_once($goto);
			die();
		} else {
			$tpl = new TemplatePower('template/error.html');
			$tpl->assignGlobal('error_message', 'Invalid username / password.' . $user);
		}

	// display the default login form
	} else {		
		$tpl = new TemplatePower('template/login.html');
		$tpl->assignGlobal('redirect', $_GET['redirect']);
	}
	
	$tpl->prepare();
	$tpl->showUnAssigned(false);
	$tpl_main->assign('content', $tpl->getOutputContent());
	
	$tpl_main->showUnAssigned(false);
	$tpl_main->printToScreen();
?>
