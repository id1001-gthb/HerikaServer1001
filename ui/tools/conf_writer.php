<?php

session_start();

$enginePath=__DIR__.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR;;
require($enginePath.DIRECTORY_SEPARATOR."conf".DIRECTORY_SEPARATOR.'conf_loader.php');

$confSchema=conf_loader_load_schema();

$buffer="<?php".PHP_EOL;

$oldGroup="";
$oldSubGroup="";

if (isset($_POST["profile"])) {
    $_SESSION["PROFILE"]=$_POST["profile"];
    unset($_POST["profile"]);
    unset($_POST["profileSelector"]);
}

if (isset($_GET["incomplete"])) {
    $defaultConf=file_get_contents($enginePath.DIRECTORY_SEPARATOR."conf".DIRECTORY_SEPARATOR.'conf.sample.php');
    $pattern = '/<\?php(.*?)\?>/s';

    // Use preg_match to find the content between the PHP tags
    if (preg_match($pattern, $defaultConf, $matches)) {
        // $matches[1] contains the content between the tags
        $php_code = trim($matches[1]);
        $buffer="<?php".PHP_EOL.$php_code;

    } else {
        error_log("No PHP code found in the file.");
    }


}

foreach ($_POST as $k=>$v) {
    
    $fullNameHierch=explode("@",$k);
    $plainNameHierch=strtr($k,array("@"=>" "));
    
    if (is_array($v))
        $value=json_encode($v,true);
    else if ($confSchema[$plainNameHierch]["type"]=="number") {
        if ($v==="")
            continue;
        else
            $value="".addcslashes($v,"'")."";
    }
    else if ($confSchema[$plainNameHierch]["type"]=="boolean")
        $value=($v=="true")?"true":"false";
    else
        $value="'".addcslashes($v,"'")."'";
    
    
    
    if ($oldGroup!=$fullNameHierch[0]) {
           $buffer.=PHP_EOL.PHP_EOL;
           $oldGroup=$fullNameHierch[0];
    }
    
    if (isset($fullNameHierch[1]))
        if ($oldSubGroup!=$fullNameHierch[1]) {
            $buffer.=PHP_EOL;
            $oldSubGroup=$fullNameHierch[1];
        }
    
    if (sizeof($fullNameHierch)==1) {
        if (isset($confSchema[$plainNameHierch]["description"])) {
            $buffer.="//".$confSchema[$plainNameHierch]["description"].PHP_EOL;
        }
        $buffer.="\${$fullNameHierch[0]}=$value;".PHP_EOL;
    }
    
    if (sizeof($fullNameHierch)==2) {
        $inlineComment="";
        if (isset($confSchema[$plainNameHierch]["description"])) {
            $inlineComment="//".$confSchema[$plainNameHierch]["description"];
        }
        $buffer.="\${$fullNameHierch[0]}[\"$fullNameHierch[1]\"]=$value;\t$inlineComment".PHP_EOL;
    }
    
    
    if (sizeof($fullNameHierch)==3) {
        $inlineComment="";

        if (isset($confSchema[$plainNameHierch]["description"])) {
            $inlineComment.="//".$confSchema[$plainNameHierch]["description"];
        }
        $buffer.="\${$fullNameHierch[0]}[\"$fullNameHierch[1]\"][\"$fullNameHierch[2]\"]=$value;\t$inlineComment".PHP_EOL;
    }
    
    
}
$buffer.="?>".PHP_EOL;

if (isset($_GET["save"])) {
    if (isset($_SESSION["PROFILE"]))
        $result=file_put_contents($_SESSION["PROFILE"],$buffer);
    else
        $result=file_put_contents($enginePath."conf".DIRECTORY_SEPARATOR."conf.php",$buffer);
    echo '<!DOCTYPE html>
        <html lang="en" >
        <head>
        <style>
        body {
        background-color: black;
        color: white;
        font-size: small ; 
        font-family: Consolas,Monaco,Lucida Console,Liberation Mono,DejaVu Sans Mono,Bitstream Vera Sans Mono,Courier New, monospace;
        width: 100%;
        display: inline-block;
        }
        </style>
        </head>
        <body>
    ';
    
    
    if ($result!==false) {
        echo "Writing config file.....";
        echo '<script>alert("Config file '.basename($_SESSION["PROFILE"]).' has been written");parent.location.href="../conf_wizard.php?ts='.(time()."#".$_GET["sc"]).'"</script>';
    } else {
        echo "Writing config file.....";
        echo "Some error ocurred.".PHP_EOL;
        
    }
    
    
    
} else {
    $_POST["text"]=$buffer;
    require(__DIR__.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."conf_checker.php");
}

?>
