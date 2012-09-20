php-mysql-orm
-------------

code examples
=============

```php
include "db.class.php";

$user=new db('user');

/*get all data*/
$datas=$user->find();

/*conditional query*/
$condition['id']=1;
$condition['name']='小明';
$arr=$user->where($condition)->find();

/*add data*/
$user->name='小明';
$user->email='xiaoming@gmail.com';
$user->add();

/*or add data this way*/
$data['name']='hh';
$data['email']='hh@qq.com';
$user->add($data);

/*code with foreach*/
$arr=$user->select('name,email')->find();
foreach ($arr as $var) {
	echo $var['name'];
}

/******* for more : http://archphp.sinaapp.com/add.html *****/
```

wiki
====
http://archphp.sinaapp.com/%E6%95%B0%E6%8D%AE%E5%BA%93%E6%93%8D%E4%BD%9C%E7%B1%BB.html

install
=======

include it in your php script.
setting:
```php
 /************************************* settingts ******************************************/
define('DB_HOST','localhost');
define('DB_NAME','dbname');
define('DB_USER','root');
define('DB_PASSWD','******');
define('TABLE_PREFIX','t_');
define('DB_CHAR_SET','utf8');
define('QUERY_ERROR',true);//if echo query error.
/************************************************************************************/
```
