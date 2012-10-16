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

中文wiki:[wiki-zh](http://hit9.github.com/wiki/php-mysql-orm/index.html)
