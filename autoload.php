<?php
    spl_autoload_register("autoload");
    
    function autoload($className){
        include(__DIR__ . "/src/" . str_replace("\\", "/", $className) . ".php");
    }
?>