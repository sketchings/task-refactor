<?php
    //start acknowledge tasks
if ($_POST['reminder_id']) {
    //print_r($_POST);
    $data['Acknowledgement']=date("Y-m-d H:i:s");
    $update=$db->update("Reminders", $data, "Reminder_ID=".$_POST['reminder_id']);
} elseif ($_POST['doaction']) {
    if ($_POST['doaction']=="Close") {
        $tdata['Status']=5;
    } else {
        $tdata['Status']=2;
    }
    //print_r($_POST);
    if ($_POST['old_assigned']!=$_POST['newRight'] && "d".$_POST['old_assigned']!=$_POST['newRight']
    ) {
        if (substr($_POST['newRight'], 0, 1)=="d") {
            $tdata['Department_ID']=substr($_POST['newRight'], 1);
            $reassign=substr($_POST['newRight'], 1);
            $tdata['User_ID']=0;
            $ud=$slave->select("SELECT User_ID, Department_Name FROM User_Departments as U, Departments as D WHERE U.Department_ID=D.Department_ID AND U.Department_ID=".substr($_POST['newRight'], 1)." GROUP BY U.Department_ID");
            if ($ud) {
                foreach ($ud as $use) {
                    $assigned_to.=$ud[0]['Department_Name'];
                    $where.=" OR User_ID=".$use['User_ID'];
                }
                $where="(".substr($where, 4).") AND ";
            }
        } elseif ($_POST['newRight']) {
            $reassign=$_POST['newRight'];
            $u=$slave->select("SELECT First_Name, Last_Name From Users WHERE User_ID=".$reassign);
            $assigned_to=display_name($u[0]['First_Name'], $u[0]['Last_Name']);
            $tdata['User_ID']=$reassign;
            $tdata['Department_ID']=0;
            $where="User_ID=".$reassign." AND ";
        }
        $data['Reassigned_ID']=$reassign;
    }
    $tdata['Priority']=$_POST['priority'];
    if ($_POST['finish_date']) {
        $tdata['Finish_Date'] = sqldate($_POST['finish_date']);
    } else {
        $tdata['Finish_Date']=NULL;
    }
        
    $data['User_ID']=$_SESSION['user_id'];
    $data['Acknowledged']=date("Y-m-d H:i:s");
    $data['Task_ID']=$_POST['task_id'];
    $data['Progress']=$_POST['progress'];
        
    if ($_POST['doaction']=="Reassign") {
        $data['Accepted']=0;
    } elseif ($_POST['doaction']=="Accept") {
        $data['Accepted']=1;
    } elseif ($_POST['doaction']=="Close") {
        $tdata['Progress']='100';
        $data['Progress']='100';
    }
    //work log
    if ($_POST['public_note']) {
        $work['Task_ID']=$_POST['task_id'];
        $work['Log_Date']=date("Y-m-d H:i:s");
        $work['Log_Progress']=$data['Progress'];
        $work['Public_Note'] = str_replace("\n", "<br />", $_POST['public_note']);
        $work['User_ID']=$_SESSION['user_id'];
        $insert=$db->insert("Task_Logs", $work);
        $data['Log_ID']=$insertid;
        $delete = $db->delete("Task_Acknowledgement", "Task_ID=".$slave->mySQLQuote($_POST['task_id']));
    }
    $update=$db->update("Tasks", $tdata, "Task_ID=".$_POST['task_id']);
    //if ($_POST['doaction']!="Close") {
    //if ($where)
    $insert=$db->insert("Task_Acknowledgement", $data);
    //}
    //send alert
    $query="SELECT Creator_ID, User_ID, Department_ID, Task_Name FROM Tasks WHERE (Creator_ID!=".$_SESSION['user_id']." OR User_ID!=".$_SESSION['user_id'].") AND Task_ID=".$data['Task_ID'];
    $creid=$slave->select($query);
    if ($data['Reassigned_ID']) {
        $create="U.User_ID=".$data['Reassigned_ID']." OR ";
    }
    if ($creid) {
        if ($creid[0]['Creator_ID']!=$_SESSION['user_id']) {
            $create.="U.User_ID=".$creid[0]['Creator_ID']." OR ";
        }
        if ($creid[0]['User_ID']==0) {
            $ud=$slave->select("SELECT User_ID FROM User_Departments as U WHERE U.Department_ID=".$creid[0]['Department_ID']);
            if ($ud) {
                foreach ($ud as $use) {
                    $create.="U.User_ID=".$use['User_ID']." OR ";
                }
            }
        } elseif ($creid[0]['User_ID']!=$_SESSION['user_id']) {
            $create.="U.User_ID=".$creid[0]['User_ID']." OR ";
        }
    }
    //echo $create;
    $create=substr($create, 0, -4);
    if ($create) {
        $ruser=$slave->select("SELECT U.User_ID, First_Name, Last_Name, Email, Cell_Phone, Alert_Email, Alert_Text, Text_Domain FROM Users as U, User_Preferences as P WHERE U.User_ID=P.User_ID AND ($create)");
        if ($ruser) {
            foreach ($ruser as $r) {
                $message="";
                //echo $data['Reassigned_ID']."<br >".$r['User_ID']."<br >";
                if ($data['Reassigned_ID']==$r['User_ID']) {
                    $subject= "CRM ".$priorities[$_POST['priority']]['Name']." task ".$_POST['task_id']." asssigned to you";
                } else {
                    $subject="CRM ".$priorities[$_POST['priority']]['Name']." task ".$_POST['task_id']." updated";
                    $message="Assigned: $assigned_to\r\n";
                }
                if (($_POST['priority']==1 or $r['Alert_Text']==1) and $r['Cell_Phone'] and $r['Text_Domain']) {
                    if ($message) {
                        $message.=" -> ";
                    }
                    mail($r['Cell_Phone'].$r['Text_Domain'], $subject, $message.$creid[0]['Task_Name']." -> ".$work['Public_Note']);
                }
                if (($_POST['priority']==1 or $r['Alert_Email']==1) and $r['Email']) {
                    $requester=$slave->select("SELECT Email, First_Name, Last_Name FROM Users WHERE User_ID=".$_SESSION['user_id']);
                    $message.= "<br />FROM: ".display_name($requester[0]['First_Name'], $requester[0]['Last_Name'])."\r\n<br />";
                    $message.= "Name: ".$creid[0]['Task_Name']."\r\n<br />";
                    $message.= $r['Task_Description']."\r\n<br />";
                    $tsk=$slave->select("SELECT * FROM Task_Logs WHERE Task_ID=".$data['Task_ID']);
                    if ($tsk) {
                        foreach ($tsk as $t) {
                            $message.= $t['Public_Note']."\r\n<br />";
                        }
                    }
                    if (valid_email($requester[0]['Email'])) {
                        $ftemail=$requester[0]['Email'];
                    } else {
                        $ftemail=$noreply_email;
                    }
                    //print_r($r);
                    //print_r($requester);
                    smtp_mail("", "", $r['Email'], $ftemail, display_name($requester[0]['First_Name'], $requester[0]['Last_Name']), $ftemail, $subject, $message);
                }
            }
        }
    }
} elseif ($_POST['acknowledge']) {
    foreach ($_POST['acknowledge'] as $tid=>$ack) {
        $data['User_ID']=$_SESSION['user_id'];
        $data['Acknowledged']=date("Y-m-d H:i:s");
        $data['Accepted']='1';
        $data['Task_ID']=$tid;
        $data['Progress']=$_POST['progress'][$tid];
        $insert=$db->insert("Task_Acknowledgement", $data);
    }
} elseif ($_POST['task_id']) {
    $data['User_ID']=$_SESSION['user_id'];
    $data['Acknowledged']=date("Y-m-d H:i:s");
    $data['Accepted']='1';
    foreach ($_POST['task_id'] as $tid) {
        $data['Task_ID']=$tid;
        $data['Progress']=$_POST['progress'][$tid];
        $insert=$db->insert("Task_Acknowledgement", $data);
    }
}
