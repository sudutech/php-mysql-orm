<?php
require_once('settings.php');
require_once('../src/db.class.php');

$user=new db('user');

/*要么这两条数据全部写入,要不全不写入*/
$user->begin();//开启一个事务
$user->name='小胖';
$user->email='xiaopang@gmail.com';
$re1=$user->add();
$user->name='小惠';
$user->id=2;//假如id=2已经有记录了,构造一个冲突
$re2=$user->add();//构造一个不可能完成的事件做回滚测试
if($re1 && $re2)
{
	$user->commit();// 如果均顺利执行,commit
}else{
	$user->rollback();//否则有一个执行失败则回滚
}
