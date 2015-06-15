<?php
if (!$_GET['sid']) {
    if (!$_POST and !strstr($_SERVER['SERVER_NAME'], "dev")) {
        $database="reports";
    }
    include_once"../global.php";
    
    if ($_POST) {
        include"acknowledge_actions.php";
    }
}
$pri=$slave->select("SELECT * FROM Task_Priority");
$priorities=array();
foreach ($pri as $p) {
    $priorities[$p['Priority_ID']]['Name']=$p['Priority_Name'];
    $priorities[$p['Priority_ID']]['Description']=$p['Priority_Description'];
}
//echo "ALENA IS WORKING ON TASKS";
//echo "<p>Alena changed the way new tasks assigned to you and acknowledgements work. Hopefully this will keep things from slipping through the cracks. You may see extras you haven't acknowledged. Let alena know if things don't seem to be correct</p>";
$error="";
$users=$slave->select("SELECT User_ID, First_Name, Last_Name, User_Status_ID FROM Users ORDER BY First_Name");
    
if (!$_GET['sid']) {
    show_head($title);
    echo "<h1>Incoming Queue</h1>\n";
}
    $query="SELECT T.Request_User_ID, if (D.User_ID, D.User_ID, '') as Department_User_ID, T.User_ID as Assigned_User_ID,T.Department_ID, T.Task_ID,Task_Name, Task_Description, Owner_ID, Finish_Date,T.Progress,Priority, Creator_ID, Request_User_ID, R.First_Name as Requested_First, R.Last_Name as Requested_Last, Creation_Date FROM Tasks as T LEFT OUTER JOIN User_Departments as D ON T.Department_ID=D.Department_ID AND D.User_ID=".$_SESSION['user_id']." LEFT OUTER JOIN Task_Acknowledgement as A ON T.Task_ID=A.Task_ID AND A.User_ID=".$_SESSION['user_id'].", Users as R WHERE R.User_ID=Request_User_ID AND Task_Acknowledgement_ID IS NULL AND (T.Request_User_ID=".$_SESSION['user_id']."  OR T.Creator_ID=".$_SESSION['user_id']." OR T.User_ID=".$_SESSION['user_id']." OR D.User_ID=".$_SESSION['user_id'].") AND T.Progress<100 GROUP BY T.Task_ID ORDER BY Accepted DESC, Priority ASC, T.Creation_Date ASC";
    //if ($_SESSION['user_id']==6) echo $query;
    $all_tasks=$slave->select($query);
if ($all_tasks) {
    foreach ($all_tasks as $t) {
        $logs=$slave->select("SELECT L.*, U.First_Name, U.Last_Name FROM Task_Logs as L, Users as U WHERE Task_ID=".$t['Task_ID']." AND L.User_ID=U.User_ID ORDER BY Log_ID DESC LIMIT 1");
        if ($logs) {
            $t['Log_User']=$logs[0]['User_ID'];
            $t['Log_First']=$logs[0]['First_Name'];
            $t['Log_Last']=$logs[0]['Last_Name'];
            $t['Log_Date']=$logs[0]['Creation_Date'];
            $t['Public_Note']=$logs[0]['Public_Note'];
            $t['Log_Note']=$logs[0]['Log_Note'];
            $t['Log_ID']=$logs[0]['Log_ID'];
                
            if ($t['Progress']<100) {
                if ($t['Assigned_User_ID']==$_SESSION['user_id']) {
                    $new_tasks[]=$t;
                } else {
                    $working_tasks[]=$t;
                }
            } else {
                if ($logs[0]['User_ID']!=$_SESSION['user_id']) {
                    $working_tasks[]=$t;
                }
            }
        } else {
            $t['Log_First']=$t['Requested_First'];
            $t['Log_Last']=$t['Requested_Last'];
            if ($_SESSION['user_id']!=$t['Creator_ID']) {
                if ($t['Assigned_User_ID']==$_SESSION['user_id']) {
                    $new_tasks[]=$t;
                } else {
                    $working_tasks[]=$t;
                }
            }
        }
    }
}
    $query="SELECT L.*,T.Request_User_ID, T.User_ID as Assigned_User_ID,T.Department_ID, T.Task_ID,Task_Name, Task_Description, Owner_ID, Finish_Date,T.Progress,Priority, Creator_ID, Request_User_ID, R.First_Name as Requested_First, R.Last_Name as Requested_Last, T.Creation_Date FROM Tasks as T LEFT OUTER JOIN Task_Acknowledgement as A ON T.Task_ID=A.Task_ID AND A.User_ID=".$_SESSION['user_id'].", Users as R, (SELECT * FROM Task_Logs ORDER BY Log_ID DESC) as L WHERE R.User_ID=Request_User_ID AND T.Task_ID=L.Task_ID AND Task_Acknowledgement_ID IS NULL AND T.Creator_ID=".$_SESSION['user_id']." AND L.User_ID!=".$_SESSION['user_id']." AND T.Progress=100 GROUP BY T.Task_ID ORDER BY Accepted DESC, Priority ASC, T.Creation_Date ASC";
    //if ($_SESSION['user_id']==6) echo $query;
    $closed_tasks=$slave->select($query);
