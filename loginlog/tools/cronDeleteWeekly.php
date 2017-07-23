<?php
	if(isset($_SERVER['SERVER_PROTOCOL'])) die('invalid request');

	define('__ZBXE__', true);
	define('__XE__', true);

	$path = str_replace('/modules/loginlog/tools/cronDeleteWeekly.php','', __FILE__);

	require($path.'/config/config.inc.php');

	$oContext = Context::getInstance();
	$oContext->init();

	// loginlogController 객체 생성
	$oLoginlogController = getController('loginlog');
	$oLoginlogController->deleteLogsByCron('WEEKLY');

	printf(PHP_EOL);
	printf('Delete Complete // Date : %s', date('Y-m-d H:i:s'));
	printf(PHP_EOL);