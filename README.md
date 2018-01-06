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