if ($closed_tasks) {
    foreach ($closed_tasks as $t) {
        $working_tasks[]=$t;
    }
}
    /*if ($_SESSION['user_id']==6) {
        echo "new tasks";
        print_r($new_tasks);
        echo "<p>working tasks</p>";
        print_r($working_tasks);
    }*/
    //(Creator_ID!=".$_SESSION['user_id']." AND ((SELECT X.User_ID FROM Task_Logs as X WHERE X.Task_ID=T.Task_ID ORDER BY Log_ID DESC LIMIT 1) IS NULL OR (SELECT X.User_ID FROM Task_Logs as X WHERE X.Task_ID=T.Task_ID ORDER BY Log_ID DESC LIMIT 1)!=".$_SESSION['user_id'].")) AND
    //$new_tasks=$slave->select("SELECT T.Request_User_ID, if (D.User_ID, D.User_ID, '') as Department_User_ID, T.User_ID as Assigned_User_ID,T.Department_ID, T.Task_ID,Task_Name, Task_Description, Owner_ID, Finish_Date,T.Progress,Priority, Creator_ID, Request_User_ID,U.First_Name,U.Last_Name,U.User_ID, CONCAT(R.First_Name,' ',R.Last_Name) as Requested_User FROM Tasks as T LEFT OUTER JOIN User_Departments as D ON T.Department_ID=D.Department_ID AND D.User_ID=".$_SESSION['user_id']." LEFT OUTER JOIN Task_Acknowledgement as A ON T.Task_ID=A.Task_ID AND A.User_ID=".$_SESSION['user_id']." AND Accepted=1, Users as U, Users as R WHERE T.Request_User_ID=R.User_ID AND (Task_Acknowledgement_ID IS NULL) AND (Creator_ID!=".$_SESSION['user_id']." AND ((SELECT X.User_ID FROM Task_Logs as X WHERE X.Task_ID=T.Task_ID ORDER BY Log_ID DESC LIMIT 1) IS NULL OR (SELECT X.User_ID FROM Task_Logs as X WHERE X.Task_ID=T.Task_ID ORDER BY Log_ID DESC LIMIT 1)!=".$_SESSION['user_id'].")) AND (D.User_ID=U.User_ID OR T.User_ID=U.User_ID) AND (T.User_ID=".$_SESSION['user_id']." OR D.User_ID=".$_SESSION['user_id'].") GROUP BY T.Task_ID ORDER BY Accepted DESC, Priority DESC, T.Creation_Date ASC");
    //print_r($new_tasks);
    
