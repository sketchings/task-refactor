<?php
require_once"../global.php";

$security=get_security("Projects");

$priorities[1]="Low";
$priorities[2]="Normal";
$priorities[3]="High";
$priorities[4]="EMERGENCY";

$users=$slave->select("SELECT User_ID, First_Name, Last_Name FROM Users ORDER BY First_Name");
$selected_users=array();
$selected_departments=array();
if ($_GET['project_id']) {
    if ($_GET['project_id'] == "new") {
        $selected_users[]=$_SESSION['user_id'];
    } else {
        $task_users=$slave->select("SELECT User_ID FROM Project_Users WHERE User_ID>0 AND Project_ID=".$_GET['project_id']);
        if ($task_users) {
            foreach ($task_users as $tu) {
                $selected_users[]=$tu['User_ID'];
            }
        }
        $task_departments=$slave->select("SELECT Department_ID FROM Project_Users WHERE Project_ID=".$_GET['project_id']);
        if ($task_departments) {
            foreach ($task_departments as $td) {
                $selected_departments[]=$td['Department_ID'];
            }
        }
    }
}

if ($_GET['delete']) {
    $delete = $db->delete("Projects", "Project_ID=".$slave->mySQLQuote($_GET['delete']));
    if ($delete==true) {
        $error .= "<br />Project Deleted";
    }
}
$users=$slave->select("SELECT User_ID, First_Name, Last_Name FROM Users ORDER BY First_Name");
$selected_users=array();
if ($_GET['project_id']) {
    if ($_GET['project_id'] == "new") {
        $selected_users[]=$_SESSION['user_id'];
    } else {
        $task_users=$slave->select("SELECT User_ID FROM Project_Users WHERE Project_ID=".$_GET['project_id']);
        if ($task_users) {
            foreach ($task_users as $tu) {
                $selected_users[]=$tu['User_ID'];
            }
        }
    }
    $project_id=$_GET['project_id'];
    if ($_GET['project_id'] == "new") {
        $owner_id=$_SESSION['user_id'];
        $company_id=1;
        $priority=2;
    } else {
        $_SESSION['project_id']=$_GET['project_id'];
        unset($_SESSION['project_user_id']);
        //get values for form
        $projects = $slave->select("SELECT * FROM Projects WHERE Project_ID=".$slave->mySQLQuote($_GET['project_id']));
        $project_name=$projects[0]['Project_Name'];
        $project_description=$projects[0]['Project_Description'];
        $company_id=$projects[0]['Company_ID'];
        $client_id=$projects[0]['Client_ID'];
        $start_date=display_date($projects[0]['Start_Date']);
        $target_end_date=display_date($projects[0]['Target_End_Date']);
        $actual_end_date=display_date($projects[0]['Actual_End_Date']);
        $target_budget=$projects[0]['Target_Budget'];
        $actual_budget=$projects[0]['Actual_Budget'];
        $production_url=$projects[0]['Production_URL'];
        $staging_url=$projects[0]['Staging_URL'];
        $status_id=$projects[0]['Status_ID'];
        $priority=$projects[0]['Priority'];
        $creator_id=$projects[0]['Creator_ID'];
        $creation_date=$projects[0]['Creation_Date'];
        $owner_id=$projects[0]['Owner_ID'];
    }
} elseif ($_POST['submit']=="Cancel") {
    header("Location: tasks.php");
} elseif ($_POST['submit']=="Delete") {
    $delete = $db->delete("Projects", "Project_ID=".$slave->mySQLQuote($_POST['project_id']));
} elseif (isset($_POST['project_id'])) {
    $data['Project_Name'] = $_POST['project_name'];
    $data['Project_Description'] = $_POST['project_description'];
    $data['Company_ID']=$_POST['company_id'];
    if ($_POST['client_id']) {
        $data['Client_ID']=$_POST['client_id'];
    }
    $data['Start_Date'] = sqldate($_POST['start_date']);
    $data['Target_End_Date'] = sqldate($_POST['target_end_date']);
    $data['Target_Budget'] = $_POST['target_bidget'];
    $data['Production_URL'] = $_POST['production_url'];
    $data['Staging_URL'] = $_POST['staging_url'];
    $data['Status_ID']=$_POST['status_id'];
    $data['Priority']=$_POST['priority'];
    $data['Creator_ID']=$_SESSION['user_id'];
    $data['Owner_ID']=$_POST['owner_id'];
    
    if ($_POST['project_id']=="new") {
        $insert = $db->insert("Projects", $data); //|| $error=$db->error;
        $pdata['Project_ID']=$insertid;
    } else {
        $update = $db->update("Projects", $data, "Project_ID=".$slave->mySQLQuote($_POST['project_id']));
        $pdata['Project_ID']=$_POST['project_id'];
    }
    
    //delete users
    $delete=$db->delete("Project_Users", "Project_ID=".$pdata['Project_ID']);
    if (!$_POST['newRight']) {
        $_POST['newRight']=$_SESSION['user_id'];
    }
    foreach (explode(",", $_POST['newRight']) as $tu) {
        if (substr($tu, 0, 1)=="d") {
            $pdata['Department_ID'] = substr($tu, 1);
        } else {
            $pdata['User_ID'] = $tu;
        }
        
        $insert=$db->insert("Project_Users", $pdata);
    }
}
if (isset($_GET['project_id'])) {
    $extra='<script language="JavaScript" src="'.CDN.'js/option_transfer.js"></script>';
}
$title="Project Management";
show_head($title, false, $extra, true);

