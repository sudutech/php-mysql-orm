<?php
/**********************************************************************************
 * Author:hit9
 * github:@hit9
 * Permission to use it for any purpose
 * Db类,负责数据库连接和基本的CURD,实现了连贯操作和事务处理
 * 配置项:DB_HOST,DB_CHAR_SET,DB_NAME,DB_USER,DB_PASSWD,TABLE_PREFIX,QUERY_ERROR
 * 每次实例化单利连接数据库
 * 实例化的参数:$classname(数据库表的名字(不带前缀)),$primary_key(表主键)
 * 用法详解:http://archphp.sinaapp.com/数据库操作类.html
 *
 ************************************************************************************/

/************************************* settingts ******************************************
define('DB_HOST','localhost');//数据库服务器地址,一般为localhost
define('DB_NAME','dbname');//数据库名字
define('DB_USER','root');//数据库管理用户名
define('DB_PASSWD','******');//数据库管理密码
define('TABLE_PREFIX','t_');//数据库表名前缀
define('DB_CHAR_SET','utf8');//数据库连接编码 
define('QUERY_ERROR',true);//是否调试模式,调试模式下会显示查询报错
/************************************************************************************/

class db
{
	private static $conn;//数据库连接
	private $table;//数据库表名字
	private $tablepre;//表前缀
	private $data;//暂时存放写入的数据的数组
	private $options;//暂时存放查询条件的数组
	private $query_error=QUERY_ERROR;//是否开启数据库查询错误显示
	//默认的主键
	public $primary_key='id';
	//构造表名,初始化数组,连接数据库
	// 参数$classname 是一个Mod模型的类名字,比如 'UserMod'
	function __construct($tablename,$primary_key='id')
	{
		$this->tablepre=TABLE_PREFIX;
		$this->table=$this->tablepre.$tablename;
		$this->primary_key=$primary_key;
		$this->data=array();
		$this->options=array();
		$this->connect();//连接数据库
	}
	//设置类的私有属性
	function __set($name,$value)
	{
		$this->data[$name]=$value;
	}
	//重载方法
	function __call($method,$args)
	{
		//如果是limit or orderBy
		if($method==='limit' or $method==='orderBy')
		{
			$this->options[$method]=$args[0];
			return $this;// 返回此对象
		}elseif(preg_match("/like$/",$method))//like
		{
			$char=substr($method,0,-4);
			$this->options['like']="`".$char."` LIKE '$args[0]'";
			return $this;
		}
		elseif(preg_match("/^getBy/",$method))//如果是getBy函数,就根据字段取出一个结果来
		{
			$char=lcfirst(substr($method,5));//获取字段名,getByChar 字段名在函数名中必须首字母大写
			$sql="SELECT * FROM ".$this->table." WHERE ".$char."='$args[0]' LIMIT 1";
			return mysql_fetch_object($this->query($sql));
		}else{
			if($this->query_error) exit('Unknown method: '.$method);
			else exit('Something Wrong');
		}
	}
	//where()
	public function where($arg='')
	{
		if(is_array($arg))//把数组形式的参数解析成字符串形式
		{
			$arr_str=array();
			foreach ($arg as $key=>$var) {
				$var=$this->check_input($var);
				$arr_str[]="`".$key."`='".$var."'";
			}
			$str_temp=implode(' AND ',$arr_str);
		}elseif(is_string($arg))
		{
			$str_temp=$arg;
		}
		if(isset($this->options['where']))//如果已经有where
		{
			$this->options['where'].=" AND ".$str_temp;
		}else{
			$this->options['where']=$str_temp;
		}
		return $this;
	}

	public function select($arg='')
	{
		if(is_string($arg)) $this->options['select']=$arg;
		else if(is_array($arg)){
			$this->options['select']=implode(',',$arg);
		}
		return $this;
	}

