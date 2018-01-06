#!/usr/bin/python  
# -*- coding: utf-8 -*-  
import time  
import sqlite3  
def get_sensor_temp():
	tfile = open("/sys/bus/w1/devices/28-0316a1019fff/w1_slave")
#读取文件所有内容
	text = tfile.read()
#关闭文件
	tfile.close()
#用换行符分割字符串成数组，并取第二行
	secondline = text.split("\n")[1]
#用空格分割字符串成数组，并取最后一个，即t=23000
	temperaturedata = secondline.split(" ")[9]
#取t=后面的数值，并转换为浮点型
	temperature = float(temperaturedata[2:])
#转换单位为摄氏度
	temperature = temperature / 1000
#打印值
	print temperature
	return temperature
def get_cpu_temp():  
# 打开文件  
	file = open("/sys/class/thermal/thermal_zone0/temp")  
	# 读取结果，并转换为浮点数  
	temp = float(file.read()) / 1000  
	# 关闭文件  
	file.close()  
	return temp  
      
def insert_cpu_temp(temp1,temp2):  
        # 连接数据库  
	conn=sqlite3.connect('cpu.db')  
	curs=conn.cursor()     
        # 插入数据库  
	curs.execute('''CREATE TABLE IF NOT EXISTS Data  
       (ID INTEGER  PRIMARY KEY     AUTOINCREMENT,  
       datetime           DATETIME    DEFAULT (datetime('now', 'localtime')), 
       cpu_temp            FLOAT     NOT NULL,
	   sensor_temp			FLOAT		NOT NULL );''')  
	curs.execute("INSERT INTO Data(cpu_temp,sensor_temp)\
	VALUES((?),(?))",(temp1,temp2));#插入变量方法
	conn.commit()            
	# 关闭数据库  
	conn.close()  
      
def main():   
	Cpu_Temp = get_cpu_temp()  
	Sensor_Temp=get_sensor_temp()
	insert_cpu_temp(Cpu_Temp,Sensor_Temp)  
      
if __name__ == '__main__':  
	main()  
