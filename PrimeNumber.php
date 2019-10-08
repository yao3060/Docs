<?php
$stime = microtime(true); #获取程序开始执行的时间

class PrimeNumber
{

    /**
     * 判断 $number 是否为质素
     *
     * @param [type] $number
     * @return boolean
     */
    static function isPrime($number)
    {
        if ($number == 1) return false;

        if ($number == 2 || $number == 3) return true;

        for ($i = 2; $i <= sqrt($number); $i++) {
            if ($number % $i == 0)
                return false;
        }
        return true;
    }

    /**
     * 找出比 $number 小的质素
     *
     * @param [type] $number
     * @return void
     */
    function getPrimes($number)
    {
        $result = [];
        for ($i = 2; $i <= $number; $i++) {
            if (self::isPrime($i)) {
                $result[] = $i;
            }
        }

        return $result;
    }

    /**
     * 分别除以比 $number 小的质素
     *
     * @param integer $number
     * @return void
     */
    function Factorization(int $number)
    {
        if (self::isPrime($number)) {
            return $number . " is Prime Number";
        }
        $result = [];
        if ($number % 2 == 0) $result[] = 2;

        $primes = $this->getPrimes(ceil($number / 2));

        for ($i = 0; $i < count($primes); $i++) {
            if ($number % $primes[$i] == 0) {
                $result[] = $primes[$i];
            }
        }

        return [
            'number' => $number,
            'factors' => $result,
            //'primes' => $primes
        ];
    }
}

$factors = (new PrimeNumber())->Factorization(65535);
print_r($factors);

$etime = microtime(true); #获取程序执行结束的时间
$total = $etime - $stime;   #计算差值

echo "\n{$total} times \n";