if ($error) {
    echo "<p>$error</p>";
}
if (isset($project_id)) {
    if ($project_id=="new") {
        $do_action="Add Project";
    } else {
        $do_action="Modify Project";
    }
    echo "<h1>$do_action</h1>";
    ?>
<form name="return_reasons" id="form" method="post" action="projects.php"  enctype="multipart/form-data">
<table>
<tr>
    <td class="label">Name</td>
    <td><input name="project_name" type="text" value="<?php echo $project_name;
    ?>" /></td>
</tr>
<tr>
    <td class="label">Description</td>
    <td><textarea name="project_description"><?php echo $project_description;
    ?></textarea></td>
</tr>
<tr>
    <td class="label">Company</td>
    <td><select name="company_id">
    <option value="" selected>Select One</option>
<?php
//view dropdown of existing departments
$company = $slave->select("SELECT * FROM Companies ORDER BY Company_Name");
    if ($company) {
        for ($y=0; $y<count($company); $y++) {
            echo "<option value=\"".$company[$y]['Company_ID']."\"";
            if ($company[$y]['Company_ID']==$company_id) {
                echo " selected";
            }
            echo ">".$company[$y]['Company_Name']."</option>\n";
        }
    }
    ?>
  </select></td>
</tr>
<tr>
    <td class="label">Clients</td>
    <td><select name="client_id">
    <option value="" selected>Select One</option>
<?php
//view dropdown of existing departments
$clients = $slave->select("SELECT * FROM Clients as I LEFT OUTER JOIN Company_Locations as L ON I.Company_Location_ID=L.Company_Location_ID, Companies as C WHERE I.Company_ID=C.Company_ID ORDER BY Company_Name");
    if ($clients) {
        for ($y=0; $y<count($clients); $y++) {
            echo "<option value=\"".$clients[$y]['Client_ID']."\"";
            if ($clients[$y]['Client_ID']==$client_id) {
                echo " selected";
            }
            echo ">".$clients[$y]['Company_Name'];
            if ($clients[$y]['Location_Name']) {
                echo " (".$clients[$y]['Location_Name'].")";
            }
            echo " - ".$clients[$y]['Contact_Name'];
            echo "</option>\n";
        }
    }
    ?>
  </select></td>
</tr>
<tr>
    <td class="label">Start Date</td>
    <td><input type="text" name="start_date" value="<?php if ($start_date) {
    echo $start_date;
} else {
    echo date('m/d/Y');
}
    ?>" onfocus="this.select();lcs(this)" onclick="event.cancelBubble=true;this.select();lcs(this)" /></td>
</tr>
<tr>
    <td class="label">Target End Date</td>
    <td><input type="text" name="target_end_date" value="<?php echo $target_end_date;
    ?>" onfocus="this.select();lcs(this)" onclick="event.cancelBubble=true;this.select();lcs(this)" /></td>
</tr>
<tr>
    <td class="label">Target Budget</td>
    <td>$<input type="text" name="target_budget" value="<?php echo $target_budget;
    ?>" size=7 /></td>
