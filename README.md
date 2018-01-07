# raspberry-Temperature
记录本次课设过程.

主要完成树莓派CPU以及DS18B20温度采集并在网页上进行显示.

详见：http://rainlua1.vicp.io.

操作步骤
## 一、本地数据采集与存储
### 1.硬件布置
#### 1.1接线：
	VCC 接  3.3V 的gpio接口

      GND 接  GND 的gpio接口
      
      DQ  接   GPIO7（#4）的gpio接口（BCM编码）
      
#### 1.2配置
先在raspi-config中配置单总线设备使能

在/boot/config.txt配置文件的最后添加如下内容： 
```dtoverlay=w1-gpio-pullup,gpiopin=4```
#### 1.3传感器操作

先进行内核升级，避免后面出现错误
```sudo apt-get update```
```sudo apt-get upgrade```

#### 1.4确认设备是否生效	
加载驱动模块，找到设备号
```
sudo modprobe w1-gpio
sudo modprobe w1-therm
cd /sys/bus/w1/devices/·
ls
```
复制设备号备用
#### 1.5.温度读取
单总线的传感器和树莓派温度读取比较简单
##### ①传感器
```
tfile = open("/sys/bus/w1/devices/28-0316a1019fff/w1_slave")
#读取文件所有内容
	text = tfile.read()
#关闭文件
	tfile.close()
```
读取的字符串经过截取，转化成对应的温度值
##### ②cpu
```
file = open("/sys/class/thermal/thermal_zone0/temp")  
	# 读取结果，并转换为浮点数  
	temp = float(file.read()) / 1000  
	# 关闭文件  
	file.close()
```
至此，对硬件的操作已经结束


### 2.python-sqlite操作
对温度采集数据之后之后可以进行数据存储
#### 2.1 模块安装
```sudo apt-get install sqlite sqlite3 -y```
新建数据库：```sqlite cpu.db```

sqlite 指令：```./database``` 可查询.db数据库文件
#### 2.2数据库操作
```
CREATE TABLE COMPANY(
   ID INTEGER  PRIMARY KEY     AUTOINCREMENT,
   datetime           DATETIME    DEFAULT (datetime('now', 'localtime')),
   temp            FlOAT     NOT NULL
);
```
说明：1.PRIMARY KEY主键，AUTOINCREMENT自动增长

     2.```datetime:datetime('now', 'localtime')```中的localtime表示本时区时间，如果没有该参数则为格林尼治时间。我们可以在raspi-config修改时区，我选的是Shanghai.
     
#### 2.3 python 操作
 因为 Python 2.5.x 以上版本默认自带了 sqlite3模块,所有这里不用安装sqlite模块
 import sqlite3即可
 ```
 conn=sqlite3.connect('home/pi/cpu.db')  
	curs=conn.cursor()     
        # 插入数据库  
	curs.execute('''CREATE TABLE IF NOT EXISTS Data  
       (ID INTEGER  PRIMARY KEY     AUTOINCREMENT,  
       datetime           DATETIME    DEFAULT (datetime('now', 'localtime')), 
       cpu_temp            FLOAT     NOT NULL,
	   sensor_temp			FLOAT		NOT NULL );''')  
	curs.execute("INSERT INTO Data(cpu_temp,sensor_temp)\
	VALUES((?),(?))",(temp1,temp2,));#插入变量方法
```
说明：1.curs.execute游标，利用该API可以执行sql语句，sqlite操作方法与sql相似，加上"."即可

2.CREATE TABLE IF NOT EXISTS Data避免重复创建Table

参考：1.[菜鸟教程]（http://www.runoob.com/sqlite/sqlite-python.html）

2.[python sqlite树莓派操作]（http://blog.csdn.net/xukai871105/article/details/38356755）
### 3.数据插入操作
3.1常量：
```
c.execute("INSERT INTO COMPANY (ID,NAME,AGE,ADDRESS,SALARY) \
      VALUES (1, 'Paul', 32, 'California', 20000.00 )");
```
 2.单个变量：
 ```curs.execute("INSERT INTO Data(cpu_temp) VALUES((?))", (temp1,))```
 
 其中：Table Name：Data	Table元素：cpu_temp	变量：temp1
 3.多个变量：
 ```
 curs.execute("INSERT INTO Data(cpu_temp,sensor_temp)\
	VALUES((?),(?))",(temp1,)(temp2,));#插入变量方法
```

### 3.脚本设置
新建脚本：Temp.sh

内容:```sudo python Temp.sh```

增加权限：```sudo chmod 777 Temp.sh```

定时执行，```sudo crontab -e```

最后一行添加：```*/30 * * * * /home/pi/Temp.sh```（注意空格）

至此就可以进行数据存储功能了；


## 二、服务器与网页显示
我这里选用是sqlite+nginx+php5,原因就不赘述了
### 1.nginx配置

