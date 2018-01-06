<!DOCTYPE HTML>
<html>
    <head>
        <meta charset="utf-8"><link rel="icon" href="https://static.jianshukeji.com/highcharts/images/favicon.ico">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
           /* css 代码  */
        </style>
        <script src="https://img.hcharts.cn/highcharts/highcharts.js"></script>
        <script src="https://img.hcharts.cn/highcharts/modules/exporting.js"></script>
        <script src="https://img.hcharts.cn/highcharts/modules/series-label.js"></script>
        <script src="https://img.hcharts.cn/highcharts/modules/oldie.js"></script>
        <script src="https://img.hcharts.cn/highcharts-plugins/highcharts-zh_CN.js"></script>
    </head>
    <body>
        <div id="container" style="max-width:800px;height:400px"></div>
<?php
 class MyDB extends SQLite3
   {
      function __construct()
      {
         $this->open('/home/pi/cpu.db');
      }
   }
   $db = new MyDB();
   if(!$db){
      echo $db->lastErrorMsg();
   }
   $sql =<<<EOF
      SELECT * from Data;
EOF;
 $ret = $db->query($sql);
echo "<table border='1'>
<tr>
<th>ID</th>
<th>datetime</th>
<th>cpu_temp</th>
<th>sensor_temp </>
</tr>";
while($row = $ret->fetchArray(SQLITE3_ASSOC))
{
  echo "<tr>";
  echo "<td>" . $row['ID'] . "</td>";
  echo "<td>" . $row['datetime'] . "</td>";
  echo "<td>" . $row['cpu_temp'] . "</td>";
  echo "<td>" . $row['sensor_temp'] . "</td>";
  echo "</tr>";
 $Lables=$Lables.'"'. $row['datetime'].'",';
  $Temp_cpu=$Temp_cpu.''. $row['cpu_temp'].',';
  $Temp_sensor=$Temp_sensor.''. $row['sensor_temp'].',';
}
echo "/home/pi/cpu.db FROM TABLE Data";
echo "</table>";
$db->close();
?>
        <script>
            // JS 代码
var chart = Highcharts.chart('container', {
    title: {
        text: '树莓派cpu温度显示'
    },
    subtitle: {
        text: '数据来源：/home/pi/cpu.db'
    },
        xAxis:{
                categories:[<?php echo $Lables; ?>]
        },
    yAxis: {
        title: {
            text: '温度(摄氏度(℃))'
        }
 },
    series: [{
        name: 'cpu温度',
       data: [<?php echo $Temp_cpu; ?>]
    }],
	series: [{
        name: 'DS18B20温度',
       data: [<?php echo $Temp_sensor; ?>]
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
        </script>
    </body>
</html>

