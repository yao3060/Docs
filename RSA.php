<?php

/**
 * 1. 从 1-10 中间任意取两个 质素 p 和 q
 * 2. 让 p*q = n
 * 3. funN = (p-1) * (q-1)
 * 4. 获取 公钥 e：
 * – 1 < e < funN
 * – e 和 funN 互质
 * 5. 获取 私钥 d：使得 (e * d) % funN = 1
 * 6. 任意拿一个数字 m 用公钥匙 e 对其加密：pow(m, e) % n = c，c 即加密后的信息
 * 7. 用私钥 d 对c 解密：pow(c, d) % n = m， m 得到还原
 */
class RSA
{
    protected $primes = [];
    protected $min;
    protected $max;
    protected $p;
    protected $q;
    protected $n;
    protected $funN;
    public $e; // public key
    public $d; // private key

    public function __construct($min, $max)
    {
        $this->min = $min;
        $this->max = $max;
    }

    public function isPrime($number)
    {
        if ($number < 2) return false;

        if ($number == 2 || $number == 3) return true;

        for ($i = 2; $i <= sqrt($number); $i++) {
            if ($number % $i == 0) return false;
        }

        return true;
    }

    public function getPrimes()
    {
        for ($i = $this->min; $i <= $this->max; $i++) {

            if ($this->isPrime($i)) {
                $this->primes[] = $i;
            }
        }

        return $this->primes;
    }

    public function setData()
    {

        if (count($this->primes) < 1) {
            $this->getPrimes();
        }

        $randomKeys = array_rand($this->primes, 2);
        $this->p = $this->primes[$randomKeys[0]];
        $this->q = $this->primes[$randomKeys[1]];
        $this->n = $this->p * $this->q;
        $this->funN = ($this->p - 1) * ($this->q - 1);
        $this->setPublicKey();
        if (!$this->e) {
            return (new self($this->min, $this->max))->setData();
        }
        $this->setPrivateKey();

        return $this;
    }

    public function getData()
    {
        return $this;
    }

    /**
     * get public key: 1 < $publicKey < $this->funN
     */
    public function setPublicKey()
    {
        for ($i = 1; $i < $this->funN; $i++) {
            if ($this->isPrime($i) && ($this->funN % $i  !== 0)) {
                $this->e = $i;
            }
        }
    }

    /**
     * ($publicKey * $privateKey) % $this->funN = 1
     * 
     * $publicKey * $privateKey > $this->funN
     * 
     * $privateKey > $this->funN / $publicKey
     */
    public function setPrivateKey()
    {
        $start = floor($this->funN / $this->e);
        $privateKey = 0;
        while ($privateKey == 0) {
            if (($this->e * $start) % $this->funN === 1) {
                $privateKey = $start;
                $this->d = $privateKey;
            }
            $start++;
        }
    }

    public function encrypt($number)
    {
        # code...
        return pow($number, $this->e) % $this->n;
    }

    public function decrypt($number)
    {
        return pow($number, $this->d) % $this->n;
    }
}


$rsa = (new RSA(1, 10))->setData();
print_r($rsa->getData());

$original = 3;
echo "original: " . $original . "\n";
$encrypt = $rsa->encrypt($original);
echo "encrypt message:" . $encrypt . "\n";

$decrypt = $rsa->decrypt($encrypt);
echo "decrypt message:" . $decrypt . "\n";
