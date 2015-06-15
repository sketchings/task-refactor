<?php
require_once"../global.php";
global $db,$slave;

$error="";
$title="Open Incident Requests";
show_head($title);

echo "<h2>Your Open Incident Requests</h2>\n";

    $query="SELECT I.*,S.*,C.*,O.*,U.User_ID, U.First_Name,U.Last_Name FROM Users as U, Incidents as I LEFT OUTER JOIN Incident_Categories as C ON I.Category_ID=C.Category_ID LEFT OUTER JOIN Incident_Subjects as S ON I.Subject_ID=S.Subject_ID LEFT OUTER JOIN Incident_Origins as O ON I.Origin_ID=O.Origin_ID WHERE IF (I.Assigned_User_ID = 0, I.Entered_User_ID, I.Assigned_User_ID)=U.User_ID AND Resolution_Date IS NULL AND Entered_User_ID=".$_SESSION['user_id']." AND (Assigned_User_ID>0 AND Assigned_User_ID!=".$_SESSION['user_id'].") ORDER BY U.First_Name, Received_Date";
    //echo $query;
    $incidents = $slave->select($query);
        
        //$incident_subjects = $slave->select("SELECT * FROM Incident_Subjects ORDER BY Subject_Name");
if ($incidents) {
    $action="Edit";
    echo "\n<dl>\n";
    for ($y=0; $y<count($incidents); $y++) {
        if ($incidents[$y]['User_ID']!=$userid) {
            $userid=$incidents[$y]['User_ID'];
            if ($y>0) {
                echo "</dl>\n";
            }
            echo "<h2>".$incidents[$y]['First_Name']." ".$incidents[$y]['Last_Name']."</h2>\n<dl>\n";
        }
                
        if ($incidents[$y]['Resolution_Date']!=null and $action=="Edit") {
            if ($y==0) {
                echo "<dt>NO OPEN INCIDENTS</dt>";
            }
            echo "</dl>\n<h2>History</h2>\n<dl>\n";
            $action="View";
        }
        echo "<dt><a href=\"/csr/incidents.php?id=".$incidents[$y]['Incident_ID']."\">$action</a> ";
        echo display_date($incidents[$y]['Received_Date'])." ".$incidents[$y]['Category_Name'].": ".$incidents[$y]['Subject_Name']."</dt>\n";
        echo "<dd>".$incidents[$y]['Incident_Description']."</dd>\n";
    }
    echo "</dl>\n";
    $numin=1;
}
show_foot();