</tr>
<tr>
    <td class="label">Production URL</td>
    <td><input type="text" name="production_url" value="<?php echo $production_url;
    ?>" /></td>
</tr>
<tr>
    <td class="label">Staging URL</td>
    <td><input type="text" name="staging_url" value="<?php echo $staging_url;
    ?>" /></td>
</tr>
<tr>
    <td class="label">Status</td>
    <td><select name="status_id">
    <option value="" selected>Select One</option>
<?php
//view dropdown of existing departments
$status = $slave->select("SELECT * FROM Project_Status ORDER BY Status_ID ASC");
    if ($status) {
        for ($y=0; $y<count($status); $y++) {
            echo "<option value=\"".$status[$y]['Status_ID']."\"";
            if ($status[$y]['Status_ID']==$status_id) {
                echo " selected";
            }
            echo ">".$status[$y]['Status_Name']."</option>\n";
        }
    }
    ?>
  </select></td>
</tr>
<tr>
    <td class="label">Priority</td>
    <td><select name="priority">
    <option value="" selected>Select One</option>
<?php
//priority
foreach ($priorities as $key=>$val) {
    echo "<option value=\"".$key."\"";
    if ($key==$priority) {
        echo " selected";
    }
    echo ">".$val."</option>\n";
}
    ?>
  </select></td>
</tr>
<tr>
    <td class="label">Owner</td>
    <td><select name="owner_id">
    <option value="" selected>Select One</option>
<?php
//view dropdown of existing departments
$status = $slave->select("SELECT * FROM Users ORDER BY Last_Name");
    if ($status) {
        for ($y=0; $y<count($status); $y++) {
            echo "<option value=\"".$status[$y]['User_ID']."\"";
            if ($status[$y]['User_ID']==$owner_id) {
                echo " selected";
            }
            echo ">".$status[$y]['Last_Name'].", ".$status[$y]['First_Name']."</option>\n";
        }
    }
    ?>
  </select></td>
</tr>
<tr><td>Assign Users</td><td>
<TABLE BORDER=0>
<tr><td>Unassigned</td><td>&nbsp;</td><td>Assigned to Task</td></tr>
<TR>
    <TD>
    <SELECT NAME="list1" MULTIPLE SIZE=10 onDblClick="opt.transferRight()">
<?php
$departments=$slave->select("SELECT Department_ID, Department_Name FROM Departments");
//view dropdown of existing departments
foreach ($departments as $d) {
    if (!in_array($d['Department_ID'], $selected_departments)) {
        echo "<OPTION VALUE=\"d".$d['Department_ID']."\">Dept: ".$d['Department_Name']."</OPTION>\n";
    }
}
    foreach ($users as $u) {
        if (!in_array($u['User_ID'], $selected_users)) {
            echo "<OPTION VALUE=\"".$u['User_ID']."\">User: ".$u['First_Name']." ".$u['Last_Name']."</OPTION>\n";
        }
    }
    ?>
    </SELECT>
    </TD>
    <TD VALIGN=MIDDLE ALIGN=CENTER>

        <INPUT TYPE="button" NAME="right" VALUE="&gt;&gt;" ONCLICK="opt.transferRight()"><BR><BR>
        <INPUT TYPE="button" NAME="right" VALUE="All &gt;&gt;" ONCLICK="opt.transferAllRight()"><BR><BR>
        <INPUT TYPE="button" NAME="left" VALUE="&lt;&lt;" ONCLICK="opt.transferLeft()"><BR><BR>
        <INPUT TYPE="button" NAME="left" VALUE="All &lt;&lt;" ONCLICK="opt.transferAllLeft()">
    </TD>
    <TD>
    <SELECT NAME="list2" MULTIPLE SIZE=10 onDblClick="opt.transferLeft()">
    <?php
    foreach ($departments as $d) {
        if (in_array($d['Department_ID'], $selected_departments)) {
            echo "<OPTION VALUE=\"d".$d['Department_ID']."\">Dept: ".$d['Department_Name']."</OPTION>\n";
        }
    }
    foreach ($users as $u) {
        if (in_array($u['User_ID'], $selected_users)) {
            echo "<OPTION VALUE=\"".$u['User_ID']."\">User: ".$u['First_Name']." ".$u['Last_Name']."</OPTION>\n";
        }
    }
    ?>
    </SELECT>
    <INPUT TYPE="hidden" NAME="newRight" VALUE="" SIZE=70>
    </TD>
