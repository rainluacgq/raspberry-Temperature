# raspberry-Temperature
记录sqlite学习历程
主要完成树莓派CPU以及DS18B20温度显示并在网页上进行显示
详见：http://rainlua1.vicp.io
操作步骤
1.硬件布置
1.1接线： VCC 接  3.3V 的gpio接口
      GND 接  GND 的gpio接口
      DQ  接   GPIO7（#4）的gpio接口（BCM编码）
1.2配置
先配置单总线设备使能
在/boot/config.txt配置文件的最后添加如下内容： dtoverlay=w1-gpio-pullup,gpiopin=4
1.3传感器操作
先进行内核升级，避免后面出现错误
sudo apt-get update
sudo apt-get upgrade
2、确认设备是否生效	
sudo modprobe w1-gpio
sudo modprobe w1-therm
cd /sys/bus/w1/devices/
ls
复制设备号备用
2.温度读取
单总线的传感器和树莓派温度读取比较简单
①传感器
tfile = open("/sys/bus/w1/devices/28-0316a1019fff/w1_slave")
#读取文件所有内容
	text = tfile.read()
#关闭文件
	tfile.close()
②cpu
file = open("/sys/class/thermal/thermal_zone0/temp")  
	# 读取结果，并转换为浮点数  
	temp = float(file.read()) / 1000  
	# 关闭文件  
	file.close()  
至此，对硬件的操作已经结束


2.python-sqlite操作
对温度采集数据之后之后可以进行数据存储
2.1 模块安装
sudo apt-get install sqlite sqlite3 -y
新建数据库：sqlite cpu.db
sqlite 指令：./database 可查询.db数据库文件
2.2数据库操作
CREATE TABLE COMPANY(
   ID INTEGER  PRIMARY KEY     AUTOINCREMENT,
   datetime           DATETIME    DEFAULT (datetime('now', 'localtime')),
   temp            FlOAT     NOT NULL
);
说明：PRIMARY KEY主键，AUTOINCREMENT自动增长
      datetime:datetime('now', 'localtime')中的localtime表示本时区时间，如果没有该参数则为格林尼治时间。
 2.3 python 操作
 因为 Python 2.5.x 以上版本默认自带了 sqlite3模块。
 
