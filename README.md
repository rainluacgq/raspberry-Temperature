# raspberry-Temperature
记录sqlite学习历程
主要完成树莓派CPU以及DS18B20温度显示并在网页上进行显示
详见：http://rainlua1.vicp.io
操作步骤
## 一、本地数据采集与存储
### 1.硬件布置
1.1接线： VCC 接  3.3V 的gpio接口
      GND 接  GND 的gpio接口
      DQ  接   GPIO7（#4）的gpio接口（BCM编码）
1.2配置
先配置单总线设备使能
在/boot/config.txt配置文件的最后添加如下内容： 
```dtoverlay=w1-gpio-pullup,gpiopin=4```
1.3传感器操作
先进行内核升级，避免后面出现错误
```sudo apt-get update```
```sudo apt-get upgrade```

### 2、确认设备是否生效	
```
sudo modprobe w1-gpio
sudo modprobe w1-therm
cd /sys/bus/w1/devices/·
ls
```
复制设备号备用
#### 2.温度读取
单总线的传感器和树莓派温度读取比较简单
##### ①传感器
```tfile = open("/sys/bus/w1/devices/28-0316a1019fff/w1_slave")```
#读取文件所有内容
	```text = tfile.read()```
#关闭文件
	tfile.close()
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
新建数据库：sqlite cpu.db
sqlite 指令：./database 可查询.db数据库文件
#### 2.2数据库操作
```
CREATE TABLE COMPANY(
   ID INTEGER  PRIMARY KEY     AUTOINCREMENT,
   datetime           DATETIME    DEFAULT (datetime('now', 'localtime')),
   temp            FlOAT     NOT NULL
);
```
说明：PRIMARY KEY主键，AUTOINCREMENT自动增长
     ``` datetime:datetime('now', 'localtime')```中的localtime表示本时区时间，如果没有该参数则为格林尼治时间。
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
	VALUES((?),(?))",(temp1,)(temp2,));#插入变量方法
```
说明：1.curs.execute游标，利用该API可以执行sql语句，sqlite操作方法与sql相似，加上"."即可
2.CREATE TABLE IF NOT EXISTS Data避免重复创建Table
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
最后一行添加：```*/30 * * * * /home/pi/Temp.sh```
（注意空格）
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
添加index.php 
使支持PHP
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
如：
```
var chart = Highcharts.chart('container', {
    title: {
        text: '2010 ~ 2016 年太阳能行业就业人员发展情况'
    },
    subtitle: {
        text: '数据来源：thesolarfoundation.com'
    },
   
    legend: {
        layout: 'vertical',
        align: 'right',
        verticalAlign: 'middle'
    },
    series: [{
        name: '安装，实施人员',
        data: [43934, 52503, 57177, 69658, 97031, 119931, 137133, 154175]
    }, {
        name: '工人',
        data: [24916, 24064, 29742, 29851, 32490, 30282, 38121, 40434]
    }, {
        name: '销售',
        data: [11744, 17722, 16005, 19771, 20185, 24377, 32147, 39387]
    }, {
        name: '项目开发',
        data: [null, null, 7988, 12169, 15112, 22452, 34400, 34227]
    }, {
        name: '其他',
        data: [12908, 5948, 8105, 11248, 8989, 11816, 18274, 18111]
    }],
    responsive: {
        rules: [{
            condition: {
                maxWidth: 500
            },
            chartOptions: {
                legend: {
                    layout: 'horizontal',
                    align: 'center',
                    verticalAlign: 'bottom'
                }
            }
        }]
    }
});
```
这是选取的是一个例程，注意data类型必须是数值型数据，更多实例参考http://www.hcharts.cn/
至此主体工作完成。

