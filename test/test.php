<?php
require_once('settings.php');
require_once('../src/db.class.php');

$user=new db('user');

/*要么这两条数据全部写入,要不全不写入*/
$user->begin();//开启一个事务
$user->name='小飞';
$user->email='xiaofei@gmail.com';
$re1=$user->add();
$re2=$user->find(100);//构造一个不可能完成的事件做回滚测试
if($re1 && $re2)
{
	$user->commit();// 如果均顺利执行,commit
}else{
	$user->rollback();//否则有一个执行失败则回滚
}