if ($new_tasks) {
    echo "<fieldset><legend><strong>These tickets have been assigned to you</strong></legend>\n";
    ?>
<script type="text/javascript">
<!--
var which_submit="";
function isReady(form) {
    if (form.newRight.value!="d"+form.old_assigned.value && form.newRight.value!=form.old_assigned.value && which_submit!="Reassign") {
        alert("You cannot reassign and accept, please choose Reassign");
        form.newRight.focus();
        return false;
    } else if (which_submit=="Reassign" && form.newRight.value==form.old_assigned.value) {
        alert("You must choose someone to reassign the ticket to");
        form.newRight.focus();
        return false;
    } else if (which_submit!="Accept") {
        if (form.public_note.value=="") {
            alert("You must enter a reason");
            form.public_note.focus();
            return false;
        }
    }
    form.doaction.value=which_submit;
    return true;
}
//-->
</script>
    <?php
    //print_r($new_tasks);
    //echo "<table><tr><th>Task</th><th>From</th><th>Due</th><th colspan=2>Action</th></tr>\n";
    echo "<table>";
    $loop=0;
    foreach ($new_tasks as $t) {
        if ($loop>0) {
            echo "<tr><td colspan=2><hr /></td></tr>\n";
        }
        $loop++;
        if ($t['Department_ID']>0) {
            $old_assigned=$t['Department_ID'];
        } else {
            $old_assigned=$t['Assigned_User_ID'];
        }
        echo "<tr><td>";
        $files=$slave->select("SELECT * FROM Files as F, File_Types as T WHERE F.File_Type_ID=T.File_Type_ID AND Log_ID=0 AND Task_ID=".$t['Task_ID']);
        if ($files) {
            foreach ($files as $f) {
                if ($f['Image']==1) {
                    $icon="111.png";
                } else {
                    $icon="3.png";
                }
                echo "<a href=\"${target_path}".$f['File_ID'].".".$f['Extention']."\" target=\"_blank\"><img src=\"".CDN."img/icons/$icon\" border=0 /></a> ";
            }
        }
        echo "Subject: ";
        if ($t['High_Priority']) {
            echo "<img src=\"".CDN."img/fire.png\" alt=\"High Priority\" />\n";
        }
        echo "<a href=\"/projects/tasks.php?task_id=".$t['Task_ID']."\">".$t['Task_Name']."</a>"; //</td>\n";
        //echo "<td>";
        if ($t['Requested_First']) {
            echo " (".display_name($t['Requested_First'], $t['Requested_Last']).")<br />";//</td>\n";
        }     //echo $t['Request_User_ID']."<br>".$t['Department_User_ID']."<br>".$t['Assigned_User_ID'];
        echo $t['Task_Description'];                //echo "<td>".$finish_date."</td>\n";
        /*
        $reassignment=$slave->select("SELECT L.Creation_Date, Public_Note, U.First_Name, U.Last_Name FROM Task_Logs as L, Users as U WHERE L.User_ID=U.User_ID AND L.Task_ID=".$t['Task_ID']." ORDER BY Log_ID DESC LIMIT 1");
        if ($reassignment) {
        foreach ($reassignment as $r) {
        //Reassigned to ".$r['Assigned_First']." ".$r['Assigned_Last']." b
        echo "<p><strong>FROM ".$r['First_Name']." ".$r['Last_Name']." on ".display_date($r['Creation_Date'])." at ".display_time($r['Creation_Date']).":</strong><br />".$r['Public_Note']."</p>\n";
        }
        }
        */
        if ($t['Log_Date']) {
            echo "<p><strong>FROM ".$t['Log_First']." ".$t['Log_Last']." on ".display_date($t['Log_Date'])." at ".display_time($t['Log_Date']).":</strong><br />".$t['Public_Note']."</p>\n";
        }
        echo "</td><td><form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" onSubmit=\"return isReady(this)\">";
        if (!$t['Finish_Date'] or $t['Finish_Date']=="0000-00-00") {
            $finish_date="";
        } else {
            $finish_date=display_date($t['Finish_Date']);
        }

        ?>
                
 <strong>Take Action (ID <?php echo $t['Task_ID'];
        ?>)</strong><br />
 Reason (optional to accept)<br />
 <textarea name="public_note"></textarea><br />
 <table><tr><td>Severity</td><td><select name="priority">
  <option value="" selected>Select One</option>
    <?php
    //priority
    foreach ($priorities as $key=>$val) {
        echo "<option value=\"".$key."\"";
        if ($key==$t['Priority']) {
            echo " selected";
        }
        echo ">".$val['Name']."</option>\n";
    }
        ?>
   </select></td></tr>
   <tr><td>Due Date</td><td><input type="text" name="finish_date" value="<?php echo $finish_date;
        ?>" onfocus="this.select();lcs(this)" onclick="event.cancelBubble=true;this.select();lcs(this)" /></td></tr>
<tr><td>Assign To</td><td><select name="newRight">
    <OPTGROUP LABEL="Departments">
<?php
$departments=$slave->select("SELECT Department_ID, Department_Name FROM Departments");
//view dropdown of existing departments
for ($d=0;$d<count($departments);$d++) {
    echo "<option value=\"d".$departments[$d]['Department_ID']."\"";
    if ($departments[$d]['Department_ID']==$t['Department_ID']) {
        echo " SELECTED";
    }
    echo ">".$departments[$d]['Department_Name']."</option>\n";
}
        ?>
    </OPTGROUP>
    <OPTGROUP LABEL="Users">
<?php
//view dropdown of existing departments
for ($u=0;$u<count($users);$u++) {
    if ($users[$u]['User_Status_ID']==1) {
        echo "<option value=\"".$users[$u]['User_ID']."\"";
        if ($users[$u]['User_ID']==$t['Assigned_User_ID']) {
            echo " SELECTED";
        }
        echo ">".$users[$u]['First_Name']." ".$users[$u]['Last_Name']."</option>\n";
    }
}
        ?>
    </OPTGROUP>
  </select></td></tr></table>
<?php
                echo "<input type=\"hidden\" name=\"task_id\" value=\"".$t['Task_ID']."\" />";
        echo "<input type=\"hidden\" name=\"progress\" value=\"".$t['Progress']."\" />";
        echo "<input type=\"hidden\" name=\"old_assigned\" value=\"".$old_assigned."\" />";
        echo "<input type=\"hidden\" name=\"doaction\" value=\"\" />";
        echo "<input type=\"submit\" name=\"reassign\" value=\"Reassign\" onclick=\"javascript:which_submit='Reassign';\">";
        echo "<input type=\"submit\" name=\"accept\" value=\"Accept\" onclick=\"javascript:which_submit='Accept';\">";
        echo "<input type=\"submit\" name=\"close\" value=\"Close\" onclick=\"javascript:which_submit='Close';\"></td>";
        echo "</form></td></tr>\n";
    }
    echo "</table></fieldset>\n";
}
    //$working_tasks=$slave->select("SELECT L.Log_ID, T.Task_ID,Task_Name,Owner_ID,T.Progress,Priority, Creator_ID, Request_User_ID,U.First_Name as Assigned_First,U.Last_Name as Assigned_Last,U.User_ID as Assigned_User_ID,LU.Last_Name,LU.First_Name,LU.User_ID, Log_Date, Log_Note, Public_Note, LU.User_ID as Log_User FROM Tasks as T LEFT OUTER JOIN User_Departments as D ON T.Department_ID=D.Department_ID AND D.User_ID=".$_SESSION['user_id']." LEFT OUTER JOIN Users as U ON T.User_ID=U.User_ID, Users as LU, Task_Logs as L LEFT OUTER JOIN Task_Acknowledgement as A ON L.Task_ID=A.Task_ID AND A.User_ID=".$_SESSION['user_id']." AND Accepted=1 WHERE L.User_ID!=".$_SESSION['user_id']." AND L.Task_ID=T.Task_ID AND Task_Acknowledgement_ID IS NULL AND L.User_ID=LU.User_ID AND (T.Creator_ID=".$_SESSION['user_id']." OR T.Request_User_ID=".$_SESSION['user_id']." OR User_Department_ID IS NOT NULL) GROUP BY Task_ID ORDER BY Progress ASC");
    //print_r($working_tasks);
    //print_r($_SESSION);