</TR>
</TABLE>
</td></tr>
<tr><td>&nbsp;</td><td><input type="submit" name="submit" value="<?php echo $do_action;
    ?>" /><input type="submit" name="submit" value="Cancel" /></td></tr>
</table>
<input type="hidden" name="project_id" value="<?php echo $project_id;
    ?>" />
</form>
<SCRIPT LANGUAGE="JavaScript">
var opt = new OptionTransfer("list1","list2");
opt.setAutoSort(true);
opt.setDelimiter(",");
//opt.setStaticOptionRegex("^(Bill|Bob|Matt)$");
opt.saveRemovedLeftOptions("removedLeft");
opt.saveRemovedRightOptions("removedRight");
opt.saveAddedLeftOptions("addedLeft");
opt.saveAddedRightOptions("addedRight");
opt.saveNewLeftOptions("newLeft");
opt.saveNewRightOptions("newRight");
window.onLoad=opt.init(document.forms[0]);
</SCRIPT>
<?php
//<input type="submit" name="submit" value="Delete" onclick="if (confirm('Are you sure you want to delete?')) return true; else return false;" />
if ($project_id!="new") {
    $_GET['sid']="test";
    include_once"tasks.php";
}
} else { //list projects
    echo "<h1>Projects</h1>";
    if ($security['Add']==true) {
        echo "<p><a href=\"projects.php?project_id=new\">Add New Project</a></p>";
    }
    //show all products
    
    $projects = $slave->select("SELECT S.*,P.*, O.Location_Name, C.Company_Name as Internal, M.Company_Name, CONCAT(U.First_Name,' ',U.Last_Name) as Owner FROM Projects as P LEFT OUTER JOIN Clients as L ON P.Client_ID=L.Client_ID LEFT OUTER JOIN Company_Locations as O ON L.Company_Location_ID=O.Company_Location_ID LEFT OUTER JOIN Companies as M ON L.Company_ID=M.Company_ID, Companies as C, Project_Status as S, Users as U WHERE P.Owner_ID=U.User_ID AND P.Status_ID=S.Status_ID AND P.Company_ID=C.Company_ID ORDER BY Target_End_Date ASC");
    if ($projects) {
        echo "<table><tr><th>Project Name</th><th>Company</th><th>Client</th><th>Start</th><th>End</th><th>Actual</th><th>Priority</th><th>Owner</th><th>Tasks  (My)</th><th>Status</th></tr>";
        for ($y=0; $y<count($projects); $y++) {
            echo "<tr>";
            echo "<td><a href=\"projects.php?project_id=".$projects[$y]['Project_ID']."\">".$projects[$y]['Project_Name']."</a></td>";
            echo "<td>".$projects[$y]['Internal']."</td>\n";
            echo "<td>".$projects[$y]['Company_Name'];
            if ($projects[$y]['Location_Name']) {
                echo " (".$clients[$y]['Location_Name'].")";
            }
            echo " - ".$projects[$y]['Contact_Name']."</td>";
            echo "<td>".display_date($projects[$y]['Start_Date'])."</td>";
            echo "<td>".display_date($projects[$y]['Target_End_Date'])."</td>";
            echo "<td>".display_date($projects[$y]['Actual_End_Date'])."</td>";
            echo "<td>".$priorities[$projects[$y]['Priority']]."</td>";
            echo "<td>".$projects[$y]['Owner']."</td>";
            //tasks
            $tasks=$slave->select("SELECT COUNT(Task_ID) as Count FROM Tasks WHERE Project_ID=".$projects[$y]['Project_ID']);
            $mytasks=$slave->select("SELECT COUNT(User_ID) as Count FROM Tasks as T WHERE T.User_ID=".$_SESSION['user_id']." AND Project_ID=".$projects[$y]['Project_ID']);
            echo "<td><a href=\"tasks.php?project_id=".$projects[$y]['Project_ID']."\">".$tasks[0]['Count']." (".$mytasks[0]['Count'].")</a></td>";
            echo "<td>".$projects[$y]['Status_Name']."</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No Projects, please add New Project</p>\n";
    }
}
show_foot();
?>
