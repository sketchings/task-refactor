<?php
require_once"../global.php";

if ($_GET['log']) {
    $where=" AND Log_ID=".$_GET['log'];
}
$task=$slave->select("SELECT * FROM Tasks as T, Files as F, File_Types as Y WHERE T.Task_ID=F.Task_ID AND F.File_Type_ID=Y.File_Type_ID AND T.Task_ID=".$_GET['id'].$where);
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Images for Task ';
echo $task[0]['Task_Name'];
echo '</title>
<link href="'.CDN.'css/print.css" rel="stylesheet" type="text/css" />
</head>

<body>
';
foreach ($task as $f) {
    echo "<img src=\"$target_path".$f['File_ID'].".".$f['Extention']."\"><br />\n";
}
echo '
</body>
</html>';