if ($working_tasks) {
    echo "<fieldset><legend><strong>These tickets have been worked on</strong></legend>\n";
    echo "<p class=\"error\">These are simply to notify you that they have been put into the system or worked on them 100% means they are complete. There is nothing for you to do but acknowledge</p>";
    //print_r($new_tasks);
    echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" name=\"acknowledge\" id=\"acknowledge\" >";
    echo "<table><tr><th><input type=\"checkbox\" onclick=\"checkUncheckAll(this,'acknowledge');\" />";
    echo "</th><th>Progress</th><th>ID</th><th>Task</th><th>File</th><th>Assigned</th><th>From</th><th>Entered Date</th><th>Action</th></tr>\n";
    foreach ($working_tasks as $t) {
        if ($t['Public_Note']) {
            $note=true;
        } else {
            $note=false;
        }
        if ($t['Log_User']) {
            $muid=$t['Log_User'];
        } else {
            $muid=$t['Creator_ID'];
        }
        if ($slave->select("SELECT Manager_ID FROM Users WHERE User_ID=$muid AND Manager_ID=".$_SESSION['user_id'])) {
            $note=true;
            $manager=true;
        } else {
            $manager=false;
        }
        echo "<tr>";
        echo "<td><input type=\"checkbox\" name=\"task_id[]\" value=\"".$t['Task_ID']."\" /></td>\n";
        echo "<td>".$t['Progress']."%</td>\n";
        echo "<td>".$t['Task_ID']."</td>\n";
        echo "<td>";
        if ($t['High_Priority']) {
            echo "<img src=\"".CDN."img/fire.png\" alt=\"High Priority\" />\n";
        }
        //if ($note) echo "<a href=\"javascript:showID('note_".$t['Task_ID']."')\">";
        echo "<a href=\"/projects/tasks.php?task_id=".$t['Task_ID']."\">";
        echo $t['Task_Name'];
        echo "</a>";
        //if ($note) echo "</a>";
        echo "</td>\n";
        echo "<td>";
        if ($t['Log_ID']) {
            $log="AND Log_ID=".$t['Log_ID'];
        }
        $files=$slave->select("SELECT * FROM Files as F, File_Types as T WHERE F.File_Type_ID=T.File_Type_ID $log AND Task_ID=".$t['Task_ID']);
        if ($files) {
            foreach ($files as $f) {
                if ($f['Image']==1) {
                    $icon="111.png";
                } else {
                    $icon="3.png";
                }
                echo "<a href=\"${target_path}".$f['File_ID'].".".$f['Extention']."\" target=\"_blank\"><img src=\"".CDN."img/icons/$icon\" border=0 /></a> ";
            }
        }
        echo "</td>\n";
        if ($t['Progress']==100) {
            echo "<td>Complete</td>";
        } else {
            if ($t['Department_ID']) {
                $department=$slave->select("SELECT Department_Name FROM Departments WHERE Department_ID=".$t['Department_ID']);
                $assigned=$department[0]['Department_Name'];
            } elseif ($t['Assigned_User_ID']) {
                $user=$slave->select("SELECT First_Name, Last_Name FROM Users WHERE User_ID=".$t['Assigned_User_ID']);
                $assigned=display_name($user[0]['First_Name'], $user[0]['Last_Name']);
            }
            echo "<td>$assigned</td>\n";
        }
        echo "<td>".display_name($t['Log_First'], $t['Log_Last'])."</td>\n";
        if ($t['Log_Date']) {
            $finish_date=display_date($t['Log_Date']);
        } else {
            $finish_date=display_date($t['Creation_Date']);
        }
        echo "<td>".$finish_date."</td>\n";
        echo "<td>";
        echo "<input type=\"hidden\" name=\"progress[".$t['Task_ID']."]\" value=\"".$t['Progress']."\" />";
        echo "<input type=\"submit\" name=\"acknowledge[".$t['Task_ID']."]\" value=\"Acknowledge\"></td></tr>\n";
        //if ($note) {
        echo "<tr><td colspan=9>";
        //echo "<div id=\"note_".$t['Task_ID']."\" style=\"display:none\">";
        if ($t['Log_ID']) {
            echo $t['Public_Note'];
            if ($_SESSION['manager'] and $t['Log_Note']) {
                echo "<br /><i><strong>Private:</strong> ".$t['Log_Note']."</i>\n";
            }
        } else {
            echo $t['Task_Description'];
        }
        //echo "</div>";
        echo "</td></tr>";
        //}
        echo "<tr><td colspan=9><hr /></td></tr>\n";
    }
    echo "</table>";
    echo "<input type=\"submit\" value=\"Acknowledge Checked\" />";
    echo "</form></fieldset>\n";
}
    //end acknowledge tasks
    //denied tasks
if (!$_GET['sid']) {
    show_foot();
}
?>
