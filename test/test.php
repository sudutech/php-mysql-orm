<?php
require_once('settings.php');
require_once('../src/db.class.php');

$user=new db('user');

$re_arr=$user->exec_sql('SELECT name FROM t_user WHERE 1');
foreach ($re_arr as $var) {
	echo $var['name'];
}
