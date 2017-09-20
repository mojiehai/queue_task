<?php


namespace ap;
class C{
    private $res = '';
    private $res1 = '';
    private $res2 = '';
    private $res3 = '';
    private $res4 = '';
    private $res5 = '';


    public function __sleep(){
        $this->res = "1";
        return ['res','res1','res2','res3','res4','res5'];
    }

    public function getVars(){
        return get_class_vars(get_called_class());
    }

    public function getK(){
        echo get_called_class();echo "<br />";
        var_dump(get_class_vars("ap\B"));
        var_dump(get_class_vars(get_called_class()));
        var_dump($this->res);
        var_dump($this->res1);
        var_dump($this->res2);
        var_dump($this->res3);
        var_dump($this->res4);
        var_dump($this->res5);
    }

    public function __wakeup(){
        $this->res2 = "2";
    }
}

class B extends C{
    private $abc = "";
}


$res = new C();

//$ress = serialize($res);

//$r = unserialize($ress);
//$r->getK();
//$res -> getK();

echo "<br />";
$b = new B();
//$b -> getK();
var_dump($b->getVars());
