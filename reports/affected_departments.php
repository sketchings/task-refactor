<?php
require_once"../../global.php";


$access=$_SESSION['access'];
$total_tasks=array();
$title="Affected Departments";
if ($_GET) {
    $where="AND (";
    if ($_GET['user_id'] and $_GET['user_id']!="all") {
        $user=$slave->select("SELECT First_Name, Last_Name, Department_ID FROM Users as U, User_Departments as D WHERE U.User_ID=D.User_ID AND U.User_ID=".$_GET['user_id']);
        if ($user) {
            $where.="(User_ID=".$_GET['user_id'];
            if ($_GET['department_id']=="all") {
                foreach ($user as $d) {
                    $where.=" OR T.Department_ID=".$d['Department_ID'];
                    $forud=" AND their department(s)";
                }
            }
            $where.=")";
            $forud=" for ".display_name($user[0]['First_Name'], $user[0]['Last_Name']).$forud;
        }
    }
    if ($_GET['department_id'] and $_GET['department_id']!="all") {
        $department=$slave->select("SELECT Department_Name,User_ID FROM Departments as D, User_Departments as U WHERE D.Department_ID=U.Department_ID AND D.Department_ID=".$_GET['department_id']);
        if ($department) {
            if ($_GET['user_id']) {
                $where.=" OR ";
            }
            $where.="(T.Department_ID=".$_GET['department_id'];
            if ($_GET['user_id']=="all") {
                foreach ($department as $d) {
                    $where.=" OR User_ID=".$d['User_ID'];
                    $forud=" AND IT Users";
                }
            }
            $where.=")";
            $forud=" for ".$department[0]['Department_Name'].$forud;
        }
    }
    $where.=")";
    if ($where=="AND ()") {
        $where="";
    }
}

show_head($title);
echo "<h1>$title</h1>\n";
echo "<table><tr>";
echo "<td>User <select name=\"user_id\" onChange=\"MM_jumpMenu('parent',this,0)\">\n";
echo "<option value=\"".$_SERVER['PHP_SELF'].(($_GET['department_id'])?"?department_id=".$_GET['department_id']:"")."\">No Users</option>\n";
echo "<option value=\"".$_SERVER['PHP_SELF']."?".(($_GET['department_id'])?"department_id=".$_GET['department_id']."&":"")."user_id=all\">All Users</option>\n";
//current
$ddu=$slave->select("SELECT User_ID, CONCAT(First_Name, ' ', Last_Name) as User, User_Status_ID FROM Users WHERE User_Status_ID=1 ORDER BY First_Name");
if ($ddu) {
    foreach ($ddu as $u) {
        echo "<option value=\"".$_SERVER['PHP_SELF']."?".(($_GET['department_id'])?"department_id=".$_GET['department_id']."&":"")."user_id=".$u['User_ID']."\"";
        if ($_GET['user_id']==$u['User_ID']) {
            echo "SELECTED";
        }
        echo ">".$u['User']."</option>\n";
    }
}
echo "</select></td>";

echo "<td> AND/OR Department <select name=\"department_id\" onChange=\"MM_jumpMenu('parent',this,0)\">\n";
echo "<option value=\"".$_SERVER['PHP_SELF'].(($_GET['user_id'])?"?user_id=".$_GET['user_id']:"")."\">No Departments</option>\n";
echo "<option value=\"".$_SERVER['PHP_SELF']."?".(($_GET['user_id'])?"user_id=".$_GET['user_id']."&":"")."department_id=all\">All Departments</option>\n";
$ddd=$slave->select("SELECT Department_ID, Department_Name FROM Departments ORDER BY Department_Name");
if ($ddd) {
    foreach ($ddd as $p) {
        echo "<option value=\"".$_SERVER['PHP_SELF']."?".(($_GET['user_id'])?"user_id=".$_GET['user_id']."&":"")."department_id=".$p['Department_ID']."\"";
        if ($_GET['department_id']==$p['Department_ID']) {
            echo "SELECTED";
        }
        echo ">".$p['Department_Name']."</option>\n";
    }
}
echo "</select></td>";
echo "</tr></table>\n";
        
$query="SELECT COUNT(T.Affected_Department) as Count, AD.Department_Name as Affected_Department_Name FROM Tasks as T, Departments as AD WHERE T.Affected_Department=AD.Department_ID AND Progress<100 $where GROUP BY T.Affected_Department ORDER BY Affected_Department_Name";
//echo $query;
$dep=$slave->select($query);
if ($dep) {
    foreach ($dep as $d) {
        $total+=$d['Count'];
    }
    echo "<p>Total Active Tasks$forud: $total</p>\n";
    echo "<table>";
    foreach ($dep as $d) {
        echo "<tr>";
        echo "<th>".$d['Affected_Department_Name']."</th>\n";
        echo "<td valign=\"bottom\" class=\"bar_graph\"><div style=\"width:".number_format((percent($d['Count'], $total)*3), 0)."px\"></div></td>\n";
        echo "<td>".percent($d['Count'], $total)."% (".$d['Count'].")</td>\n";
        echo "</tr>\n";
    }
    echo "</table>";
}

show_foot();
