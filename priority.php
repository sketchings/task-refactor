<?php
require_once"../global.php";


//echo "test";
$text=$slave->select("SELECT * FROM Task_Priority WHERE Priority_ID=".$slave->mySQLQuote($_GET['id']));
if ($text) {
    echo $text[0]['Priority_Description'];
}