首先安装nginx：
```sudo apt-get install nginx```

启动nginx服务
```sudo /etc/init.d/nginx start```

安装php支持模块
```sudo apt-get install php5-fpm```

修改nginx配置，
```sudo nano /etc/nginx/sites-enabled/default```

添加index.php 使支持PHP
```
        # pass the PHP scripts to FastCGI server listening on 127.0.0.1:9000
        #
        location ~ \.php$ {
                include snippets/fastcgi-php.conf;
        
                # With php5-cgi alone:
        #       fastcgi_pass 127.0.0.1:9000;
                # With php5-fpm:
                fastcgi_pass unix:/var/run/php5-fpm.sock;
        }
```
完成后执行
```sudo /etc/init.d/nginx reload```
可以看到debian欢迎界面即表示配置正确

### 2.安装php-sqlite3模块
```sudo apt-get install php-sqlite3```

### 3.php-sqlite3操作
```
<?php
   class MyDB extends SQLite3
   {
      function __construct()
      {
         $this->open('test.db');
      }
   }
   $db = new MyDB();
   if(!$db){
      echo $db->lastErrorMsg();
   } else {
      echo "Opened database successfully\n";
   }

   $sql =<<<EOF
      SELECT * from COMPANY;
EOF;

   $ret = $db->query($sql);
   while($row = $ret->fetchArray(SQLITE3_ASSOC) ){
      echo "ID = ". $row['ID'] . "\n";
      echo "NAME = ". $row['NAME'] ."\n";
      echo "ADDRESS = ". $row['ADDRESS'] ."\n";
      echo "SALARY =  ".$row['SALARY'] ."\n\n";
   }
   echo "Operation done successfully\n";
   $db->close();
?>
```
说明：访问该网页报500server错误一般是数据库或者table表读取不对，增加路径即可

### 3.画表格
进行画表格时，使用jQuery和chart.js这两个js函数库进行网页显示，但是因为chart.js版本迭代快，前后差别大，我就遇到了很多问题最后没有做成功
这里选用了http://www.hcharts.cn/  进行画图

Highcharts 是一个用纯JavaScript编写的一个图表库。使用 json 格式配置,表格可导出为 PDF/ PNG/ JPG / SVG 格式
如：
```
<html>
<head>
<meta charset="UTF-8" />
<title>Highcharts 教程 | 菜鸟教程(runoob.com)</title>
<script src="http://apps.bdimg.com/libs/jquery/2.1.4/jquery.min.js"></script>
<script src="http://code.highcharts.com/highcharts.js"></script>
</head>
<body>
<div id="container" style="width: 550px; height: 400px; margin: 0 auto"></div>
<script language="JavaScript">
$(document).ready(function() {
   var title = {
      text: '城市平均气温'   
   };
   var subtitle = {
      text: 'Source: runoob.com'
   };
   var xAxis = {
      categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
         'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
   };
   var yAxis = {
      title: {
         text: 'Temperature (\xB0C)'
      },
      plotLines: [{
         value: 0,
         width: 1,
         color: '#808080'
      }]
   };   

   var tooltip = {
      valueSuffix: '\xB0C'
   }

   var legend = {
      layout: 'vertical',
      align: 'right',
      verticalAlign: 'middle',
      borderWidth: 0
   };

   var series =  [
      {
         name: 'Tokyo',
         data: [7.0, 6.9, 9.5, 14.5, 18.2, 21.5, 25.2,
            26.5, 23.3, 18.3, 13.9, 9.6]
      }, 
      {
         name: 'New York',
         data: [-0.2, 0.8, 5.7, 11.3, 17.0, 22.0, 24.8,
            24.1, 20.1, 14.1, 8.6, 2.5]
      },
      {
         name: 'London',
         data: [3.9, 4.2, 5.7, 8.5, 11.9, 15.2, 17.0, 
            16.6, 14.2, 10.3, 6.6, 4.8]
      }
   ];

   var json = {};

   json.title = title;
   json.subtitle = subtitle;
   json.xAxis = xAxis;
   json.yAxis = yAxis;
   json.tooltip = tooltip;
   json.legend = legend;
   json.series = series;

   $('#container').highcharts(json);
});
</script>
</body>
</html>
```
这是chart例程，直线图.这是选取的是一个例程，
###### 说明：
1.data类型必须是数值型数据(不支持字符类型显示)，更多实例

参考1.http://www.hcharts.cn/.

2.http://www.runoob.com/highcharts/highcharts-line-basic.html

2.series可以修改数据源的数量

如单个数据源：
```
series: [{
        name: '安装，实施人员',
        data: [43934, 52503, 57177, 69658, 97031, 119931, 137133, 154175]
    }],
 ```
多个数据源如上例所示；
至此主体工作完成。