	//清空options数组,所有连贯操作的最后一个操作都要清空options数组
	private function empty_options()
	{
		$this->options=array();
	}
	//单例连接数据库,返回连接符号
	private function connect()
	{
		if(!self::$conn)
		{
			self::$conn=mysql_connect(DB_HOST,DB_USER,DB_PASSWD);
			mysql_query('set names '.DB_CHAR_SET);
			mysql_select_db(DB_NAME);
		}
		return self::$conn;
	}
	//把数据变成字符串
	private function implodefields($cond)
	{
		$fields = array();
		foreach($cond as $key => $value) {
			//安全,转义字符串,防注入
			$value = $this->check_input($value);
			$fields[] = "`$key`='$value'";
		}
		return implode(', ', $fields);
	}
	//检查输入
	private	function check_input($value)
	{
		$value = str_replace("_","\_",$value);
		$value = str_replace("%","\%",$value);
		// 去除斜杠
		if (get_magic_quotes_gpc())
		{
			$value = stripslashes($value);
		}
		// 如果不是数字则加引号
		if (!is_numeric($value))
		{
			$value = mysql_real_escape_string($value);
		}
		return $value;
	}
	//清洗data数组.一个acion流程中需要多次写入,每写完一次数据都会使用此方法清洗data
	public function data_clean()
	{
		$this->data=array();
	}
	//查询数据库,返回查询句柄
	private function query($sql='')	
	{
		if($sql=='')
		{
			$this->debug_error('SQL is empty');
		}else{
			$re=mysql_query($sql);
			if(!$re) $this->debug_error('Query Error :'.mysql_error().'<br>Your SQL is : '.$sql);
		}
		return $re;
	}
	//获取一个结果集的数目
	// 参数:sql语句,如果语句为空,取查询条件where的查询条件构造sql语句,如果无查询条件,取出该表的记录个数
	public function getNum($sql='')
	{
		//无sql语句传入,查看有无查询条件,否则当作查询表的记录数处理
		if($sql=='')
		{
			if(!empty($this->options))//如果前面有连贯操作
			{
				$sql="SELECT {$this->primary_key} FROM `{$this->table}`";
				$sql=$this->parseoptions($sql);// 解析查询条件构造sql
				$this->empty_options();//连贯操作的终点需要清空连贯操作的数组options
			}else{
				$sql="SELECT {$this->primary_key} FROM `{$this->table}`";
			}	
		}
		return mysql_num_rows($this->query($sql));
	}
	//插入操作,在表上增加一个记录,返回执行结果true or false
	public function add($arr=array())
	{
		$this->data=array_merge($this->data,$arr);
		$fields = $this->implodefields($this->data);
		$sql = "INSERT INTO `{$this->table}` SET $fields";
		$this->data_clean();//data数组清空
		return $this->query($sql);
	}
	//查询
	//如果按照主键查询,则返回一个对象便于快速获取字段值
	//如果做为一个查询的结果集(前边有连贯条件限制),则返回一个二维数组便于遍历
	public function find($id='',$fields='*')
	{
		if(isset($this->options['select'])) $fields=$this->options['select'];
		//如果有参数传入,视作查询主键处理
		if(!empty($id))
		{
			$id=$this->check_input($id);
			$sql="SELECT ".$fields." FROM `{$this->table}` WHERE `{$this->primary_key}`='$id' LIMIT 1";
			$obj=mysql_fetch_object($this->query($sql));
			return $obj;
		}else{
			if(!empty($this->options))//如果前面有连贯操作
			{
				$sql="SELECT ".$fields." FROM `{$this->table}`";
				$sql=$this->parseoptions($sql);// 解析查询条件构造sql
				$this->empty_options();//连贯操作的终点需要清空连贯操作的数组options
			}else{
				//否则视作查询全部记录
				$sql="SELECT ".$fields." FROM `{$this->table}`";
			}
			$return_arr=array();
			$re=$this->query($sql);
			while($re_arr=mysql_fetch_array($re))
			{
				$return_arr[]=$re_arr;
			}
			return $return_arr;
		}
	}
	//delete函数
	public function delete($id='')
	{
		//如果传入了参数,按照主键删除
		if(!empty($id))
		{
			$id=$this->check_input($id);
			$sql="DELETE FROM `{$this->table}` WHERE `{$this->primary_key}`='$id'";
			return $this->query($sql);
		}else{
			$sql="DELETE FROM `{$this->table}`";
			if(empty($this->options)) $this->debug_error('No Query Conditions for delete()');
			$sql=$this->parseoptions($sql);// 解析查询条件构造sql
			$this->empty_options();
			return	$this->query($sql);
		}
	}
	//清空表
	public function empty_table()
	{
		$sql="TRUNCATE TABLE `{$this->table}`";
		return $this->query($sql);
	}
	//update 部分
	public function update($arg1=NULL,$arg2=NULL)
	{
		if(isset($arg1))//如果有参数传入
		{
			if(isset($arg2))//如果有两个参数
			{
				if(!is_array($arg1) and is_array($arg2)) 
				{
					$this->data=$arg2; 
					$id=$arg1;
				}else{
					$this->debug_error('Wrong Use of function update($arg1,$arg2):If 2 arguments used,$arg1 must be primary_key and $arg2 must be data array');
				}
			}else{
				if(is_array($arg1))//如果使按照data数组更新 
				{
					$this->data=$arg1;
				}else{
					$id=$arg1;//否则视作按照主键更新
				}
			}
		}
		if(empty($this->data)) $this->debug_error('update() function can not found data to use');
		$fields = $this->implodefields($this->data);
		if(isset($id))//如果使用主键更新方式
		{
			$id=$this->check_input($id);
			$sql="UPDATE `{$this->table}` SET ".$fields." WHERE `{$this->primary_key}`='$id'";
		}else{//使用条件更新
			$sql="UPDATE `{$this->table}` SET ".$fields;
			if(empty($this->options)) $this->debug_error('No Query Conditions for update()');
			$sql=$this->parseoptions($sql);// 解析查询条件构造sql
			$this->empty_options();
		}
		$this->data_clean();//data数组清空
		return $this->query($sql);
	}
	//直接执行sql语句
	public function exec_sql($sql)
	{
		$sqltype = strtolower(substr(trim($sql),0,6));// 截取sql语句中的前6个字符串,并转换成小写
		$result = $this->query($sql);
		$calback_arrary = array();// 定义二维数组
		if ("select" == $sqltype)// 判断执行的是select语句
		{
			if (false == $result)
			{
				return false;
			}
			else if (0 == mysql_num_rows($result))
			{
				return false;
			}
			else
			{
				while($result_array = mysql_fetch_array($result))
				{
					array_push($calback_arrary, $result_array);
				}
				return $calback_arrary;// 成功返回查询结果的二维数组,既然查询到的结果是多个,输出数组便于foreach
			}
		}
		else if ("update" == $sqltype || "insert" == $sqltype || "delete" == $sqltype)
		{
			if ($result)
			{
				return true;
			}
			return false;
		}
	}

	//解析查询条件
	private function parseoptions($sql='')
	{
		$arr=$this->options;
		//首先检查where
		if(isset($arr['where']))
		{
			$sql.=" WHERE ".$arr['where'];
			if(isset($arr['like']))//在检查where的同时检查是否有like子句
			{
				$sql.=" AND ".$arr['like'];
			}
		}else{
			if(isset($arr['like']))
			{
				$sql.=" WHERE ".$arr['like'];//只有like无其它where的情形
			}
		}
		//检查orderBy
		if(isset($arr['orderBy']))
		{
			$sql.=" ORDER BY ".$arr['orderBy'];
		}
		//检查limit
		if(isset($arr['limit']))
		{
			$sql.=" LIMIT ".$arr['limit'];
		}
		return $sql;
	}

	//事务处理部分,commit & rollback
	// 开始一个事务
	public function begin()
	{
		$this->query('BEGIN');
	}
	//事务完成
	public function commit()
	{
		$this->query('COMMIT');
	}
	//事务回滚
	public function rollback()
	{
		$this->query('ROLLBACK');
	}

	private function debug_error($msg)
	{
		if($this->query_error) echo $msg;//debug下,显示错误
	}
}
