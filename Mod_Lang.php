<?php

class langAssets{
    private $assets = array(
        "EN"=>array(
            "pay"       =>  "pay",
            "money"     =>  "money",
            "dollars"   =>  "dollars"
        ),
        "CN"=>array(
            "pay"       =>  "付给",
            "money"     =>  "钱",
            "dollars"   =>  "元"
        )
    );
    var $crtLang;
    function __construct(string $lang_code){
        $this->crtLang = $assets[$lang_code];
    }
}

?>