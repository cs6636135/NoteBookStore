<?php
try {
$pdo = new PDO("mysql:host=sql103.infinityfree.com;dbname=if0_41046079_notebook;charset=utf8","if0_41046079","Apple2003ss");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    //echo "connected";

} catch (PDOException$e){
    echo "error".$e->getMessage();
}

?>