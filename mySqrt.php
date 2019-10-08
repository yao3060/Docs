<?php
$stime = microtime(true); #获取程序开始执行的时间

function mySqrt(int $number)
{
    $temp = $number;
    $temps = [];

    $i = 0;
    do {
        $temps[] = $temp = ($temp + $number / $temp) / 2;
        if ($i > 1 && $temps[$i] == $temps[$i - 1]) {
            break;
        }
    } while (++$i);

    return [
        $temps,
        $temp,
    ];
}

$number = 16;
var_dump(sqrt($number) == mySqrt($number)[1]);

$etime = microtime(true); #获取程序执行结束的时间
$total = $etime - $stime;   #计算差值

echo "\n{$total} times \n";
