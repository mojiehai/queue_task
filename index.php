<?php


namespace ap;
class C{
    protected $abc = "";
    private $res = '';
    private $res1 = '';
    private $res2 = '';
    private $res3 = '';
    private $res4 = '';
    private $res5 = '';

    public function __construct($abc){
        $this->abc = $abc;
    }

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

    public function getAbc(){
        return $this->abc;
    }
}

class B extends C{
    protected $abc = "";
    public function __construct($abc){
        parent::__construct($abc);
    }

}



$b = new B(123);
echo $b->getAbc();
