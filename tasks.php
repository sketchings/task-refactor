<?php
if (!$_POST and !strstr($_SERVER['SERVER_NAME'], "dev")) {
    $database="reports";
}
require_once"../global.php";


$access=$_SESSION['access'];
$total_tasks=array();
$pri=$slave->select("SELECT * FROM Task_Priority ORDER BY Priority_ID ASC");
$priorities=array();
foreach ($pri as $p) {
    $priorities[$p['Priority_ID']]['Name']=$p['Priority_Name'];
    $priorities[$p['Priority_ID']]['Description']=$p['Priority_Description'];
    $help.='<tr><td><strong>'.$p['Priority_ID']."-".$p['Priority_Name'];
    $help.='</strong>, '.$p['Priority_Description'].'<br />EXAMPLES:<br />';
    $help.=preg_replace('/\n/', '<br />', $p['Examples']);
    $help.='</td></tr>';
}

$files=$slave->select("SELECT * FROM File_Types WHERE Allowed=1");
foreach ($files as $f) {
    if ($f['Image']==1) {
        $img_array[$f['File_Type_ID']]=$f['Extention'];
    }
    $allow[$f['File_Type_ID']]=$f['Extention'];
}

$type="";
$show_active="yes";
$show_complete="no";
if ($_GET['today']) {
    $today=$_GET['today'];
    $type="date";
}
if ($_GET['week']) {
    $week=$_GET['week'];
    $type="week";
    $_SESSION['project_user_id']=$_SESSION['User_ID'];
}
if ($_GET['requester']) {
    $show_complete="";
    $requester=$_GET['requester'];
    $type="req";
    unset($_SESSION['department_id']);
    unset($_SESSION['distributor_id']);
    unset($_SESSION['project_id']);
    unset($_SESSION['project_user_id']);
}
if ($_GET['assign']) {
    $show_complete="";
    $assigner=$_GET['assign'];
    $type="ass";
    unset($_SESSION['department_id']);
    unset($_SESSION['distributor_id']);
    unset($_SESSION['project_id']);
    unset($_SESSION['project_user_id']);
}
if ($_GET['action']=="inactive") {
    $show_active="no";
}
if ($_GET['action']=="both") {
    $show_complete="";
    unset($_SESSION['department_id']);
    unset($_SESSION['distributor_id']);
    unset($_SESSION['project_id']);
    unset($_SESSION['project_user_id']);
}
if ($_GET['action']=="complete" or $_GET['status']==5) {
    $show_complete="yes";
    $type="complete";
} elseif ($_GET['action']=="log") {
    $type="log";
    if (!$_GET['user_id']) {
        unset($_SESSION['department_id']);
        unset($_SESSION['distributor_id']);
        unset($_SESSION['project_id']);
        unset($_SESSION['project_user_id']);
    }
}

if ($_GET['user_id']) {
    $_SESSION['project_user_id']=$_GET['user_id'];
    unset($_SESSION['project_id']);
    unset($_SESSION['department_id']);
    unset($_SESSION['distributor_id']);
} elseif (isset($_GET['project_id'])) {
    if ($_GET['project_id']=="all") {
        unset($_SESSION['department_id']);
        unset($_SESSION['distributor_id']);
        unset($_SESSION['project_id']);
        unset($_SESSION['project_user_id']);
    } else {
        $_SESSION['project_id']=$_GET['project_id'];
        unset($_SESSION['department_id']);
        unset($_SESSION['distributor_id']);
        unset($_SESSION['project_user_id']);
    }
} elseif (isset($_GET['department_id'])) {
    $_SESSION['department_id']=$_GET['department_id'];
    unset($_SESSION['distributor_id']);
    unset($_SESSION['project_id']);
    unset($_SESSION['project_user_id']);
} elseif (isset($_GET['distributor_id'])) {
    $_SESSION['distributor_id']=$_GET['distributor_id'];
    unset($_SESSION['department_id']);
    unset($_SESSION['project_id']);
    unset($_SESSION['project_user_id']);
}


if ($_GET['expand']=="all") {
    unset($_SESSION['expand']);
    $_SESSION['expand']['all']=1;
} elseif ($_GET['expand']=="none") {
    unset($_SESSION['expand']);
    $_SESSION['expand']['all']=0;
} elseif ($_GET['expand']) {
    $_SESSION['expand'][$_GET['expand']]=1;
}
if ($_GET['collapse']) {
    $_SESSION['expand'][$_GET['collapse']]=0;
}
    
$users=$slave->select("SELECT User_ID, First_Name, Last_Name, User_Status_ID FROM Users ORDER BY First_Name");
$selected_user;
$selected_department;
if ($_GET['task_id']) {
    if ($_GET['task_id'] != "new") {
        $tasks=$slave->select("SELECT User_ID, Department_ID, Parent_Task_ID FROM Tasks WHERE User_ID>0 AND Task_ID=".$_GET['task_id']);
        if ($tasks) {
            foreach ($tasks as $tu) {
                $parent_task_id=$tu['Parent_Task_ID'];
                if ($tu['User_ID']>0) {
                    $selected_user=$tu['User_ID'];
                    $old_assigned=$tu['User_ID'];
                }
                if ($tu['Department_ID']>0) {
                    $selected_department=$tu['Department_ID'];
                    $old_assigned=$tu['Department_ID'];
                }
            }
        }
    }
    if ($_POST['parent_task_id']=="delete") {
        $up['Parent_Task_ID']=0;
        $update=$db->update("Tasks", $up, "Task_ID=".$_GET['task_id']);
    } elseif ($_POST['parent_task_id'] and $_POST['parent_task_id']!=$_GET['task_id']) {
        $up['Parent_Task_ID']=$_POST['parent_task_id'];
        $update=$db->update("Tasks", $up, "Task_ID=".$_GET['task_id']);
    }
    $task_id=$_GET['task_id'];
    if ($_GET['task_id'] == "new") {
        $owner_id=$_SESSION['user_id'];
        $priority=5;
        $status=1;
        $progress=0;
        if ($_GET['parent_task_id']) {
            $parent_task_id=$_GET['parent_task_id'];
        }
    } else {
        //get values for form
        $tasks = $slave->select("SELECT * FROM Tasks WHERE Task_ID=".$slave->mySQLQuote($_GET['task_id']));
        $task_name=$tasks[0]['Task_Name'];
        $task_description=$tasks[0]['Task_Description'];
        $owner_id=$tasks[0]['Owner_ID'];
        $parent_task_id=$tasks[0]['Parent_Task_ID'];
        $target_budget=$tasks[0]['Target_Budget'];
        if ($tasks[0]['High_Priority']==1) {
            $high_priority="CHECKED";
        }
        if (display_date($tasks[0]['Finish_Date'])=="NULL") {
            $finish_date="Not Set";
        } else {
            $finish_date=display_date($tasks[0]['Finish_Date']);
        }
        $durration=$tasks[0]['Durration'];
        $progress=$tasks[0]['Progress'];
        $status=$tasks[0]['Status'];
        $priority=$tasks[0]['Priority'];
        $priority_description=$priorities[$priority]['Description'];
        $progress=$tasks[0]['Progress'];
        $creator_id=$tasks[0]['Creator_ID'];
        $creation_date=$tasks[0]['Creation_Date'];
        $project_id=$tasks[0]['Project_ID'];
        $request_date=display_date($tasks[0]['Request_Date']);
        $request_user_id=$tasks[0]['Request_User_ID'];
        $affected_department=$tasks[0]['Affected_Department'];
        $distributor_id=$tasks[0]['Distributor_ID'];
        $company_id=$tasks[0]['Company_ID'];
        if ($tasks[0]['Billable']==1) {
            $billable="CHECKED";
        }
        $occurs=$tasks[0]['Occurs'];
        $workdays=$tasks[0]['Workdays'];
        $frequency=$tasks[0]['Frequency'];
        $month_day=$tasks[0]['Month_Day'];
        $week=$tasks[0]['Week'];
        $month=$tasks[0]['Month'];
        $start_range=$tasks[0]['Start_Range'];
        $end_range=$tasks[0]['End_Range'];
        $monday=$tasks[0]['Monday'];
        $tuesday=$tasks[0]['Tuesday'];
        $wednesday=$tasks[0]['Wednesday'];
        $thursday=$tasks[0]['Thursday'];
        $friday=$tasks[0]['Friday'];
        $saturday=$tasks[0]['Saturday'];
        $sunday=$tasks[0]['Sunday'];
    }
    if ($_GET['project_id']) {
        $project_id=$_GET['project_id'];
    }
} elseif (isset($_POST['log_date'])) {
    $log_time=$_POST['time']*$_POST['durration'];
    $data['Task_ID'] = $_POST['task_id'];
    $data['Log_Date'] = sqldate($_POST['log_date']);
    //$data['Log_Progress'] = $_POST['log_progress'];
    if ($_POST['log_progress']==100) {
        $tdata['Finish_Date']=date("Y-m-d H:i:s");
        $tdata['Status']=5;
    }
    if ($_POST['status']==5) {
        $tdata['Progress']=100;
        $data['Log_Progress']=100;
        $tdata['Status']=5;
    } else {
        $tdata['Progress']=0;
        $data['Log_Progress']=0;
        $tdata['Status']=$_POST['status'];
    }
    //$tdata['Progress'] = $_POST['log_progress'];
    if ($_POST['stop_repeating']==1) {
        $tdata['End_Range']=date("Y-m-d H:i:s");
    }
    $data['Log_Time'] = $log_time;
    $data['Log_Cost'] = $_POST['log_cost'];
    //$data['Log_Note'] = str_replace("\n","<br />",$_POST['log_note']);
    //$data['Public_Note'] = str_replace("\n","<br />",$_POST['public_note']);
    $data['Log_Note'] = $_POST['log_note'];
    $data['Public_Note'] = $_POST['public_note'];
    $data['Log_Time'] = ($_POST['log_time_hours']*60)+$_POST['log_time_minutes'];
    $data['User_ID'] = $_SESSION['user_id'];
    $insert = $db->insert("Task_Logs", $data); //|| $error=$db->error;
    
    
    $log_id=$insertid;
    $adata['Log_ID']=$log_id;
    if ($_POST['newRight']!="" && $_POST['newRight']!=$_POST['old_assigned']) {
        if (substr($_POST['newRight'], 0, 1)=="d") {
            $tdata['Department_ID'] = substr($_POST['newRight'], 1);
            $tdata['User_ID']=0;
            $dusers=$slave->select("SELECT User_ID, Department_Name FROM User_Departments as U, Departments as D WHERE U.Department_ID=D.Department_ID AND U.Department_ID=".substr($_POST['newRight'], 1)." GROUP BY U.Department_ID");
            if ($dusers) {
                foreach ($dusers as $use) {
                    $assigned_to=$use['Department_Name'];
                    if ($use['User_ID']==$_SESSION['user_id']) {
                        $adata['Accepted']=1;
                    }
                }
            } else {
                $adata['Accepted']=0;
            }
            $adata['Reassigned_ID']=$tdata['Department_ID'];
        } else {
            $u=$slave->select("SELECT First_Name, Last_Name From Users WHERE User_ID=".$_POST['newRight']);
            $assigned_to=display_name($u[0]['First_Name'], $u[0]['Last_Name']);
            
            $tdata['User_ID'] = $_POST['newRight'];
            $tdata['Department_ID']=0;
            if ($_SESSION['user_id']==$_POST['newRight']) {
                $adata['Accepted']=1;
            } else {
                $adata['Accepted']=0;
            }
            $adata['Reassigned_ID']=$tdata['User_ID'];
        }
    }
    
    $query="SELECT * FROM Tasks WHERE Task_ID=".$data['Task_ID'];
    $creid=$slave->select($query);
    
    if ($adata['Reassigned_ID']) {
        $create="U.User_ID=".$adata['Reassigned_ID']." OR ";
    }
    if ($creid) {
        if ($creid[0]['Creator_ID']!=$_SESSION['user_id']) {
            if ($creid[0]['User_ID']!=$_SESSION['user_id']) {
                $create.="U.User_ID=".$creid[0]['User_ID']." OR ";
            }
        }
    }
    $create=substr($create, 0, -4);
    //echo $create;
    $requester=$slave->select("SELECT Email, First_Name, Last_Name FROM Users WHERE User_ID=".$_SESSION['user_id']);
    if (valid_email($requester[0]['Email'])) {
        $ftemail=$requester[0]['Email'];
    } else {
        $ftemail=$noreply_email;
    }
    if ($create) {
        $ruser=$slave->select("SELECT U.User_ID, First_Name, Last_Name, Email, Cell_Phone, Alert_Email, Alert_Text, Text_Domain FROM Users as U, User_Preferences as P WHERE U.User_ID=P.User_ID AND ($create)");
        if ($ruser) {
            foreach ($ruser as $r) {
                //echo $adata['Reassigned_ID']."<br >".$slave->mySQLQuote($r['User_ID'])."<br >";
                if ($adata['Reassigned_ID']==$r['User_ID']) {
                    $subject= $priorities[$_POST['priority']]['Name']." task ".$_POST['task_id']." for you";
                } else {
                    $subject="CRM ".$priorities[$_POST['priority']]['Name']." task ".$_POST['task_id']." updated";
                    $message="Assigned: $assigned_to\r\n";
                }
                if (($_POST['priority']==1 or $r['Alert_Text']==1) and $r['Cell_Phone'] and $r['Text_Domain']) {
                    if ($message) {
                        $message.=" -> ";
                    }
                    $eol="\r\n";
                    $headers = 'Reply-To: no-reply <no-reply@iignet.com>'.$eol;
                    $headers = 'FROM: no-reply <no-reply@iignet.com>'.$eol;
                    //mail($r['Cell_Phone'].$r['Text_Domain'], $subject, $message.$creid[0]['Task_Name']." -> ".$data['Public_Note'],$headers);
                    smtp_mail("", "", $r['Cell_Phone'].$r['Text_Domain'], $noreply_email, $noreply_name, $noreply_email, $subject, $message.$creid[0]['Task_Name']." -> ".$data['Public_Note']);
                }
                if (($_POST['priority']==1 or $r['Alert_Email']==1) and $r['Email']) {
                    $message= "FROM: ".display_name($requester[0]['First_Name'], $requester[0]['Last_Name'])."\r\n<br />";
                    $message.= "Name: ".$creid[0]['Task_Name']."\r\n<br />";
                    $message.= $creid[0]['Task_Description']."\r\n<br />";
                    $tsk=$slave->select("SELECT * FROM Task_Logs WHERE Task_ID=".$data['Task_ID']);
                    foreach ($tsk as $t) {
                        $message.= $t['Task_Description']."\r\n<br />";
                    }
                    smtp_mail("", "", $r['Email'], $ftemail, display_name($requester[0]['First_Name'], $requester[0]['Last_Name']), $ftemail, $subject, $message);
                }
            }
        }
    }
    //remove task_acknowledge
    $insert=$db->delete("Task_Acknowledgement", "Task_ID=".$_POST['task_id']);
    //task_acknowlegement
    $adata['User_ID']=$_SESSION['user_id'];
    $adata['Acknowledged']=date("Y-m-d H:i:s");
    $adata['Accepted']=1;
    $adata['Task_ID']=$_POST['task_id'];
    if ($_POST['progress']) {
        $adata['Progress'] = $_POST['log_progress'];
    }
    $insert=$db->insert("Task_Acknowledgement", $adata);
    //if ($insert) echo "inserted acknowledgement $insert";
    //else echo "not inserted acknowledgement";
    //print_r($adata);
    $update = $db->update("Tasks", $tdata, "Task_ID=".$slave->mySQLQuote($_POST['task_id']));
    $taskid=$_POST['task_id'];
    $error='<a href="tasks.php?task_id='.$taskid.'">Updated Ticket '.$taskid."</a>";
    //$creid[0]['Occurs']=0;
    if ($_POST['status']==5 and $creid[0]['Occurs']>0 and $creid[0]['Frequency']>0) {
        if ($_POST['stop_repeating']==1) {
            $creid[0]['End_Range']=date("Y-m-d");
        }
        //check if repeating and add new task
        //$ntask=$creid[0];
        foreach ($creid[0] as $key=>$val) {
            if ($val!=null) {
                $ntask[$key] = $val;
            }
        }
        /*
        $ntask['Task_Name'] = $creid[0]['Task_Name'];
        $ntask['Task_Description'] = $creid[0]['Task_Description'];
        if ($creid[0]['Owner_ID']==NULL) unset($ntask['Owner_ID']);
        $ntask['Target_Budget'] = $creid[0]['Target_Budget'];
        $ntask['Start_Date'] = $creid[0]['Start_Date'];
        $ntask['Start_Time'] = $creid[0]['Start_Time'];
        $ntask['Creation_Date'] = $creid[0]['Creation_Date'];
        $ntask['Request_Date'] = $creid[0]['Request_Date'];
        $ntask['Start_Range'] = $creid[0]['Start_Range'];
        $ntask['End_Range'] = $creid[0]['End_Range'];
        */
        //unset($ntask['Task_Description']);
        
        unset($ntask['Task_ID']);
        $ntask['Progress']=0;
        //print_r($ntask);
        //$ntask['Parent_Task_ID']=$ntask['Parent_Task_ID'];
        if ($creid[0]['Occurs']==1) {
            //daily
            if ($creid[0]['Monday']==1) {
                $newdate=calcdays($creid[0]['Frequency']);
            } else {
                $newdate=date("Y-m-d", mktime(0, 0, 0, date('m'), date('d')+$creid[0]['Frequency'], date('Y')));
            }
        } elseif ($creid[0]['Occurs']==2) {
            //weekly
            $addweek=$creid[0]['Frequency']-1;
            $weekday=date("l", mktime(0, 0, 0, date("m"), date("d")+1, date("Y")));
            if ($creid[0][$weekday]==1) {
                $newdate=date("Y-m-d", strtotime("next $weekday +$addweek week"));
            } else {
                $weekday=date("l", mktime(0, 0, 0, date("m"), date("d")+2, date("Y")));
                if ($creid[0][$weekday]==1) {
                    $newdate=date("Y-m-d", strtotime("next $weekday +$addweek week"));
                } else {
                    $weekday=date("l", mktime(0, 0, 0, date("m"), date("d")+3, date("Y")));
                    if ($creid[0][$weekday]==1) {
                        $newdate=date("Y-m-d", strtotime("next $weekday +$addweek week"));
                    } else {
                        $weekday=date("l", mktime(0, 0, 0, date("m"), date("d")+4, date("Y")));
                        if ($creid[0][$weekday]==1) {
                            $newdate=date("Y-m-d", strtotime("next $weekday +$addweek week"));
                        } else {
                            $weekday=date("l", mktime(0, 0, 0, date("m"), date("d")+5, date("Y")));
                            if ($creid[0][$weekday]==1) {
                                $newdate=date("Y-m-d", strtotime("next $weekday +$addweek week"));
                            } else {
                                $weekday=date("l", mktime(0, 0, 0, date("m"), date("d")+6, date("Y")));
                                if ($creid[0][$weekday]==1) {
                                    $newdate=date("Y-m-d", strtotime("next $weekday +$addweek week"));
                                } else {
                                    $weekday=date("l", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
                                    if ($creid[0][$weekday]==1) {
                                        $newdate=date("Y-m-d", strtotime("next $weekday +$addweek week"));
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if ($newdate and $creid[0]['Workdays']==1) {
                $newdate=calcworkday($newdate);
            }
        } elseif ($creid[0]['Occurs']==3) {
            //monthly
            if ($creid[0]['Month_Day']>0) {
                $newdate=date("Y-m-d", mktime(0, 0, 0, date('m')+$creid[0]['Frequency'], $creid[0]['Month_Day'], date('Y')));
            } else {
                if ($creid[0]['Monday']==1) {
                    $weekday="Monday";
                } elseif ($creid[0]['Tuesday']==1) {
                    $weekday="Tuesday";
                } elseif ($creid[0]['Wednesday']==1) {
                    $weekday="Wednesday";
                } elseif ($creid[0]['Thurseday']==1) {
                    $weekday="Thurseday";
                } elseif ($creid[0]['Friday']==1) {
                    $weekday="Friday";
                } elseif ($creid[0]['Saturday']==1) {
                    $weekday="Saturday";
                } elseif ($creid[0]['Sunday']==1) {
                    $weekday="Sunday";
                }
                list($month, $year)=explode(",", date("F,Y", mktime(0, 0, 0, date('m')+$creid[0]['Frequency'], 1, date('Y'))));
                $newdate=date("Y-m-d.", strtotime($creid[0]['Week']." $weekday $month $year"));
                //echo  $creid[0]['Week']." $weekday $month $year";
            }
            if ($newdate and $creid[0]['Workdays']==1) {
                $newdate=calcworkday($newdate);
            }
        } elseif ($creid[0]['Occurs']==4) {
            //yearly
            $year=$creid[0]['Finish_Date'];//date("Y");
            $year=$year+$creid[0]['Frequency'];
            
            if ($creid[0]['Monday']==1) {
                $weekday="Monday";
            } elseif ($creid[0]['Tuesday']==1) {
                $weekday="Tuesday";
            } elseif ($creid[0]['Wednesday']==1) {
                $weekday="Wednesday";
            } elseif ($creid[0]['Thurseday']==1) {
                $weekday="Thurseday";
            } elseif ($creid[0]['Friday']==1) {
                $weekday="Friday";
            } elseif ($creid[0]['Saturday']==1) {
                $weekday="Saturday";
            } elseif ($creid[0]['Sunday']==1) {
                $weekday="Sunday";
            }
            $months[1]="January";
            $months[2]="February";
            $months[3]="March";
            $months[4]="April";
            $months[5]="May";
            $months[6]="June";
            $months[7]="July";
            $months[8]="August";
            $months[9]="September";
            $months[10]="October";
            $months[11]="November";
            $months[12]="December";
            
            if ($creid[0]['Month_Day']>0) {
                $newdate=date("Y-m-d", mktime(0, 0, 0, $creid[0]['Month'], $creid[0]['Month_Day'], $year));
            } else {
                $newdate=date("Y-m-d", strtotime($creid[0]['Week']." $weekday ".$months[$creid[0]['Month']]." $year"));
            }
            if ($newdate and $creid[0]['Workdays']==1) {
                $newdate=calcworkday($newdate);
            }
        }
        if (!$creid[0]['End_Range'] or str_replace("-", "", $newdate)<=str_replace("-", "", $creid[0]['End_Range'])) {
            $ntask['Finish_Date'] = $newdate;
            $newtask=$db->insert("Tasks", $ntask);
            $error.='<br /><a href="tasks.php?task_id='.$insertid.'">Added Next Ticket ('.$insertid.") on ".display_date($newdate)."</a>";
            
            $adata['Task_ID']=$insertid;
            $adata['Accepted']=1;
            $adata['User_ID']=$_SESSION['user_id'];
            $adata['Acknowledged']=date("Y-m-d H:i:s");
            $adata['Progress']=0;
            $insert=$db->insert("Task_Acknowledgement", $adata);
        } //else $error.='<br />'.$newdate."<=".$creid[0]['End_Range'];
        //Frequency    Month_Day     Week     Month     Start_Range     End_Range     Monday     Tuesday     Wednesday     Thursday     Friday     Saturday     Sunday
    }
    if ($_FILES['uploadedfile']['name']) {
        for ($u=0;$u<count($_FILES['uploadedfile']['name']);$u++) {
            if ($_FILES['uploadedfile']['name'][$u]) {
                if (preg_match('/\.(.{3,4})$/', $_FILES['uploadedfile']['name'][$u], $match)) {
                    $ext=strtolower($match[1]);
                    if (in_array($ext, $allow)) {
                        $pid=$slave->select("SELECT Project_ID FROM Tasks WHERE Task_ID=$taskid");
                        $timg['Project_ID']=$pid[0]['Project_ID'];
                        $timg['Task_ID']=$taskid;
                        $timg['Log_ID']=$log_id;
                        $timg['File_Type_ID']=array_search($ext, $allow);
                        $timg['User_ID']=$_SESSION['user_id'];
                        $timg['Original_File'] = $_FILES['uploadedfile']['name'][$u];
                        $insert=$db->insert("Files", $timg);
                        $fileid=$insertid;
                        
                        $target_file = $target_path . $fileid.".$ext";
                        
                        if (move_uploaded_file($_FILES['uploadedfile']['tmp_name'][$u], $target_file)) {
                            $error.= "<br>The file has been uploaded</p>";
                        } else {
                            $error.= "<br>There was an error uploading the file, please try again!";
                            $delete=$db->delete("Files", "File_ID=".$fileid);
                        }
                        
                        if (in_array($ext, $img_array)) {
                            $image = new SimpleImage();
                            $image->load($target_file);
                            $image->cropTo(150);
                            $image->save($target_path. $fileid.'_sm.jpg');
                        }
                    } elseif ($_FILES['uploadedfile']['name'][$u]!="") {
                        $error.= "You must upload a ".implode(', ', $allow);
                    }
                    //echo $error;
                }
            }
        }
    }
    if ($_POST['notify']) {
        $subject="Production Change, ".$_POST['reason'];
        $message="Reason for change: ".$_POST['reason']."\n";
        $link_task=preg_replace('/^Ticket (\d*).*$/', '$1', $_POST['reason']);
        $message.="https://crm.iimgroup.com/projects/tasks.php?task_id=".$link_task."\n\n";
        
        $tz = date_default_timezone_get();
        date_default_timezone_set($_SESSION['prefs']['Time_Zone']);
        $message.="Date: ".date("d F Y")."\n\n";
        $message.="Time: ".date("H:i T")."\n\n";
        date_default_timezone_set($tz);
        $message.="Affected Component: ".$_POST['public_note']."\n\n";
        $message.="Requested By: ".$_POST['requested_user']."\n\n";
        $message.="Reboot of Server: ";
        if ($_POST['reboot']==1) {
            $message.="Yes\n\n";
        } else {
            $message.="No\n\n";
        }
        $message.="Performed By: ".$requester[0]['First_Name']." ".$requester[0]['Last_Name']."\n\n";
        echo $message;
        smtp_mail("", "", 'prodchanges@iimgroup.com', $ftemail, $requester[0]['First_Name']." ".$requester[0]['Last_Name'], $ftemail, $subject, $message, false);
    }
    //exit;
    if ($_POST['redirect']) {
        if (strstr($_POST['redirect'], "tasks.php")) {
            if (strstr($_POST['redirect'], "?")) {
                $error="&error=".urlencode($error);
            } else {
                $error="?error=".urlencode($error);
            }
            header("Location: ".$_POST['redirect'].$error);
        } else {
            header("Location: ".$_POST['redirect']);
        }
        exit;
    }
} elseif ($_POST['submit']=="Cancel") {
    header("Location: tasks.php");
    exit;
} elseif ($_POST['submit']=="Delete") {
    $taskid=$_POST['task_id'];
    $delete = $db->delete("Tasks", "Task_ID=".$slave->mySQLQuote($_POST['task_id']));
    $delete = $db->delete("Task_Logs", "Task_ID=".$slave->mySQLQuote($_POST['task_id']));
    $delete = $db->delete("Task_Acknowledgement", "Task_ID=".$slave->mySQLQuote($_POST['task_id']));
    $delete = $db->delete("Task_Dependencies", "Task_ID=".$slave->mySQLQuote($_POST['task_id'])." OR Dependency_ID=".$slave->mySQLQuote($_POST['task_id']));
    $error='<a href="tasks.php?task_id='.$taskid.'">Deleted Ticket '.$taskid."</a>";
} elseif (isset($_POST['task_id'])) {
    $data['Task_Name'] = $_POST['task_name'];
    $data['Task_Description'] = $_POST['task_description'];
    if ($_POST['parent_task_id']) {
        $data['Parent_Task_ID']=$_POST['parent_task_id'];
        $project=$slave->select("SELECT Project_ID FROM Tasks WHERE Task_ID=".$_POST['parent_task_id']);
        $data['Project_ID']=$project[0]['Project_ID'];
    } else {
        $data['Project_ID']=$_POST['project_id'];
    }
    //if ($_POST['durration']) $data['Durration']=$_POST['durration'];
    //$data['Progress']=$_POST['progress'];
    //echo $_POST['finish_date'];
    if ($_POST['finish_date']) {
        $data['Finish_Date'] = sqldate($_POST['finish_date']);
    } else {
        $data['Finish_Date']=NULL;
    }
    if ($_POST['target_bidget']) {
        $data['Target_Budget'] = $_POST['target_bidget'];
    }
    if ($_POST['status']) {
        $data['Status']=$_POST['status'];
    }
    $data['Priority']=$_POST['priority'];
    if ($_POST['high_priority']) {
        $data['High_Priority']=1;
    } else {
        $data['High_Priority']=0;
    }
    if ($_POST['request_date']) {
        $data['Request_Date'] = sqldate($_POST['request_date']);
    } else {
        $data['Request_Date']=date("Y-m-d H:i:s");
    }
    if ($_POST['request_user_id']) {
        $data['Request_User_ID'] = $_POST['request_user_id'];
    } elseif ($_POST['task_id']=="new") {
        $data['Request_User_ID'] = $_SESSION['user_id'];
    }
    $data['Affected_Department'] = $_POST['affected_department'];
    $data['Distributor_ID'] = $_POST['distributor_id'];
    $data['Company_ID'] = $_POST['company_id'];
    if ($_POST['billable']) {
        $data['Billable']=1;
    } else {
        $data['Billable']=0;
    }
    //delete users
    //if (!$_POST['newRight']) $_POST['newRight']=$_SESSION['user_id'];
    if ($_POST['newRight']!=$_POST['old_assigned']) {
        foreach (explode(",", $_POST['newRight']) as $tu) {
            unset($tdata['User_ID']);
            unset($tdata['Department_ID']);
            
            if (substr($tu, 0, 1)=="d") {
                $data['Department_ID'] = substr($tu, 1);
                $data['User_ID']=0;
                $ud=$slave->select("SELECT User_ID FROM User_Departments WHERE Department_ID=".substr($tu, 1));
                if ($ud) {
                    foreach ($ud as $use) {
                        $where.=" OR User_ID=".$use['User_ID'];
                        if ($use['User_ID']==$_SESSION['user_id']) {
                            $adata['Accepted']=1;
                        }
                    }
                    $where="(".substr($where, 4).") AND ";
                }
                if ($_POST['old_assigned']>0) {
                    $adata['Reassigned_ID']=$data['Department_ID'];
                }
            } else {
                $data['User_ID'] = $tu;
                $data['Department_ID']=0;
                $where="User_ID=".$slave->mySQLQuote($tu)." AND ";
                if ($_SESSION['user_id']==$tu) {
                    $adata['Accepted']=1;
                }
                if ($_POST['old_assigned']>0) {
                    $adata['Reassigned_ID']=$data['User_ID'];
                }
            }
        }
        $where.="Progress=".$_POST['progress']." AND ";
        $update = $db->update("Task_Acknowledgement", array("Accepted"=>0), $where."Task_ID=".$_POST['task_id']);
        $where="";
    }
    
    $data['Occurs']=0;
    $data['Frequency']=0;
    $data['Month_Day']=0;
    $data['Week']=0;
    $data['Month']=0;
    $data['Start_Range']=NULL;
    $data['End_Range']=NULL;
    $data['Monday']=0;
    $data['Tuesday']=0;
    $data['Wednesday']=0;
    $data['Thursday']=0;
    $data['Friday']=0;
    $data['Saturday']=0;
    $data['Sunday']=0;
    if ($_POST['occurs']>0) {
        $data['Occurs']=$_POST['occurs'];
        if ($_POST['frequency']) {
            $data['Frequency']=$_POST['frequency'];
        }
        if ($_POST['every']=="weekday") {
            $data['Frequency']=1;
            $data['Monday']=1;
            $data['Tuesday']=1;
            $data['Wednesday']=1;
            $data['Thursday']=1;
            $data['Friday']=1;
        } elseif ($_POST['every']=='month_day') {
            if ($_POST['month_day']) {
                $data['Month_Day']=$_POST['month_day'];
            }
            if ($_POST['month1']) {
                $data['Month']=$_POST['month1'];
            }
        } elseif ($_POST['every']=='week') {
            if ($_POST['week']) {
                $data['Week']=$_POST['week'];
            }
            if ($_POST['month2']) {
                $data['Month']=$_POST['month2'];
            }
            if ($_POST['weekday']) {
                if ($_POST['weekday']=="monday") {
                    $data['Monday']=1;
                }
                if ($_POST['weekday']=="tuesday") {
                    $data['Tuesday']=1;
                }
                if ($_POST['weekday']=="wednesday") {
                    $data['Wednesday']=1;
                }
                if ($_POST['weekday']=="thursday") {
                    $data['Thursday']=1;
                }
                if ($_POST['weekday']=="friday") {
                    $data['Friday']=1;
                }
                if ($_POST['weekday']=="saturday") {
                    $data['Saturday']=1;
                }
                if ($_POST['weekday']=="sunday") {
                    $data['Sunday']=1;
                }
            }
        } elseif ($_POST['every']!='number') {
            //weekly
            if ($_POST['monday']) {
                $data['Monday']=1;
            }
            if ($_POST['tuesday']) {
                $data['Tuesday']=1;
            }
            if ($_POST['wednesday']) {
                $data['Wednesday']=1;
            }
            if ($_POST['thursday']) {
                $data['Thursday']=1;
            }
            if ($_POST['friday']) {
                $data['Friday']=1;
            }
            if ($_POST['saturday']) {
                $data['Saturday']=1;
            }
            if ($_POST['sunday']) {
                $data['Sunday']=1;
            }
        }
        if ($_POST['start_range']) {
            $data['Start_Range'] = sqldate($_POST['start_range']);
        }
        if ($_POST['ends']==1) {
            $data['End_Range'] = sqldate($_POST['end_range']);
        }
    }
    
    if ($_POST['task_id']=="new") {
        if (!$_POST['finish_date']) {
            $task_days=$slave->select("SELECT Default_Days FROM Task_Priority WHERE Priority_ID=".$_POST['priority']);
            if ($task_days) {
                //$data['Finish_Date']=date("Y-m-d",mktime(0,0,0,date(m),date(d)+,date(y)));
            }
        }
        $data['Creator_ID']=$_SESSION['user_id'];
        //if ($_SESSION[user_id]==6) {
            
        //print_r($data);
        //exit;
        //}
        $insert = $db->insert("Tasks", $data); //|| $error=$db->error;
        $tdata['Task_ID']=$insertid;
        $ruser=$slave->select("SELECT U.User_ID, First_Name, Last_Name, Email, Cell_Phone, Alert_Email, Alert_Text, Text_Domain FROM Users as U, User_Preferences as P WHERE U.User_ID=P.User_ID AND U.User_ID=".$data['User_ID']);
        if ($data['User_ID']!=$data['Creator_ID']) {
            if ($ruser) {
                foreach ($ruser as $r) {
                    $message="";
                    if ($data['User_ID']==$r['User_ID']) {
                        $subject= "CRM ".$priorities[$_POST['priority']]['Name']." new task ".$tdata['Task_ID']." asssigned to you";
                    } else {
                        $subject="CRM ".$priorities[$_POST['priority']]['Name']." task ".$_POST['task_id']." added";
                        $message="Assigned: $assigned_to\r\n";
                    }
                    if (($_POST['priority']==1 or $r['Alert_Text']==1) and $r['Cell_Phone'] and $r['Text_Domain']) {
                        if ($message) {
                            $text_message=$message." -> ".$_POST['task_name'];
                        } else {
                            $text_message=$_POST['task_name'];
                        }
                        //mail($r['Cell_Phone'].$r['Text_Domain'], $subject, $text_message);
                        smtp_mail("", "", $r['Cell_Phone'].$r['Text_Domain'], $noreply_email, $noreply_name, $noreply_email, $subject, $text_message);
                    }
                    if (($_POST['priority']==1 or $r['Alert_Email']==1) and $r['Email']) {
                        if ($_POST['request_user_id']) {
                            $from_id=$_POST['request_user_id'];
                        } else {
                            $from_id=$_SESSION['user_id'];
                        }
                        $requester=$slave->select("SELECT Email, First_Name, Last_Name FROM Users WHERE User_ID=".$from_id);
                        $message.= "FROM: ".display_name($requester[0]['First_Name'], $requester[0]['Last_Name'])."\r\n<br />";
                        $message.= "Name: ".$_POST['task_name']."\r\n<br />";
                        $message.= $_POST['task_description'];
                        if (valid_email($requester[0]['Email'])) {
                            $ftemail=$requester[0]['Email'];
                        } else {
                            $ftemail=$noreply_email;
                        }
                        smtp_mail("", "", $r['Email'], $ftemail, display_name($requester[0]['First_Name'], $requester[0]['Last_Name']), $ftemail, $subject, $message);
                    }
                }
            }
        }
        $adata['Accepted']=1;
        $error='<a href="tasks.php?task_id='.$tdata['Task_ID'].'">ADDED Ticket '.$tdata['Task_ID']."</a>";
    } else {
        $update = $db->update("Tasks", $data, "Task_ID=".$_POST['task_id']);
        update_child($data['Project_ID'], $_POST['task_id']);
        $tdata['Task_ID']=$_POST['task_id'];
        $error='<a href="tasks.php?task_id='.$tdata['Task_ID'].'">UPDATED Ticket '.$tdata['Task_ID']."</a>";
        
        //remove task_acknowledge
        $insert=$db->delete("Task_Acknowledgement", "Task_ID=".$_POST['task_id']);
    }
    
    //task_acknowlegement
    //$delete=$db->delete("Task_Acknowledgement","Progress=0 AND Task_ID=".$tdata['Task_ID']);
    if ($adata) {
        $adata['User_ID']=$_SESSION['user_id'];
        $adata['Acknowledged']=date("Y-m-d H:i:s");
        $adata['Task_ID']=$tdata['Task_ID'];
        $adata['Progress']=$_POST['progress'];
        $insert=$db->insert("Task_Acknowledgement", $adata);
    }
    if ($_FILES['uploadedfile']['name']) {
        for ($u=0;$u<count($_FILES['uploadedfile']['name']);$u++) {
            if ($_FILES['uploadedfile']['name'][$u]) {
                /*if ($handle = opendir($target_path)) {
                 while (false !== ($file = readdir($handle))) {
                  if (preg_match('/^'.$tdata['Task_ID'].'/',$file)) {
                unlink($target_path.$file);
                  }
                 }
                 closedir($handle);
                }*/
                if (preg_match('/\.(.{3,4})$/', $_FILES['uploadedfile']['name'][$u], $match)) {
                    $taskid=$tdata['Task_ID'];
                    $ext=strtolower($match[1]);
                    if (in_array($ext, $allow)) {
                        $pid=$slave->select("SELECT Project_ID FROM Tasks WHERE Task_ID=$taskid");
                        $timg['Project_ID']=$pid[0]['Project_ID'];
                        $timg['Task_ID']=$taskid;
                        $timg['Log_ID']=0;
                        $timg['File_Type_ID']=array_search($ext, $allow);
                        $timg['User_ID']=$_SESSION['user_id'];
                        $timg['Original_File'] = $_FILES['uploadedfile']['name'][$u];
                        $insert=$db->insert("Files", $timg);
                        $fileid=$insertid;
                        
                        $target_file = $target_path . $fileid.".$ext";
                        
                        if (move_uploaded_file($_FILES['uploadedfile']['tmp_name'][$u], $target_file)) {
                            $error.= "<br>The file has been uploaded</p>";
                        } else {
                            $error.= "<br>There was an error uploading the file, please try again!";
                            $delete=$db->delete("Files", "File_ID=".$fileid);
                        }
                        
                        if (in_array($ext, $img_array)) {
                            $image = new SimpleImage();
                            $image->load($target_file);
                            $image->cropTo(150);
                            $image->save($target_path. $fileid.'_sm.jpg');
                        }
                    } elseif ($_FILES['uploadedfile']['name'][$u]!="") {
                        $error.= "You must upload a ".implode(', ', $allow);
                    }
                    //echo $error;
                }
            }
        }
    }
    if ($_POST['redirect']) {
        if (strstr($_POST['redirect'], "tasks.php")) {
            if (strstr($_POST['redirect'], "?")) {
                $error="&error=".urlencode($error);
            } else {
                $error="?error=".urlencode($error);
            }
            header("Location: ".$_POST['redirect'].$error);
        } else {
            header("Location: ".$_POST['redirect']);
        }
        exit;
    }
}

$title="Task Management";
$editor=true;
if ($_GET['work']) {
    $editor=false;
}
if (!$_GET['sid']) {
    show_head($title, false, $extra, $editor);
}
if ($_GET['error']) {
    $error=$_GET['error'];
}
//echo "<h1 class=\"error\">Alena is working on this, don't mind the display</h1>";
if ($error) {
    echo "<p>".str_replace('\"', '', $error)."</p>";
}
if ($_GET['work']) {
    $prog=$slave->select("SELECT Progress,Task_Name,IF (T.User_ID>0, T.User_ID, Department_ID) as Old_Assigned, Request_User_ID, First_Name, Last_Name FROM Tasks as T, Users as U WHERE T.Request_User_ID=U.User_ID AND Task_ID=".$_GET['work']);
    $old_assigned=$prog[0]['Old_Assigned'];
    echo "<h1>Log Work for $task_id, ".$prog[0]['Task_Name']."</h1>";
    ?>
<script language="javascript" type="text/javascript">
<!--
function isReady(form) {
    form.submit.disabled=true;
    return true;
}
function addElement() {
  var ni = document.getElementById('myDiv');
  var numi = document.getElementById('theValue');
  var num = (document.getElementById('theValue').value -1)+ 2;
  numi.value = num;
  var newdiv = document.createElement('div');
  var divIdName = 'my'+num+'Div';
  newdiv.setAttribute('id',divIdName);
  newdiv.innerHTML = '<input name="uploadedfile[]" type="file" />';
  ni.appendChild(newdiv);
}
//-->
</script>
<form name="work" id="work" method="post" action="tasks.php"  enctype="multipart/form-data" onsubmit="return isReady(this)">
<table>
<tr>
    <td class="label">Date</td>
    <td><input type="text" name="log_date" value="<?php echo display_date(date('Y-m-d'));
    ?>" onfocus="this.select();lcs(this)" onclick="event.cancelBubble=true;this.select();lcs(this)" /></td>
</tr>
<tr>
    <td class="label">Public Note</td>
    <td><textarea name="public_note" style="width:400px;height:200px;"></textarea></td>
</tr>
<tr>
    <td class="label"><i><strong>Private</strong> Note</i></td>
    <td><textarea name="log_note" style="width:400px;height:200px;"></textarea></td>
</tr>
<tr>
    <td class="label">Attach File <input type="hidden" value="0" id="theValue" />
<a href="javascript:;" onclick="addElement();"><img src="<?php echo CDN;
    ?>img/expand.png" alt="add more" border=0 /></a></td>
    <td><input name="uploadedfile[]" type="file" />
    <div id="myDiv"></div></td>
</tr>
<tr>
    <td class="label">Time Spent</td>
    <td><select name="log_time_hours">
    <option value="0">0</option>
    <option value="1">1</option>
    <option value="2">2</option>
    <option value="3">3</option>
    <option value="4">4</option>
    <option value="5">5</option>
    <option value="6">6</option>
    <option value="7">7</option>
    <option value="8">8</option>
    <option value="9">9</option>
    <option value="10">10</option>
    </select>hours <select name="log_time_minutes">
    <option value="0">0</option>
    <option value="15" SELECTED>15</option>
    <option value="30">30</option>
    <option value="45">45</option>
    </select>minutes </td>
</tr>
<tr>
    <td class="label">Reassign To</td>
    <td><input type="hidden" name="old_assigned" value="<?php echo $old_assigned;
    ?>" />
    <select name="newRight">
    <option value="">NONE</option>
    <OPTGROUP LABEL="Departments">
<?php
$departments=$slave->select("SELECT Department_ID, Department_Name FROM Departments ORDER BY Department_Name");
//view dropdown of existing departments
for ($d=0;$d<count($departments);$d++) {
    echo "<option value=\"d".$departments[$d]['Department_ID']."\"";
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
        echo ">".display_name($users[$u]['First_Name'], $users[$u]['Last_Name'])."</option>\n";
    }
}
    ?>
    </OPTGROUP>
  </select></td>
</tr>
<tr>
    <td class="label">Status</td>
    <td><select name="status">
    <?php if ($status==3 or $status==5) {
    echo "<option value=\"3\">Reopened</option>\n";
} else {
    echo "<option value=\"2\">Open</option>\n";
}
    $open=$slave->select("SELECT * FROM Tasks WHERE Progress<100 AND Parent_Task_ID=$task_id");
    if (!$open) {
        echo "<option value=\"5\">Closed</option>\n";
    }
    echo "<option value=\"4\">On Hold</option>";
    ?>
        </select>
        <?php
        if ($open) {
            echo "<small class=\"error\">cannot complete until children are complete</small>";
        }
        //else echo '<input type="hidden" name="log_progress" value="'.$prog[0]['Progress'].'" /><input id="complete" type="checkbox" onclick="document.work.log_progress.value=100"><small>Close ticket</small>';
    ?></td>
</tr>
<?php if ($end_range>date("Y-m-d")) {
    ?>
<tr>
    <td class="label">Stop Repeating</td>
    <td><input type="checkbox" name="stop_repeating" value="1" /></td>
</tr>
<?php

}
    ?>
<tr>
    <td class="label">Notification Email</td>
    <td><input type="checkbox" name="notify" value=1 onChange="showID('notify_options')" /> <small>For Production Change</small>
    <?php
    if ($parent_task_id>0) {
        get_parent($parent_task_id, false);
    }
    //echo $parent_task_id;
    //print_r($parents);
    ?>
    <div id="notify_options" style="display:none">
    <table>
        <tr>
            <td>Reason for change:</td>
            <td><select name="reason">
    <?php echo "<option value=\"Ticket $task_id, ".$prog[0]['Task_Name']."\">$task_id: ".$prog[0]['Task_Name']."</option>\n";
    if ($parents) {
        foreach ($parents as $p) {
            echo "<option value=\"Ticket ".$p['Task_ID'].", ".$p['Task_Name']."\">".$p['Task_ID'].": ".$p['Task_Name']."</option>\n";
        }
    }
    ?>
            </select></td>
        </tr>
        <tr>
            <td>Requested By:</td>
            <td><select name="requested_user">
    <?php echo "<option value=\"".$prog[0]['First_Name']." ".$prog[0]['Last_Name']."\">".display_name($prog[0]['First_Name'], $prog[0]['Last_Name'])."</option>\n";
    if ($parents) {
        foreach ($parents as $p) {
            echo "<option value=\"".$p['First_Name']." ".$p['Last_Name']."\"";
            if ($p['Parent_Number_ID']==0) {
                echo " SELECTED=\"SELECTED\"";
            }
            echo ">".display_name($p['First_Name'], $p['Last_Name'])."</option>\n";
        }
    }
    ?>
            </select></td>
        </tr>
        <tr>
            <td>Reboot of Server:</td>
            <td><input type="checkbox" name="reboot" value=1 /></td>
        </tr>
    </table></div></td>
</tr>
<tr><td>&nbsp;</td><td><input type="hidden" name="redirect" value="<?php echo($_SESSION['prefs']['Task_Redirect'])?$_SESSION['prefs']['Task_Redirect']:$_SERVER['HTTP_REFERER'];
    ?>" />
<input type="submit" name="submit" value="Update Task" /></td></tr>
</table>
<input type="hidden" name="task_id" value="<?php echo $_GET['work'];
    ?>" />
</form>
<?php

} elseif (isset($task_id)) {
    echo "<h1>";
    if ($task_id=="new") {
        if ($_GET['project_id']==0) {
            $do_action="Add Ticket";
        } else {
            $do_action="Add Task";
        }
        echo $do_action;
        $project_id=$_SESSION['project_id'];
    } else {
        if ($_GET['action']=="edit") {
            $do_action="Modify Task";
        } else {
            $do_action="View Task";
        }
        echo $do_action;
    }
    echo " $task_id</h1>";
    if ($_GET['task_id']=="new" or $_GET['action']=="edit") {
        ?>
   <script language="javascript" type="text/javascript">
   <!--
   function isReady(form) {
   var occval = 0;

   for ( i = 0; i < form.occurs.length; i++ )
   {
   if ( form.occurs[i].checked == true )
   occval = form.occurs[i].value;
   }
    if (occval>0) {
     if (form.finish_date.value.replace(/^(\d\d)\/(\d\d)\/(\d\d\d\d)$/,"$3$2$1")<form.start_range.value.replace(/^(\d\d)\/(\d\d)\/(\d\d\d\d)$/,"$3$2$1")) {
      alert("Your start range cannot be after your due date");
      form.start_range.focus();
     return false;
     }
    }
    if (form.task_name.value=="") {
     alert("You must enter a Subject");
     form.task_name.focus();
     return false;
    }
    if (form.newRight.value=="") {
     alert("You must select someone to assign this task to");
     form.newRight.focus();
     return false;
    }
    form.submit.disabled=true;
    return true;
   }
   function addElement() {
     var ni = document.getElementById('myDiv');
     var numi = document.getElementById('theValue');
     var num = (document.getElementById('theValue').value -1)+ 2;
     numi.value = num;
     var newdiv = document.createElement('div');
     var divIdName = 'my'+num+'Div';
     newdiv.setAttribute('id',divIdName);
     newdiv.innerHTML = '<input name="uploadedfile[]" type="file" />';
     ni.appendChild(newdiv);
   }
   //-->
   </script>
   <form name="tasks" id="tasks" method="post" action="tasks.php"  enctype="multipart/form-data" onSubmit="return isReady(this)">
   <table width="100%">
    <?php if (true) {
    ?>
<tr>
    <td class="label">Project (optional)</td>
    <td><select name="project_id">
    <option value="0" selected>Non Project Ticket</option>
<?php
//view dropdown of existing departments
$projects = $slave->select("SELECT Project_ID, Project_Name FROM Projects ORDER BY Project_Name");
    if ($projects) {
        for ($y=0; $y<count($projects); $y++) {
            echo "<option value=\"".$projects[$y]['Project_ID']."\"";
            if ($projects[$y]['Project_ID']==$project_id) {
                echo " selected";
            }
            echo ">".$projects[$y]['Project_Name']."</option>\n";
        }
    }
    ?>
  </select></td>
</tr>
<?php

}
        ?>
<tr>
    <td class="label">Subject</td>
    <td><input name="task_name" type="text" value="<?php echo htmlspecialchars($task_name);
        ?>" style="width:550px;" /></td>
</tr>
<tr>
    <td class="label">Description</td>
    <td><textarea name="task_description"><?php echo $task_description;
        ?></textarea>
<?php
if ($task_id!="new") {
    $files=$slave->select("SELECT * FROM Files as F, File_Types as T WHERE F.File_Type_ID=T.File_Type_ID AND Log_ID=0 AND Task_ID=".$task_id);
    if ($files) {
        foreach ($files as $f) {
            echo "<br /><a href=\"${target_path}".$f['File_ID'].".".$f['Extention']."\" target=\"_blank\">";
            if ($f['Image']==1) {
                echo "<img src=\"${target_path}".$f['File_ID']."_sm.jpg\" border=0 /><br />";
            }
            echo "${target_path}".$f['File_ID'].".".$f['Extention']."</a>";
        }
    }
}
        ?></td>
   </tr>
   <tr>
    <td class="label">Attach File <input type="hidden" value="0" id="theValue" /><a href="javascript:;" onclick="addElement();"><img src="<?php echo CDN;
        ?>img/expand.png" alt="add more" border=0 /></a></td>
    <td><input name="uploadedfile[]" type="file" />
    <div id="myDiv"> </div></td>
   </tr>
   <tr>
    <td class="label">Severity</td>
    <td><select name="priority" id="priority" onchange="checkexists('/projects/priority.php','id='+this.value,'priority_description')">
    <option value="" selected>Select One</option>
    <?php
    //priority
    foreach ($priorities as $key=>$val) {
        echo "<option value=\"".$key."\"";
        if ($key==$priority) {
            echo " selected";
        }
        echo ">$key - ".$val['Name']."</option>\n";
    }
        ?>
     </select>
     <div id="dek"><table><?php echo $help;
        ?></table></div>
  <script type="text/javascript">
<!--

//Pop up information box II (Mike McGrath (mike_mcgrath@lineone.net,  http://website.lineone.net/~mike_mcgrath))
//Permission granted to Dynamicdrive.com to include script in archive
//For this and 100's more DHTML scripts, visit http://dynamicdrive.com

Xoffset=10;    // modify these values to ...
Yoffset=-250;    // change the popup position.

var old,skn,iex=(document.all),yyy=-1000;

var ns4=document.layers
var ns6=document.getElementById&&!document.all
var ie4=document.all

if (ns4)
skn=document.dek
else if (ns6)
skn=document.getElementById("dek").style
else if (ie4)
skn=document.all.dek.style
if (ns4)document.captureEvents(Event.MOUSEMOVE);
else {
skn.visibility="visible"
skn.display="none"
}
document.onmousemove=get_mouse;




//-->
</script>
  <a href="javascript:;" onmouseover="popup('Website Abstraction, the definitive JavaScript site on the net.','lightgreen')" onmouseout="kill()"><img src="<?php echo CDN;
        ?>img/help.png" alt="HELP! Tell me more" border="0" width="16" height="16" align="top" /></a>
  <div id="priority_description"><?php echo $priority_description;
        ?></div></td>
</tr>
<tr>
    <td class="label">Due Date</td>
    <td><input type="text" name="finish_date" value="<?php if ($finish_date!='Not Set') {
    echo $finish_date;
}
        ?>" onfocus="this.select();lcs(this)" onclick="event.cancelBubble=true;this.select();lcs(this)"/> <input type="checkbox" value="1" name="high_priority" <?php echo $high_priority;
        ?> /><small>high priority</small></td>
</tr>
<?php if (($task_id=="new" and !isset($_GET['project_id'])) or ($task_id!="new" and ($project_id!=0 or $parent_task_id!=0))) {
    ?>
<tr>
    <td class="label">Target Budget</td>
    <td>$<input type="text" name="target_budget" value="<?php echo $target_budget;
    ?>" size=7 /></td>
</tr>
<?php

}
        ?>
<tr>
    <td class="label">Request Date</td>
    <td><input type="text" name="request_date" value="<?php if ($request_date) {
    echo $request_date;
} else {
    echo display_date(date('Y-m-d'));
}
        ?>" onfocus="this.select();lcs(this)" onclick="event.cancelBubble=true;this.select();lcs(this)" /></td>
</tr>
<tr>
    <td class="label" nowrap>Requested By</td>
    <td><select name="request_user_id">
    <option value="">NONE</option>
<?php
//view dropdown of existing departments
if (!$request_user_id) {
    $request_user_id=$_SESSION['user_id'];
}
        for ($u=0;$u<count($users);$u++) {
            if ($users[$u]['User_Status_ID']==1) {
                echo "<option value=\"".$users[$u]['User_ID']."\"";
                if ($users[$u]['User_ID']==$request_user_id) {
                    echo " selected";
                }
                echo ">".display_name($users[$u]['First_Name'], $users[$u]['Last_Name'])."</option>\n";
            }
        }
        ?>
  </select></td>
</tr>
<tr>
    <td class="label">Assigned To</td>
    <td><input type="hidden" name="old_assigned" value="<?php echo $old_assigned;
        ?>" />
    <select name="newRight">
    <option value="">Please Select</option>
<?php
$departments=$slave->select("SELECT Department_ID, Department_Name FROM Departments ORDER BY Department_Name");
//view dropdown of existing departments
for ($d=0;$d<count($departments);$d++) {
    echo "<option value=\"d".$departments[$d]['Department_ID']."\"";
    if ($_GET['task_id']=="new" and $departments[$d]['Department_ID']==7) {
        if ($slave->select("SELECT User_ID FROM User_Departments WHERE Department_ID=".$departments[$d]['Department_ID']." AND User_ID=".$_SESSION['user_id'])) {
            echo " selected";
        }
    } elseif ($departments[$d]['Department_ID']==$selected_department) {
        echo " selected";
    }
    echo ">DEPT: ".$departments[$d]['Department_Name']."</option>\n";
}
//view dropdown of existing departments
for ($u=0;$u<count($users);$u++) {
    if ($users[$u]['User_Status_ID']==1) {
        echo "<option value=\"".$users[$u]['User_ID']."\"";
        if ($users[$u]['User_ID']==$selected_user) {
            echo " selected";
        }
        echo ">".display_name($users[$u]['First_Name'], $users[$u]['Last_Name'])."</option>\n";
    }
}
        ?>
  </select></td>
</tr>
<tr>
    <td class="label">Affected Department</td>
    <td><select name="affected_department">
<?php
//view dropdown of existing departments
for ($d=0;$d<count($departments);$d++) {
    echo "<option value=\"".$departments[$d]['Department_ID']."\"";
    if ($_GET['task_id']=="new" or $affected_department<1) {
        if ($_SESSION['prefs']['Department_ID']==$departments[$d]['Department_ID']) {
            echo " selected";
        }
    } elseif ($departments[$d]['Department_ID']==$affected_department) {
        echo " selected";
    }
    echo ">".$departments[$d]['Department_Name']."</option>\n";
}
        ?>
  </select></td>
</tr>
<tr>
    <td class="label">Distributor</td>
    <td><select name="distributor_id">
<?php
//view dropdown of existing distributors
$distributors=$slave->select("SELECT Company_ID, Company_Name FROM Companies WHERE Contact_Type_ID=6 ORDER BY Company_Name");
        for ($d=0;$d<count($distributors);$d++) {
            echo "<option value=\"".$distributors[$d]['Company_ID']."\"";
            if ($_GET['task_id']=="new" or $distributor_id<1) {
                $ud=$slave->select("SELECT Distributor_ID FROM Users WHERE User_ID=".$_SESSION['user_id']);
                if ($distributors[$d]['Company_ID']==$ud[0]['Distributor_ID']) {
                    //
            echo " selected";
                }
            } elseif ($distributors[$d]['Company_ID']==$distributor_id) {
                echo " selected";
            }
            echo ">".$distributors[$d]['Company_Name']."</option>\n";
        }
        ?>
  </select></td>
</tr>
<tr>
    <td class="label">Client</td>
    <td><select name="company_id">
    <option value="0">Internal</option>
<?php
//view dropdown of existing clients
$clients=$slave->select("SELECT Company_ID, Company_Name FROM Companies WHERE Contact_Type_ID!=6 ORDER BY Company_Name");
        for ($d=0;$d<count($clients);$d++) {
            echo "<option value=\"".$clients[$d]['Company_ID']."\"";
            if ($clients[$d]['Company_ID']==$company_id) {
                echo " selected";
            }
            echo ">".$clients[$d]['Company_Name']."</option>\n";
        }
        ?>
  </select></td>
</tr>
<tr>
    <td class="label">Billable</td>
    <td><input type="checkbox" value="1" name="billable" <?php echo $billable;
        ?> /></td>
</tr>
<tr><td class="label">Recurrence</td>
    <td><fieldset><legend>Occurs</legend>
    <table><tr>
        <td>
        <input type="radio" name="occurs" value="0" <?php if ($occurs==0) {
    echo "checked";
}
        ?> onClick="showTab('recurrence.php','occurs=0','recurrence_details')" /> Once<br />
        <input type="radio" name="occurs" value="1" <?php if ($occurs==1) {
    echo "checked";
}
        ?> onClick="showTab('recurrence.php','occurs=1','recurrence_details')" /> Daily<br />
        <input type="radio" name="occurs" value="2" <?php if ($occurs==2) {
    echo "checked";
}
        ?> onClick="showTab('recurrence.php','occurs=2','recurrence_details')" /> Weekly<br />
        <input type="radio" name="occurs" value="3" <?php if ($occurs==3) {
    echo "checked";
}
        ?> onClick="showTab('recurrence.php','occurs=3','recurrence_details')" /> Monthly<br />
        <input type="radio" name="occurs" value="4" <?php if ($occurs==4) {
    echo "checked";
}
        ?> onClick="showTab('recurrence.php','occurs=4','recurrence_details')" /> Yearly
        </td>
        <td>
        <div id="recurrence_details"><?php $_GET[occurs]=$occurs;
        include"recurrence.php";
        ?>
        </div>
        </td>
    </tr>
    </table>
    </fieldset>
    </td>
</tr>
</table>
</td></tr>
<tr><td>&nbsp;</td><td><input type="hidden" name="progress" value="<?php echo $progress;
        ?>" />
<?php if ($_GET['parent_task_id']) {
    echo '<input type="hidden" name="parent_task_id" value="'.$_GET['parent_task_id'].'" />';
}
        ?>
<input type="hidden" name="redirect" value="<?php echo($_SESSION['prefs']['Task_Redirect'])?$_SESSION['prefs']['Task_Redirect']:$_SERVER['HTTP_REFERER'];
        ?>" />
<input type="submit" name="submit" value="<?php echo $do_action;
        ?>" />
<input type="button" name="cancel" value="Cancel" onclick="history.go(-1)" />
</td></tr>
</table>

<input type="hidden" name="task_id" value="<?php echo $task_id;
        ?>" />
</form>
<?php
//<input type="submit" name="submit" value="Delete" onclick="if (confirm('Are you sure you want to delete?')) return true; else return false;" />
    } else {
        $args=array("project_id","user_id","action","work","parent_task_id");
        echo "<div id=\"group\"><ul id=\"tabs\">\n";
        echo "<li><a href=\"".clean_url($args)."action=edit\">Edit Task</a></li>\n";
        echo "<li><a href=\"".clean_url($args)."work=$task_id\">Add Work Log</a></li>";
        echo "<li><a href=\"tasks.php?task_id=new&parent_task_id=$task_id\">Add Subtask</a></li>";
        echo "</ul>\n";
        ?>
   <table><tr><td>
   <table>
   <tr><th colspan=2 align="left">Details</th></tr>
   <tr>
    <td class="label" align="right">Project</td>
    <td class="field" nowrap><?php
    //view dropdown of existing departments
     $projects = $slave->select("SELECT Project_ID, Project_Name FROM Projects WHERE Project_ID=".$project_id);
        if ($projects) {
            echo $projects[0]['Project_Name'];
        } else {
            echo "Non Project Ticket";
        }
        ?></td>
   </tr>
   <tr>
    <td class="label" align="right">Task Parent</td>
    <td class="field" nowrap>
    <?php echo "<form action=\"tasks.php?task_id=".$_GET['task_id']."\" method=\"post\">\n";
        get_parent($parent_task_id);
        if ($parent_task_id>0) {
            echo "<input type=\"hidden\" id=\"parent_task_id\" name=\"parent_task_id\" value=\"delete\" />\n";
            echo " <INPUT TYPE=\"image\" SRC=\"".CDN."img/icons/x.png\" HEIGHT=\"12\" WIDTH=\"12\" BORDER=\"0\" ALT=\"Submit Form\">";
        } else {
            echo "<input size=4 type=\"text\" id=\"parent_task_id\" name=\"parent_task_id\" value=\"\" />\n";
            echo "<input type=\"submit\" value=\"add\">\n";
        }
        echo "</form>\n";
        ?>
    </td>
   </tr>
   <tr>
    <td class="label" align="right">Subject</td>
    <td class="field" nowrap><?php echo $task_name;
        ?></td>
   </tr>
   <tr>
    <td class="label" align="right">Severity</td>
    <td class="field"><?php echo "$priority - ".$priorities[$priority]['Name'];
        ?></td>
   </tr>
   <tr>
    <td class="label" align="right">Progress</td>
    <td class="field"><?php echo $progress;
        ?>%</td>
   </tr>
   <tr><th colspan=2 align="left">Dates and Targets</th></tr>
   <tr>
    <td class="label" align="right">Entered Date</td>
    <td class="field"><?php echo display_date($creation_date)." @ ".display_time($creation_date);
        ?></td>
   </tr>
   <tr>
    <td class="label" align="right">Entered User</td>
    <td class="field">
    <?php
    //view entered user
    for ($u=0;$u<count($users);$u++) {
        if ($users[$u]['User_ID']==$creator_id) {
            echo display_name($users[$u]['First_Name'], $users[$u]['Last_Name'])." \n";
        }
    }
        ?></td>
   </tr>
   <tr>
    <td class="label" align="right">Request Date</td>
    <td class="field"><?php echo $request_date;
        ?></td>
</tr>
<tr>
    <td class="label" align="right">Due Date</td>
    <td class="field"><?php echo $finish_date;
        ?></td>
</tr>
<tr>
    <td class="label" align="right">Target Budget</td>
    <td class="field">$<?php echo $target_budget;
        ?></td>
</tr>
<tr>
    <td class="label" nowrap>Requested By</td>
    <td class="field">
<?php
//view requested user
for ($u=0;$u<count($users);$u++) {
    if ($users[$u]['User_ID']==$request_user_id) {
        echo display_name($users[$u]['First_Name'], $users[$u]['Last_Name'])." \n";
    }
}
        ?></td>
</tr>
<tr>
    <td class="label">Status</td>
    <td class="field">
<?php
if ($status==5) {
    //view dropdown of existing departments
    $clog=$slave->select("SELECT User_ID FROM Task_Logs WHERE Log_Progress=100 AND Task_ID=$task_id ORDER BY Log_ID DESC");
    if ($clog) {
        for ($u=0;$u<count($users);$u++) {
            if ($users[$u]['User_ID']==$clog[0]['User_ID']) {
                echo "Closed By: ".display_name($users[$u]['First_Name'], $users[$u]['Last_Name'])."\n";
            }
        }
    } else {
        echo $task_status[$status];
    }
} else {
    echo $task_status[$status];
}
        ?></td>
</tr>
</table>
</td><td><table>
<tr><th colspan=2 align="left">Affected Department</th></tr>
<tr>
    <td colspan=2 class="field">
<?php
$departments=$slave->select("SELECT Department_ID, Department_Name FROM Departments WHERE Department_ID=$affected_department");
        echo $departments[0]['Department_Name'];
        ?>
  </td>
</tr>
<tr><th colspan=2 align="left">Distributor / Client</th></tr>
<tr>
    <td colspan=2 class="field">
<?php
if ($billable) {
    echo "<img src=\"".CDN."img/icons/dollar.png\" />\n";
}
        if ($company_id) {
            $cwhere=" OR Company_ID=$company_id";
        }
        $companies=$slave->select("SELECT Company_ID, Company_Name FROM Companies WHERE Company_ID=$distributor_id $cwhere");
        if ($companies) {
            foreach ($companies as $c) {
                if ($c['Company_ID']==$distributor_id) {
                    echo $c['Company_Name'];
                }
            }
            echo " / ";
            $tcomp="Internal";
            foreach ($companies as $c) {
                if ($c['Company_ID']==$company_id) {
                    $tcomp=$c['Company_Name'];
                }
            }
            echo $tcomp;
        } else {
            echo "NOT ASSIGNED";
        }
        ?>
  </td>
</tr>
<tr><th colspan=2 align="left">Assigned User/Department</th></tr>
<tr>
    <td colspan=2 class="field">
<?php
$departments=$slave->select("SELECT Department_ID, Department_Name FROM Departments");
//view dropdown of existing departments
for ($d=0;$d<count($departments);$d++) {
    if ($departments[$d]['Department_ID']==$selected_department) {
        echo "DEPT: ".$departments[$d]['Department_Name']." \n";
    }
}
//view dropdown of existing departments
for ($u=0;$u<count($users);$u++) {
    if ($users[$u]['User_ID']==$selected_user) {
        echo display_name($users[$u]['First_Name'], $users[$u]['Last_Name'])." \n";
    }
}
        ?>
  </td>
</tr>
<tr><th colspan=2 align="left">Dependencies</th></tr>
<tr><th colspan=2 align="left">Tasks depending on this Task</th></tr>
<tr><th colspan=2 align="left">Description</th></tr>
<tr>
    <td colpsan=2><div><?php echo $task_description;
        ?></div>
    </td>
</tr>
<?php if ($occurs>0) {
    $occur[0]="Once";
    $occur[1]="Day(s)";
    $occur[2]="Week(s)";
    $occur[3]="Month(s)";
    $occur[4]="Year(s)";

    echo "<tr><th colspan=2 align=\"left\">Occurs Every ";
    if ($occurs!=1 or $monday==0) {
        echo "$frequency ".$occur[$occurs];
    }
    if ($occurs==1) {
        if ($monday==1) {
            echo " Weekday";
        }
    } elseif ($occurs==2) {
        $weekdays=" on ";
        if ($monday==1) {
            $weekdays.="Monday, ";
        }
        if ($tuesday==1) {
            $weekdays.="Tuesday, ";
        }
        if ($wednesday==1) {
            $weekdays.="Wednesday, ";
        }
        if ($thursday==1) {
            $weekdays.="Thursday, ";
        }
        if ($friday==1) {
            $weekdays.="Friday, ";
        }
        if ($saturday==1) {
            $weekdays.="Saturday, ";
        }
        if ($sunday==1) {
            $weekdays.="Sunday, ";
        }
        $weekdays=substr($weekdays, 0, -2);
        echo $weekdays;
    } elseif ($occurs==3) {
        echo " on the ";
        if ($month_day>0) {
            if ($month_day==1) {
                echo "1st";
            } elseif ($month_day==2) {
                echo "2nd";
            } elseif ($month_day==3) {
                echo "3rd";
            } else {
                echo $month_day."th";
            }
        } else {
            if ($week==1) {
                echo "First ";
            } elseif ($week==2) {
                echo "Second ";
            } elseif ($week==3) {
                echo "Third ";
            } elseif ($week==4) {
                echo "Fourth ";
            } elseif ($week==5) {
                echo "Fifth ";
            }
            if ($monday==1) {
                echo "Monday";
            }
            if ($tuesday==1) {
                echo "Tuesday";
            }
            if ($wednesday==1) {
                echo "Wednesday";
            }
            if ($thursday==1) {
                echo "Thursday";
            }
            if ($friday==1) {
                echo "Friday";
            }
            if ($saturday==1) {
                echo "Saturday";
            }
            if ($sunday==1) {
                echo "Sunday";
            }
        }
    } elseif ($occurs==4) {
        echo " on the ";
        if ($week>0) {
            if ($week==1) {
                echo "First ";
            } elseif ($week==2) {
                echo "Second ";
            } elseif ($week==3) {
                echo "Third ";
            } elseif ($week==4) {
                echo "Fourth ";
            } elseif ($week==5) {
                echo "Fifth ";
            }
            if ($monday==1) {
                echo "Monday";
            }
            if ($tuesday==1) {
                echo "Tuesday";
            }
            if ($wednesday==1) {
                echo "Wednesday";
            }
            if ($thursday==1) {
                echo "Thursday";
            }
            if ($friday==1) {
                echo "Friday";
            }
            if ($saturday==1) {
                echo "Saturday";
            }
            if ($sunday==1) {
                echo "Sunday";
            }
        } else {
            if ($month_day==1) {
                echo "1st";
            } elseif ($month_day==2) {
                echo "2nd";
            } elseif ($month_day==3) {
                echo "3rd";
            } else {
                echo $month_day."th";
            }
        }
        echo " of ".date("F", mktime(0, 0, 0, $month, 1, '2010'));
    }
    echo " from ".display_date($start_range);
    if ($end_range) {
        echo " to ".display_date($end_range);
    }
    echo "</th></tr>\n";
}
        ?>
   </table></td>
    <?php
    $files=$slave->select("SELECT * FROM Files as F, File_Types as T WHERE F.File_Type_ID=T.File_Type_ID AND Log_ID=0 AND Task_ID=".$task_id);
        if ($files) {
            echo "<td>\n<table><tr>";
            $count=0;
            foreach ($files as $f) {
                if ($count>0 and $count%3==0) {
                    echo "</tr>\n<tr>";
                }
                echo "<td><a href=\"${target_path}".$f['File_ID'].".".$f['Extention']."\" target=\"_blank\">";
                if ($f['Image']==1) {
                    echo "<img src=\"${target_path}".$f['File_ID']."_sm.jpg\" border=0 />";
                } else {
                    echo "<img src=\"".CDN."img/icons/3.png\" border=0 />";
                }
            //echo "<br />${target_path}".$f['File_ID'].".".$f['Extention'];
            if ($f['Original_File']) {
                echo "<em>Original File: ".$f['Original_File'];
                echo "<br />Renamed: ".$f['File_ID'].".".$f['Extention']."</em>\n";
            } else {
                echo "no original file name";
            }
                echo "</a></td>\n";
                $count++;
            }
            if ($count>3) {
                while ($count%3!=0) {
                    echo "<td>&nbsp;</td>";
                    $count++;
                }
            }
            echo "</tr></table>\n</td>";
        }
        ?>
    </tr></table></div>
    <?php
    $children=$slave->select("SELECT * FROM Tasks WHERE Parent_Task_ID=".$task_id);

        echo "<div id=\"group\"><ul id=\"tabs\">\n";
        echo "<li";
        if ($_GET['action']=="log") {
            echo " class=\"active\"";
        }
        echo "><a href=\"".clean_url($args)."action=log\">Task Logs</a></li>\n";
        if ($children) {
            echo "<li";
            if (!$_GET) {
                echo " class=\"active\"";
            }
            echo "><a href=\"".clean_url($args)."parent_task_id=$task_id\">Child Tasks</a></li>\n";
        }
        echo "</ul>\n";
        if ($_GET['parent_task_id']) {
            if ($children) {
                //show children
            get_subtasks($task_id, 0);
            } else {
                echo "<h2 align=\"center\">No Child Tasks</h2>";
            }
        } else {
            $logs=$slave->select("SELECT L.*,Task_Name,First_Name,Last_Name,Project_Name FROM Task_Logs as L, Tasks as T LEFT OUTER JOIN Projects as P ON T.Project_ID=P.Project_ID, Users as U WHERE L.User_ID=U.User_ID AND L.Task_ID=T.Task_ID AND T.Task_ID=$task_id ORDER BY L.Creation_Date DESC");
            if ($logs) {
                echo "<table>\n";
                echo "<tr><th>Logged</th><th>Completed</th><th>Progress</th><th>Task</th><th>File</th><th>User</th><th>Cost</th><th>Time</th><th>Notes</th></tr>\n";
                foreach ($logs as $log) {
                    echo "<tr>";
                    echo "<td>".display_date($log['Creation_Date'])."<br />@".display_time($log['Creation_Date'])."</td>\n";
                    echo "<td>".display_date($log['Log_Date'])."</td>\n";
                    echo "<td>".$log['Log_Progress']."%</td>\n";
                    echo "<td>".$log['Task_Name']."</td>\n";
                    echo "<td align=\"center\">";
                    $files=$slave->select("SELECT * FROM Files as F, File_Types as T WHERE F.File_Type_ID=T.File_Type_ID AND Log_ID=".$log['Log_ID']." ORDER BY Image");
                    if ($files) {
                        $img=$multi="";
                        foreach ($files as $f) {
                            if ($img!=$f['Image'] or $img==0) {
                                $img=$f['Image'];
                                if ($f['Image']==1) {
                                    $icon="111.png";
                                    echo "<a href=\"".CDN."img.php?id=".$log['Task_ID']."&log=".$log['Log_ID']."\" alt=\"\" target=\"_blank\"><img src=\"".CDN."img/icons/$icon\" border=0 /></a>";
                                } else {
                                    $icon="3.png";
                                    echo "<a href=\"${target_path}".$f['File_ID'].".".$f['Extention']."\" alt=\"\" target=\"_blank\"><img src=\"".CDN."img/icons/$icon\" border=0 /></a>";
                                }
                            } elseif ($img==1) {
                                $mult="+";
                            }
                        }
                        echo $mult;
                    }
                    echo "</td>\n";
                    echo "<td>".display_name($log['First_Name'], $log['Last_Name'])."</td>\n";
                    echo "<td>$".number_format($log['Log_Cost'], 2)."</td>\n";
                    if ($log['Log_Time']>60 and ($log['Log_Time'] % 60<1)) {
                        $log_time=($log['Log_Time']/60)."/hour";
                    } else {
                        $log_time=$log['Log_Time']."/min";
                    }
                    echo "<td>".$log_time."</td>\n";
                    echo "<td>".str_replace("\n", "<br />", htmlentities(str_replace("&#39;", "'", $log['Public_Note']), ENT_NOQUOTES));
                    if ($log['Log_Note']) {
                        echo "<hr />".str_replace("\n", "<br />", htmlentities(str_replace("&#39;", "'", $log['Log_Note']), ENT_NOQUOTES));
                    }
                    if ($files) {
                        foreach ($files as $f) {
                            echo "<br /><em>";
                            if ($f['Original_File']) {
                                echo "Original File: ".$f['Original_File'];
                                echo "<br />Renamed: ".$f['File_ID'].".".$f['Extention'];
                            }
                            echo "</em>";
                        }
                    }
                    echo "</td>\n";
                    echo "</tr>\n";
                    $time+=$log['Log_Time'];
                    $cost+=$log['Log_Cost'];
                }
                if ($time>60) {
                    $hour=floor($time/60);
                    $min=$time%60;
                    $time="$hour/hour";
                    if ($min) {
                        $time.=" $min/min";
                    }
                } else {
                    $time="$time/min";
                }
                echo "<tr><th colspan=4 align='right'>Project Totals</th>";
                echo "<th>\$".number_format($cost, 2)."</th><th colspan=2 align='left'>$time</th></tr>\n";
                echo "</table>\n";
            } else {
                echo "<h2 align=\"center\">No Task Logs</h2>";
            }
        }
        echo "</div>";
    }
} else { //list tasks
    echo "<div style=\"float:right\"><form action=\"tasks.php\" enctype=\"multipart/form-data\">";
    
    //if ($security['Add']==true)
    echo "<a href=\"tasks.php?task_id=new\">Add New Task</a>";
    
    if (is_array($access['Projects']) || is_array($access['Sales']) || is_array($access['All Programs'])) {
        echo " | ";
        //project and User Drop Down
        $args=array("project_id","user_id");
        echo "<select name=\"project\" onChange=\"MM_jumpMenu('parent',this,0)\">\n";
        echo "<option value=\"".$_SERVER['PHP_SELF']."?project_id=all\">All Projects</option>\n";
        //projects
        $ddp=$slave->select("SELECT Project_ID, Project_Name FROM Projects ORDER BY Project_Name");
        if ($ddp) {
            echo "<optgroup label=\"Projects\">\n";
            
            echo "<option value=\"".clean_url($args)."project_id=0\"";
            if ($_SESSION['project_id']=="0") {
                echo "SELECTED";
            }
            echo ">Non Project Ticket</option>\n";
            
            foreach ($ddp as $p) {
                echo "<option value=\"".clean_url($args)."project_id=".$p['Project_ID']."\"";
                if ($_SESSION['project_id']==$p['Project_ID']) {
                    echo "SELECTED";
                }
                echo ">".$p['Project_Name']."</option>\n";
            }
            echo "</optgroup>\n";
        }
        $ddd=$slave->select("SELECT Department_ID, Department_Name FROM Departments ORDER BY Department_Name");
        if ($ddd) {
            echo "<optgroup label=\"Departments\">\n";
            
            echo "<option value=\"".clean_url($args)."department_id=0\"";
            if ($_SESSION['department_id']=="0") {
                echo "SELECTED";
            }
            echo ">No Department</option>\n";
            
            foreach ($ddd as $p) {
                echo "<option value=\"".clean_url($args)."department_id=".$p['Department_ID']."\"";
                if ($_SESSION['department_id']==$p['Department_ID']) {
                    echo "SELECTED";
                }
                echo ">".$p['Department_Name']."</option>\n";
            }
            echo "</optgroup>\n";
        }
        //distributors
        $dis=$slave->select("SELECT Company_ID, Company_Name FROM Companies WHERE Contact_Type_ID=6 ORDER BY Company_Name");
        if ($dis) {
            echo "<optgroup label=\"Distributor\">\n";
            
            foreach ($dis as $p) {
                echo "<option value=\"".clean_url($args)."distributor_id=".$p['Company_ID']."\"";
                if ($_SESSION['distributor_id']==$p['Company_ID']) {
                    echo "SELECTED";
                }
                echo ">".$p['Company_Name']."</option>\n";
            }
            echo "</optgroup>\n";
        }
        //current
        $ddu=$slave->select("SELECT User_ID, CONCAT(First_Name, ' ', Last_Name) as User, User_Status_ID FROM Users ORDER BY First_Name");
        if ($ddu) {
            echo "<optgroup label=\"Current Users\">\n";
            foreach ($ddu as $u) {
                if ($u['User_Status_ID']==1) {
                    echo "<option value=\"".clean_url($args)."user_id=".$u['User_ID']."\"";
                    if ($_SESSION['project_user_id']==$u['User_ID']) {
                        echo "SELECTED";
                    }
                    echo ">".$u['User']."</option>\n";
                }
            }
            echo "</optgroup>\n";
        }
        //users
        /*
        $ddu=$slave->select("SELECT User_ID, CONCAT(First_Name, ' ', Last_Name) as User, User_Status_ID FROM Users ORDER BY First_Name");
        if ($ddu) {
        echo "<optgroup label=\"Past Users\">\n";
        foreach ($ddu as $u) {
        if ($u['User_Status_ID']!=1) {
        echo "<option value=\"".clean_url($args)."user_id=".$u['User_ID']."\"";
        if ($_SESSION['project_user_id']==$u['User_ID']) echo "SELECTED";
        echo ">".$u['User']."</option>\n";
        }
        }
        echo "</optgroup>\n";
        }
        */
        echo "</select>";
    }
    
    echo "</form></div>";
    echo "<h1>Tasks</h1>\n";
    $offset = strtotime(date('Y-m-d'));
    
    echo "<div id=\"group\">";
    echo "<ul id=\"tabs\">\n";
    echo "<li";
    if (!$_GET or ($_GET['user_id']==$_SESSION['user_id'] and !$_GET['status'] and !$_GET['action'] and !$_GET['today'] and !$_GET['week'])) {
        echo " class=\"active\"";
    }
    echo "><a href=\"tasks.php?user_id=".$_SESSION['user_id']."\">My Todo</a></li>\n";
    echo "<li";
    if ($_GET['today']==date("Y-m-d")) {
        echo " class=\"active\"";
    }
    echo "><a href=\"tasks.php?today=".date("Y-m-d")."&user_id=".$_SESSION['user_id']."\">Daily</a></li>\n";
    echo "<li";
    if ($_GET['week']==date('Y-m-d', strtotime('-1 week', strtotime(date('Y-m-d', strtotime("next Sunday", $offset)))))) {
        echo " class=\"active\"";
    }
    echo "><a href=\"tasks.php?week=".date('Y-m-d', strtotime('-1 week', strtotime(date('Y-m-d', strtotime("next Sunday", $offset)))))."&user_id=".$_SESSION['user_id']."\">This Week</a></li>\n";
    echo "<li";
    if ($_GET['week']==date('Y-m-d', strtotime(date('Y-m-d', strtotime("next Sunday", $offset))))) {
        echo " class=\"active\"";
    }
    echo "><a href=\"tasks.php?week=".date('Y-m-d', strtotime(date('Y-m-d', strtotime("next Sunday", $offset))))."&user_id=".$_SESSION['user_id']."\">Next Week</a></li>\n";
    echo "<li";
    if ($_GET['status']==4) {
        echo " class=\"active\"";
    }
    echo "><a href=\"tasks.php?status=4&user_id=".$_SESSION['user_id']."\">My On Hold</a></li>\n";
    echo "<li";
    if ($_GET['requester']) {
        echo " class=\"active\"";
    }
    echo "><a href=\"tasks.php?requester=".$_SESSION['user_id']."\">My Requests</a></li>\n";
    echo "<li";
    if ($_GET['assign']) {
        echo " class=\"active\"";
    }
    echo "><a href=\"tasks.php?assign=".$_SESSION['user_id']."\">My Reassigned</a></li>\n";
    echo "<li";
    if ($_GET['status']=="5") {
        echo " class=\"active\"";
    }
    echo "><a href=\"tasks.php?status=5&user_id=".$_SESSION['user_id']."\">Completed</a></li>\n";
    echo "<li";
    if ($_GET['action']=="log" and $_GET['user_id']) {
        echo " class=\"active\"";
    }
    echo "><a href=\"tasks.php?action=log&user_id=".$_SESSION['user_id']."\">My Logs</a></li>\n";
    if ((is_array($access['Projects']) || is_array($access['Sales'])) || (is_array($access['All Programs']) && array_search('Administrator', $access['All Programs'])!==false)) {
        echo "<li";
        if ($_GET['project_id']=="all") {
            echo " class=\"active\"";
        }
        echo "><a href=\"tasks.php?project_id=all\">All Incomplete</a></li>\n";
        echo "<li";
        if ($_GET['action']=="both") {
            echo " class=\"active\"";
        }
        echo "><a href=\"tasks.php?action=both\">All Tasks</a></li>\n";
        //echo "<a href=\"tasks.php\">Gantt Chart</a> | ";
        echo "<li";
        if ($_GET['action']=="log" and !$_GET['user_id']) {
            echo " class=\"active\"";
        }
        echo "><a href=\"tasks.php?action=log\">All Logs</a></li>\n";
        //echo "<a href=\"tasks.php\">Events</a> | ";
        //echo "<a href=\"tasks.php\">Files</a> | ";
    }
    echo "</ul>\n";
    if ($_GET['action']=="log") {
        if ($_GET['user_id']) {
            $where="AND L.User_ID=".$_GET['user_id'];
        }
        $query="SELECT L.*,Task_Name,Username, Project_Name FROM Task_Logs as L, Login as U, Tasks as T LEFT OUTER JOIN Projects as P ON T.Project_ID=P.Project_ID WHERE L.User_ID=U.User_ID $where AND L.Task_ID=T.Task_ID ORDER BY Project_Name ASC, L.Creation_Date DESC";
        $logs=$slave->select($query);
        //echo $query;
        //print_r($logs);
        if ($logs) {
            echo "<table>\n";
            $project_name="";
            foreach ($logs as $log) {
                if (!$log['Project_Name']) {
                    $log['Project_Name']="Non Project Tickets";
                }
                if ($log['Project_Name']!=$project_name) {
                    if ($project_name!="") {
                        if ($time>60) {
                            $hour=floor($time/60);
                            $min=$time%60;
                            $time="$hour/hour";
                            if ($min) {
                                $time.=" $min/min";
                            }
                        } else {
                            $time="$time/min";
                        }
                        echo "<tr><th colspan=4 align='right'>Project Totals</th>";
                        echo "<th>\$".number_format($cost, 2)."</th><th colspan=2 align='left'>$time</th></tr>\n";
                        $time=0;
                        $cost=0;
                    }
                    $project_name=$log['Project_Name'];
                    echo "<tr><td colspan=6><h2>$project_name</h2></td></tr>";
                    echo "<tr><th>Logged</th><th>Completed</th><th>Task</th><th>File</th><th>User</th><th>Cost</th><th>Time</th><th>Notes</th></tr>\n";
                }
                echo "<tr>";
                echo "<td>".display_date($log['Creation_Date'])."<br />@".display_time($log['Creation_Date'])."</td>\n";
                echo "<td>".display_date($log['Log_Date'])."</td>\n";
                echo "<td><a href=\"tasks.php?task_id=".$log['Task_ID']."\">".$log['Task_Name']."</a></td>\n";
            
                echo "<td align=\"center\">";
                $files=$slave->select("SELECT * FROM Files as F, File_Types as T WHERE F.File_Type_ID=T.File_Type_ID AND Log_ID=".$log['Log_ID']." ORDER BY Image");
                if ($files) {
                    $img=$multi="";
                    foreach ($files as $f) {
                        if ($img!=$f['Image'] or $img==0) {
                            $img=$f['Image'];
                            if ($f['Image']==1) {
                                $icon="111.png";
                                echo "<a href=\"".CDN."img.php?id=".$log['Task_ID']."&log=".$log['Log_ID']."\" alt=\"\" target=\"_blank\"><img src=\"".CDN."img/icons/$icon\" border=0 /></a>";
                            } else {
                                $icon="3.png";
                                echo "<a href=\"${target_path}".$f['File_ID'].".".$f['Extention']."\" alt=\"\" target=\"_blank\"><img src=\"".CDN."img/icons/$icon\" border=0 /></a>";
                            }
                        } elseif ($img==1) {
                            $mult="+";
                        }
                    }
                    echo $mult;
                }
                echo "</td>\n";
                echo "<td>".$log['Username']."</td>\n";
                echo "<td>$".number_format($log['Log_Cost'], 2)."</td>\n";
                if ($log['Log_Time']>60 and ($log['Log_Time'] % 60<1)) {
                    $log_time=($log['Log_Time']/60)."/hour";
                } else {
                    $log_time=$log['Log_Time']."/min";
                }
                echo "<td>".$log_time."</td>\n";
                echo "<td>".str_replace("\n", "<br />", htmlentities(str_replace("&#39;", "'", $log['Public_Note']), ENT_NOQUOTES));
                if (($_SESSION['manager'] or $_SESSION['user_id']==$log['User_ID']) and $log['Log_Note']) {
                    echo "<br /><i><strong>Private:</strong> ".str_replace("\n", "<br />", htmlentities(str_replace("&#39;", "'", $log['Log_Note']), ENT_NOQUOTES))."</i>\n";
                }
                echo "</td>\n";
                echo "</tr>\n";
                $time+=$log['Log_Time'];
                $cost+=$log['Log_Cost'];
            }
            if ($time>60) {
                $hour=floor($time/60);
                $min=$time%60;
                $time="$hour/hour";
                if ($min) {
                    $time.=" $min/min";
                }
            } else {
                $time="$time/min";
            }
            echo "<tr><th colspan=4 align='right'>Project Totals</th>";
            echo "<th>\$".number_format($cost, 2)."</th><th colspan=2 align='left'>$time</th></tr>\n";
            echo "</table>\n";
        } else {
            echo "<h2 align=\"center\">No Task Logs</h2>";
        }
    } else {
        if ($_GET['dir']) {
            $dir=$_GET['dir'];
        } else {
            $dir="ASC";
        }
        if ($_GET['order']) {
            if ($_GET['order']=="T.Finish_Date") {
                $orderby="T.Finish_Date IS NULL, ";
            }
            $orderby=$orderby.$_GET['order']." $dir, ";
        }
        //echo "$orderby<br />";
    
        //show all products
        $parent=0;
    
        if ($_GET['status']) {
            $where.=" AND T.Status=".$_GET['status'];
        } elseif ($show_active=="yes") {
            $where.=" AND T.Status<4";
        } elseif ($show_active=="no") {
            $where.=" AND T.Status=0";
        }
        if (!$_GET or ($_GET['user_id']==$_SESSION['user_id'] and !$_GET['action'] and !$_GET['today'] and !$_GET['week'])) {
            $parent=0;
        } elseif (!$_GET['project_id'] and !$_GET['action']=="both" and !$_GET['requester'] and !$_GET['assign']) {
            //$orderby.="T.Finish_Date,";
            $parent=null;
        }
        if ($today==date("Y-m-d")) {
            $where.=" AND T.Finish_Date IS NOT NULL AND T.Finish_Date<=".$slave->mySQLQuote($today);
            if (!$_GET['order']) {
                $orderby="T.Priority DESC, ";//.="T.Finish_Date IS NULL, T.Finish_Date DESC,";
            }
        } elseif ($today) {
            $where.=" AND T.Finish_Date=".$slave->mySQLQuote($today);
            if (!$_GET['order']) {
                $orderby.="T.Priority DESC, ";//$orderby.="T.Finish_Date IS NULL, T.Finish_Date DESC,";
            }
        }
        if ($week) {
            $end=date('Y-m-d', strtotime(date('Y-m-d', strtotime("next Saturday", strtotime($week)))));
            $last_sun=date('Y-m-d', strtotime(date('Y-m-d', strtotime("last Sunday"))));
            //echo $last_sun;
            if ($week==$last_sun) {
                $where.=" AND T.Finish_Date IS NOT NULL AND T.Finish_Date<=".$slave->mySQLQuote($end);
            } else {
                $where.=" AND T.Finish_Date>=".$slave->mySQLQuote($week)." AND T.Finish_Date<=".$slave->mySQLQuote($end);
            }
            if (!$_GET['order']) {
                $orderby="T.Finish_Date IS NULL, T.Finish_Date DESC,";
            }
            //echo $where;
        }
        if ($requester) {
            $where.=" AND T.Request_User_ID=$requester AND (T.Request_User_ID!=T.Creator_ID OR T.Request_User_ID!=T.User_ID)";//((T.User_ID!=T.Creator_ID AND T.Creator_ID=$requester) OR (T.User_ID!=T.Request_User_ID AND
        }
        if ($assigner) {
            $where.=" AND (T.User_ID!=$assigner AND L.User_ID=$assigner)";
        }
        if ($show_complete=="yes") {
            $where.=" AND T.Progress=100";
            $parent=null;
        } elseif ($show_complete=="no") {
            $where.=" AND T.Progress<100";
        }
        if (isset($_SESSION['project_id'])) {
            $where.=" AND T.Project_ID=".$_SESSION['project_id'];
        }
        if (isset($_SESSION['distributor_id'])) {
            $where.=" AND T.Distributor_ID=".$_SESSION['distributor_id'];
        }
        if (isset($_SESSION['department_id'])) {
            $where.=" AND T.Department_ID=".$_SESSION['department_id'];
        }
        if ($_SESSION['project_user_id']) {
            $where.=" AND (T.User_ID=".$_SESSION['project_user_id']." OR U.User_ID=".$_SESSION['project_user_id'].")";
        }
    
        get_subtasks($parent, 0, $type, $where, $orderby);
    }
    echo "</div>\n";
}
if (!$_GET['sid']) {
    show_foot();
}

function get_parent($parent_task_id, $link=true, $print="")
{
    global $db,$slave, $parents;
    //echo $parent_task_id;
    $parent = $slave->select("SELECT Task_ID, Task_Name, Parent_Task_ID, Request_User_ID, U.First_Name, U.Last_Name FROM Tasks as T, Users as U WHERE T.Request_User_ID=U.User_ID and Task_ID=".$parent_task_id);
    if ($parent) {
        if ($parent) {
            $parents[]=$parent[0];
        }
        
        if ($print!="") {
            $print=" > $print";
        }
        
        if ($link==true) {
            $print="<a href=\"tasks.php?task_id=".$parent[0]['Task_ID']."\">".$parent[0]['Task_Name']."</a>$print";
        }
        //else $print=$parent[0]['Task_Name'].$print;
        
        get_parent($parent[0]['Parent_Task_ID'], $link, $print);
    } else {
        echo $print;
    }
}
function get_child($parent_task_id, $link=true, $print="", $space=" - ")
{
    global $db,$slave;
    //echo $parent_task_id;
    $child = $slave->select("SELECT Task_ID,Task_Name,Parent_Task_ID,Project_ID FROM Tasks WHERE Parent_Task_ID=".$parent_task_id);
    if ($child) {
        //if ($print!="") $print=" > $print";
        if ($link==true) {
            $print="$print<br />$space<a href=\"tasks.php?task_id=".$child[0]['Task_ID']."\">".$child[0]['Task_Name']."</a>";
        } else {
            $print="$print<br />$space".$child[0]['Task_Name'];
        }
        get_child($child[0]['Task_ID'], $link, $print, " &nbsp; ".$space);
    } else {
        echo "<small class=\"gray\">$print</small>";
    }
}
function get_percent($parent, $num_sub, $percent_sub, $finish_date)
{
    global $db,$slave;
    $tasks=$slave->select("SELECT Task_ID,Progress,Finish_Date FROM Tasks WHERE Progress<100 AND Parent_Task_ID=$parent");
    if ($tasks) {
        for ($y=0; $y<count($tasks); $y++) {
            $num_sub++;
            $percent_sub+=$tasks[$y]['Progress'];
            if ($finish_date<$tasks[$y]['Finish_Date']) {
                $finish_date=$tasks[$y]['Finish_Date'];
            }
            get_percent($tasks[$y]['Task_ID'], $num_sub, $percent_sub, $finish_date);
        }
    }
    return array($num_sub,$percent_sub,$finish_date);
}

function get_subtasks($parent, $level=1, $type="", $where="", $orderby="", $bg="", $preview=false)
{
    //echo "$orderby<br />";
    global $db,$slave,$priorities,$parent_task_id,$expand_collapse,$target_path,$total_tasks;
    if ($orderby!="") {
        $parent=null;
    }
    //if (!is_null($parent)) echo "parent is $parent";
    $expand_collapse=array("expand","collapse");
    $creator_id=$_SESSION['user_id'];
    if ($_SESSION['project_user_id']) {
        $creator_id=$_SESSION['project_user_id'];
    }
    if (($_GET['today'] or $_GET['user_id'] or $_GET['week']) and ($_GET['action']!="complete" and $_GET['action']!="log")) {
        $where2="LEFT OUTER JOIN Task_Acknowledgement as A ON T.Task_ID=A.Task_ID WHERE (A.Accepted=1 OR T.Creator_ID=".$creator_id.") AND ";
    } else {
        $where2="LEFT OUTER JOIN Task_Acknowledgement as A ON T.Task_ID=A.Task_ID WHERE ";
    }
    //$where2="WHERE ";
    if (!is_null($parent)) {
        if (strlen($where)==4) {
            $where="";
        }
        if ($parent===0) {
            if ($_GET['assign']) {
                $where2=$where2."(Parent_Task_ID=$parent OR Parent_Task_ID NOT IN (SELECT Task_ID FROM Tasks as T2 LEFT OUTER JOIN User_Departments as D2 ON T2.Department_ID=D2.Department_ID LEFT OUTER JOIN Users as L2 ON D2.User_ID=L2.User_ID WHERE ".str_replace(".", "2.", substr($where, 4)).")) $where";
            } else {
                $where2=$where2."(Parent_Task_ID=$parent OR Parent_Task_ID NOT IN (SELECT Task_ID FROM Tasks as T2 LEFT OUTER JOIN User_Departments as D2 ON T2.Department_ID=D2.Department_ID LEFT OUTER JOIN Users as U2 ON D2.User_ID=U2.User_ID WHERE ".str_replace(".", "2.", substr($where, 4)).")) $where";
            }
        } else {
            $where2=$where2."Parent_Task_ID=$parent $where";
        }
    } else {
        $where2=$where2.substr($where, 4);
    }
    //$where2=$where2."T.Task_ID NOT IN (SELECT Parent_Task_ID FROM Tasks) $where"; //if (is_null($parent) AND $subtasks[$t]['Parent_Task_ID']>0)
    if ($type=="complete" and $orderby=="") {
        $orderby="Log_date DESC, Task_Name";
    }
    //else if ($orderby=="" and $parent==0) $orderby="Task_Name";
    //else if ($orderby!="") $orderby=$orderby." Task_Name";
    if ($orderby and !strstr($orderby, "Task_Name")) {
        $orderby="ORDER BY ".$orderby." Task_Name";
    }
    if ($orderby and !strstr($orderby, "ORDER BY")) {
        $orderby="ORDER BY ".$orderby;
    }
    if (!$orderby and $parent==0) {
        $orderby2="ORDER BY Finish_Date IS NULL, Finish_Date";
    }
    if (!strstr($where, "L.User_ID")) {
        $log="AND L.Log_Progress=100";
    }
    
    if (strstr($orderby, "Request_Username")) {
        $orderby=str_replace("Request_Username", "RL.Username", $orderby);
        $query="SELECT T.*, Project_Name, Log_Date, AD.Department_Name as Affected_Department_Name, RL.Username FROM Login as RL, Users as U, Tasks as T LEFT OUTER JOIN Departments as AD ON T.Affected_Department=AD.Department_ID LEFT OUTER JOIN Projects as P ON T.Project_ID=P.Project_ID LEFT OUTER JOIN Task_Logs as L ON T.Task_ID=L.Task_ID $log LEFT OUTER JOIN User_Departments as D ON T.Department_ID=D.Department_ID LEFT OUTER JOIN Departments as M ON D.Department_ID=M.Department_ID LEFT OUTER JOIN Login as G ON T.User_ID=G.User_ID $where2 AND U.User_ID=T.Request_User_ID AND U.User_ID=RL.User_ID GROUP BY T.Task_ID $orderby $orderby2";
        //echo $query;
    } elseif (strstr($orderby, "Username")) {
        $query="SELECT T.*, Project_Name, Log_Date, AD.Department_Name as Affected_Department_Name FROM Tasks as T LEFT OUTER JOIN Departments as AD ON T.Affected_Department=AD.Department_ID LEFT OUTER JOIN Projects as P ON T.Project_ID=P.Project_ID LEFT OUTER JOIN Task_Logs as L ON T.Task_ID=L.Task_ID $log LEFT OUTER JOIN User_Departments as D ON T.Department_ID=D.Department_ID LEFT OUTER JOIN Departments as M ON D.Department_ID=M.Department_ID LEFT OUTER JOIN Users as U ON D.User_ID=U.User_ID LEFT OUTER JOIN Login as G ON T.User_ID=G.User_ID $where2 GROUP BY T.Task_ID $orderby $orderby2";
    } else {
        $query="SELECT T.*, Project_Name, Log_Date, AD.Department_Name as Affected_Department_Name FROM Tasks as T LEFT OUTER JOIN Departments as AD ON T.Affected_Department=AD.Department_ID LEFT OUTER JOIN Projects as P ON T.Project_ID=P.Project_ID LEFT OUTER JOIN Task_Logs as L ON T.Task_ID=L.Task_ID $log LEFT OUTER JOIN User_Departments as D ON T.Department_ID=D.Department_ID LEFT OUTER JOIN Users as U ON D.User_ID=U.User_ID $where2 GROUP BY T.Task_ID $orderby $orderby2";
    }
    //echo $query;
    //exit;
    $subtasks = $slave->select($query);
    //where parents are not assigned to user
    if (is_null($parent)) {
        $query="SELECT T.*, Project_Name, Log_Date, AD.Department_Name as Affected_Department_Name FROM Tasks as T LEFT OUTER JOIN Departments as AD ON T.Affected_Department=AD.Department_ID LEFT OUTER JOIN Projects as P ON T.Project_ID=P.Project_ID LEFT OUTER JOIN Task_Logs as L ON T.Task_ID=L.Task_ID $log LEFT OUTER JOIN User_Departments as D ON T.Department_ID=D.Department_ID LEFT OUTER JOIN Users as U ON D.User_ID=U.User_ID $where2 GROUP BY T.Task_ID $orderby $orderby2";
    }
    /*
    $query="SELECT T.*, Project_Name, Log_Date FROM Tasks as T LEFT OUTER JOIN Projects as P ON T.Project_ID=P.Project_ID LEFT OUTER JOIN Task_Logs as L ON T.Task_ID=L.Task_ID $log LEFT OUTER JOIN User_Departments as D ON T.Department_ID=D.Department_ID LEFT OUTER JOIN Users as U ON D.User_ID=U.User_ID $where2 AND Finish_Date IS NULL GROUP BY T.Task_ID ORDER BY $orderby Task_Name";
    $subtasks2 = $slave->select($query);
    if ($subtasks2) {
    if ($subtasks) $subtasks=array_merge($subtasks,$subtasks2);
    else $subtasks=$subtasks2;
    }
    */
    //if ($_SESSION['user_id']==6) print_r($subtasks);
    $expand=true;
    if ($level==0 and $type!="option") {
        if ($type=="date") {
            echo "<form action=\"tasks.php\" method=\"GET\" onSubmit=\"this.today.value=this.today.value.replace(/(\d\d)\/(\d\d)\/(\d\d\d\d)/, '$3-$1-$2')\">\n";
            echo "View Date <input name=\"today\" type=\"text\" value=\"".display_date($_GET['today'])."\" onfocus=\"this.select();lcs(this)\" onclick=\"event.cancelBubble=true;this.select();lcs(this)\"/>";
            echo "<input type=\"submit\" value=\"Go\">";
            echo "</form>\n";
        }
        if ($type!="complete") {
            echo "<div id=\"expand\">";
            $expand=false;
            if ($_SESSION['expand']['all']==1) {
                echo "<a href=\"".clean_url($expand_collapse)."expand=none\"><img src=\"".CDN."img/collapse.png\" alt=\"Collapse\" /></a>";
            } else {
                echo "<a href=\"".clean_url($expand_collapse)."expand=all\"><img src=\"".CDN."img/expand.png\" alt=\"Expand\" /></a>";
            }
            echo " all</div>\n";
        }
    }
    if ($subtasks) {
        if ($level==0 and $type!="option") {
            echo "<table><tr>";
            //if ($type!="complete") echo "<th><a href=".get_order_url('T.Progress',$dir).">Work</a></th>";
            echo "<th>&nbsp;</th>";
            echo "<th><a href=".get_order_url('T.Priority', $dir).">Severity</a></th>";
            echo "<th><a href=".get_order_url('T.Task_ID', $dir).">ID</a></th>";
            echo "<th><a href=".get_order_url('Task_Name', $dir).">Task</a></th>";
            echo "<th><a href=".get_order_url('Project_Name', $dir).">Project</a></th>";
            echo "<th><a href=".get_order_url('Affected_Department_Name', $dir).">Department</a></th>";
            echo "<th>File</th>";
            echo "<th><a href=".get_order_url('Username', $dir).">Assigned</a></th>";
            echo "<th>Last</th>";
            echo "<th><a href=".get_order_url('Request_Username', $dir).">Request</a></th>";
            echo "<th nowrap><a href=".get_order_url('T.Request_Date', $dir).">Req Date</a></th>";
            if ($type=="complete") {
                echo "<th><a href=".get_order_url('L.Log_Date', $dir).">Completed</a></th>";
            } else {
                echo "<th><a href=".get_order_url('T.Finish_Date', $dir).">Due</a></th>";
            }
            if ($type!="complete") {
                echo "<th>Days</th>";
            }
            echo "</tr>\n";
        }
        $space=str_repeat(' &nbsp; ', $level);
        $ptid=0;
        for ($t=0;$t<count($subtasks);$t++) {
            $total_tasks[$priorities[$subtasks[$t]['Priority']]['Name']]++;
            if ($type!="option" and $type!="complete" and (is_null($parent) or $parent===0) and $subtasks[$t]['Parent_Task_ID']>0 and $subtasks[$t]['Parent_Task_ID']!=$ptid) {
                $ptid=$subtasks[$t]['Parent_Task_ID'];
                echo "<tr $bg class=\"gray\">";
                echo "<td>&nbsp;</td>";
                echo "<td>&nbsp;</td>";
                echo "<td>&nbsp;</td>";
                echo "<td colspan=\"10\"><small>";
                get_parent($ptid, true);
                echo "</small></td>";
                echo "</tr>\n";
            } else {
                $ptid=$subtasks[$t]['Parent_Task_ID'];
            }
            $small_img=$use_file="";
            $expand=false;
            if ($preview==true or !$_SESSION['expand'] or ($_SESSION['expand']['all']==1 and (!isset($_SESSION['expand'][$subtasks[$t]['Task_ID']]) or $_SESSION['expand'][$subtasks[$t]['Task_ID']]!=0)) or ($_SESSION['expand']['all']==0 and $_SESSION['expand'][$subtasks[$t]['Task_ID']]==1)) {
                $expand=true;
            }
            if ($type=="option") {
                echo "<option value=\"".$subtasks[$t]['Task_ID']."\"";
                if ($subtasks[$t]['Task_ID']==$parent_task_id) {
                    echo " selected";
                }
                echo ">".$space.$subtasks[$t]['Task_Name']."</option>\n";
            } else {
                $num_sub=0;
                $percent_sub=$subtasks[$t]['Progress'];
                if ($type=="complete") {
                    $finish_date=$subtasks[$t]['Log_Date'];
                } else {
                    $finish_date=$subtasks[$t]['Finish_Date'];
                }
                //if (!is_null($parent))
                list($num_sub, $percent_sub, $finish_date)=get_percent($subtasks[$t]['Task_ID'], $num_sub, $percent_sub, $finish_date);
                //$finish_date=$subtasks[$t]['Finish_Date'];
                //if ($subtasks[$t]['Log_Date']) $finish_date=$subtasks[$t]['Log_Date'];
                //if ($level==0) {
                //    if ($t%2==0) $bg="class=\"highlight\"";
                //    else $bg="";
                //}
                //$haschildren=$slave->select("SELECT * FROM Tasks WHERE Parent_Task_ID=".$subtasks[$t]['Task_ID']);
                //$users=$slave->select("SELECT IF (UD.User_ID IS NOT NULL, UD.User_ID, U.User_ID) AS User_ID, Username, U.First_Name, U.Last_Name, Department_Name FROM Task_Users as T LEFT OUTER JOIN User_Departments as UD ON T.Department_ID=UD.Department_ID AND UD.User_ID=6 LEFT OUTER JOIN Departments as D ON UD.Department_ID=D.Department_ID LEFT OUTER JOIN Users as U ON T.User_ID=U.User_ID AND U.User_ID=6 LEFT OUTER JOIN Login as L ON U.User_ID=L.User_ID WHERE Task_ID=".$subtasks[$t]['Task_ID']." ORDER BY IF (Department_Name IS NOT NULL, Department_Name, Username)");
                //if ((!$haschildren AND $user) OR ($haschildren)) {
                echo "<tr $bg>";
                //if ($type!="complete") {
                echo "<td align='right' nowrap>";
                if (!$preview and $num_sub>0) {
                    //echo number_format($percent_sub/$num_sub,1)."% ";
                    if ($expand==true) {
                        echo "<a href=\"".clean_url($expand_collapse)."collapse=".$subtasks[$t]['Task_ID']."\" class=\"expand\"><img src=\"".CDN."img/collapse.png\" alt=\"Collapse\" /></a>";
                    } else {
                        echo "<a href=\"".clean_url($expand_collapse)."expand=".$subtasks[$t]['Task_ID']."\" class=\"expand\"><img src=\"".CDN."img/expand.png\" alt=\"Expand\" /></a>";
                    }
                } //else if ($type=="complete") echo $percent_sub."%";
                //else echo $percent_sub."%";
                //if ($percent_sub<100) echo " | <a href=\"tasks.php?work=".$subtasks[$t]['Task_ID']."\">Add</a>";
                echo "</td>";
                //}
                if ($preview) {
                    echo "<td></td><td></td>\n";
                } else {
                    echo "<td nowrap><small>";
                    if ($subtasks[$t]['High_Priority']) {
                        echo "<img src=\"".CDN."img/fire.png\" alt=\"High Priority\" />\n";
                    }
                    echo $subtasks[$t]['Priority']." ".$priorities[$subtasks[$t]['Priority']]['Name']."</small></td>";
                    echo "<td><small>".$subtasks[$t]['Task_ID']."</small></td>";
                }
                echo "<td>";
                if ($preview) {
                    echo "<small>";
                }
                if ($type!="complete" and $level>0) {
                    echo "$space - ";
                } elseif (is_null($parent) and $subtasks[$t]['Parent_Task_ID']>0) {
                    echo "$space - ";//echo "…";
                }
                echo "<a href=\"tasks.php?task_id=".$subtasks[$t]['Task_ID']."\"";
                if ($subtasks[$t]['Parent_Task_ID']>0) {
                    $pname=$slave->select("SELECT Task_Name FROM Tasks WHERE Task_ID=".$subtasks[$t]['Parent_Task_ID']);
                    echo "title=\"".$pname[0]['Task_Name']."\"";
                }
                if ($subtasks[$t]['Progress']==100) {
                    echo "><strike>".$subtasks[$t]['Task_Name']."</strike></a>";
                } else {
                    echo ">".$subtasks[$t]['Task_Name']."</a>";
                }
                //get_child($subtasks[$t]['Task_ID'],true);
                if ($preview) {
                    echo "</small>";
                }
                echo "</td>";
                echo "<td nowrap>";
                if ($subtasks[$t]['Project_ID']) {
                    echo "<small><a href=\"projects.php?project_id=".$subtasks[$t]['Project_ID']."\">".$subtasks[$t]['Project_Name']."</a></small></td>";
                } else {
                    echo "<small>Non Project Ticket</small>";
                }
                echo "</td>";
                echo "<td nowrap><small>";
                if ($subtasks[$t]['Affected_Department']>0) {
                    //$afdep=$slave->select("SELECT Department_Name FROM Departments WHERE Department_ID=".$subtasks[$t]['Affected_Department']);
                    echo $subtasks[$t]['Affected_Department_Name'];
                } else {
                    echo "No Department";
                }
                echo "</small></td>";
                echo "<td align=\"center\">";
                /*
                if ($handle = opendir($target_path)) {
                while (false !== ($file = readdir($handle))) {
                if (preg_match('/^'.$subtasks[$t]['Task_ID'].'.+/',$file)) {
                if (strstr($file,'_sm.jpg')) $small_img=$file;
                else $use_file=$file;
                }
                }
                closedir($handle);
                } else echo "could not open";
                
                if ($small_img) echo "<a href=\"${target_path}$use_file\" target=\"_blank\"><img src=\"".CDN."img/icons/111.png\" border=0 /></a>";
                else if ($use_file) echo "<a href=\"${target_path}$use_file\" target=\"_blank\"><img src=\"".CDN."img/icons/3.png\" border=0 /></a>";
                */
                $files=$slave->select("SELECT * FROM Files as F, File_Types as T WHERE F.File_Type_ID=T.File_Type_ID AND Task_ID=".$subtasks[$t]['Task_ID']." ORDER BY Image");
                if ($files) {
                    $img=$multi="";
                    foreach ($files as $f) {
                        if ($img!=$f['Image'] or $img==0) {
                            $img=$f['Image'];
                            if ($f['Image']==1) {
                                $icon="111.png";
                                echo "<a href=\"".CDN."img.php?id=".$subtasks[$t]['Task_ID']."\" alt=\"\" target=\"_blank\"><img src=\"".CDN."img/icons/$icon\" border=0 /></a>";
                            } else {
                                $icon="3.png";
                                echo "<a href=\"${target_path}".$f['File_ID'].".".$f['Extention']."\" alt=\"\" target=\"_blank\"><img src=\"".CDN."img/icons/$icon\" border=0 /></a>";
                            }
                        } elseif ($img==1) {
                            $mult="+";
                        }
                    }
                    echo $mult;
                }
                echo "</td>";
                
                echo "<td><small>";
                $users=$slave->select("SELECT IF (UD.User_ID IS NOT NULL, UD.User_ID, U.User_ID) AS User_ID, Username, U.First_Name, U.Last_Name, Department_Name FROM Tasks as T LEFT OUTER JOIN User_Departments as UD ON T.Department_ID=UD.Department_ID LEFT OUTER JOIN Departments as D ON UD.Department_ID=D.Department_ID LEFT OUTER JOIN Users as U ON T.User_ID=U.User_ID LEFT OUTER JOIN Login as L ON U.User_ID=L.User_ID WHERE Task_ID=".$subtasks[$t]['Task_ID']." ORDER BY IF (Department_Name IS NOT NULL, Department_Name, Username)");
                if ($users) {
                    $dep="";
                    for ($u=0;$u<count($users);$u++) {
                        if ($u>0 && ($users[$u]['Department_Name']=="" or $users[$u]['Department_Name']!=$dep)) {
                            echo ", ";
                        }
                        if ($users[$u]['Department_Name']) {
                            if ($users[$u]['Department_Name']!=$dep) {
                                $dep=$users[$u]['Department_Name'];
                                echo $users[$u]['Department_Name'];
                            }
                        } else {
                            echo $users[$u]['Username'];
                        }
                    }
                } else {
                    echo "none";
                }
                echo "</small></td>";
                echo "<td><small>";
                $users=$slave->select("SELECT U.User_ID, Username, U.First_Name, U.Last_Name FROM Tasks as T, Task_Logs as L, Users as U, Login as O WHERE T.Task_ID=L.Task_ID AND L.User_ID=U.User_ID AND O.User_ID=U.User_ID AND T.Task_ID=".$subtasks[$t]['Task_ID']." ORDER BY Log_ID DESC LIMIT 1");
                if (!$users) {
                    $users=$slave->select("SELECT U.User_ID, Username, U.First_Name, U.Last_Name FROM Tasks as T, Users as U, Login as O WHERE T.Creator_ID=U.User_ID AND O.User_ID=U.User_ID AND T.Task_ID=".$subtasks[$t]['Task_ID']);
                }
                if ($users) {
                    echo $users[0]['Username'];
                } else {
                    echo "none";
                }
                echo "</small></td>";
                echo "<td><small>";
                $users=$slave->select("SELECT U.User_ID, Username, U.First_Name, U.Last_Name FROM Tasks as T, Users as U, Login as L WHERE U.User_ID=L.User_ID AND T.Request_User_ID=U.User_ID AND Task_ID=".$subtasks[$t]['Task_ID']);
                if ($users) {
                    echo $users[0]['Username'];
                } else {
                    echo "none";
                }
                echo "</small></td>";
                if (display_date($subtasks[$t]['Request_Date'])=="00/00/0000" or display_date($subtasks[$t]['Request_Date'])=="NULL") {
                    echo "<td>Not Set</td>";
                } else {
                    echo "<td><small>".display_date($subtasks[$t]['Request_Date'])."</small></td>";
                }
                //echo "<td>".$subtasks[$t]['Durration']."</td>";
                //$isparent=$slave->select("SELECT Task_ID FROM Tasks WHERE Parent_Task_ID=".$subtasks[$t]['Task_ID']);
                //if ($isparent) {
                //    echo "<td>&nbsp;</td>";
                //} else
                if (display_date($finish_date)=="NULL") {
                    echo "<td><small>Not Set</small></td>";
                    if ($type!="complete") {
                        echo "<td>-</td>";
                    }
                } else {
                    echo "<td><small>".display_date($finish_date)."</small></td>";
                    if ($type!="complete" and $subtasks[$t]['Progress']<100) {
                        echo "<td><small>".dateDiff(date("Y-m-d"), $finish_date)."</small></td>";
                    }
                }
                echo "</tr>\n";
                if ($expand==true and ($_GET['today'] or $_GET['week']) and !isset($parent_task_id)) {
                    get_subtasks($subtasks[$t]['Task_ID'], $level+1, "", "AND T.Progress<100", "", "class=\"gray\"", true);
                }
                if ($expand==true and $parent===0) {
                    get_subtasks($subtasks[$t]['Task_ID'], $level+1, "", "AND T.Progress<100 AND NOT (T.User_ID=".$_SESSION['user_id']." OR U.User_ID=".$_SESSION['user_id'].")", "", "class=\"gray\"", true);
                }
                //}
            }
            if ($expand===true and !is_null($parent) and !$preview) {
                get_subtasks($subtasks[$t]['Task_ID'], $level+1, $type, $where, $orderby, $bg);
            }
            //if ($add=="no")
            //$space=str_replace('/ &nbsp; $/','',$space);
        }
        if ($level==0 and $type!="option") {
            echo "</table>";
            echo "<p>";
            $totaltt=0;
            foreach ($total_tasks as $tk=>$tv) {
                echo "Total $tk Tasks: $tv<br />";
                $totaltt=$totaltt+$tv;
            }
            echo "Grand Total: $totaltt";
            echo "</p>\n";
        }
    } else {
        if ($level==0 and $type!="option") {
            echo "<h2 align=\"center\">No Tasks</h2>";
        }
        return false;
    }
}
function update_child($project_id, $parent_task_id)
{
    global $db,$slave;
    $children=$slave->select("SELECT Task_ID FROM Tasks WHERE Parent_Task_ID=".$slave->mySQLQuote($parent_task_id));
    if ($children) {
        foreach ($children as $child) {
            $c['Project_ID']=$project_id;
            $update=$db->update("Tasks", $c, "Task_ID=".$child['Task_ID']);
            update_child($project_id, $child['Task_ID']);
        }
    }
}
function calcdays($duedays)
{
    global $db,$slave;
    $datecalc=time();
    $holidays=$slave->select("SELECT * FROM Holidays WHERE Holiday_Date>NOW() ORDER BY Holiday_Date");
    for ($i=1;$i <= $duedays;$i++) {
        $datecalc += 86400; // Add a day.
        $date_info  = getdate($datecalc);
        
        if ($date_info["wday"] == 0) {
            $datecalc += 86400; // Add a day
            continue;
        } elseif ($date_info["wday"] == 6) {
            $datecalc += 86400; // Add a day.
            $datecalc += 86400; // Add a day.
            continue;
        }
        foreach ($holidays as $h) {
            if ($h['Holiday_Date']==date("Y-m-d", $datecalc)) {
                $datecalc += 86400;
            }
        }
    }
    return date("Y-m-d", $datecalc);
}
function testcalcdays($duedays, $sun, $mon, $tue, $wed, $thu, $fri, $sat)
{
    global $db,$slave;
    $datecalc=time();
    $days[0]=$sun;
    $days[1]=$mon;
    $days[2]=$tue;
    $days[3]=$wed;
    $days[4]=$thu;
    $days[5]=$fri;
    $days[6]=$sat;
    $holidays=$slave->select("SELECT * FROM Holidays WHERE Holiday_Date>NOW() ORDER BY Holiday_Date");
    for ($i=1;$i <= $duedays;$i++) {
        $added=0;
        while ($added==0) {
            $datecalc += 86400; // Add a day.
            $date_info  = getdate($datecalc);
            for ($d=0;$d<7;$d++) {
                if ($days[$d]==1) {
                    $added=1;
                    foreach ($holidays as $h) {
                        if ($h['Holiday_Date']==date("Y-m-d", $datecalc)) {
                            $datecalc += 86400;
                            $added=0;
                        }
                    }
                } elseif ($date_info["wday"] == $d) {
                    $datecalc += 86400; // Add a day
                }
            }
        }
    }
    return date("Y-m-d", $datecalc);
}
function calcworkday($date)
{
    global $db,$slave;
    $start=0;
    list($year, $month, $day)=explode("-", $date);
    while ($day!=$start) {
        //list($year,$month,$day)=explode("-",$date);
        $start=$day;
        $datecalc=mktime(0, 0, 0, $month, $day, $year);
        $weekday  = date("N", $datecalc);
        if ($weekday==6) {
            $sub=1;
        } elseif ($weekday==7) {
            $sub=2;
        }
        if ($sub) {
            $day=$day-$sub;
            $datecalc=mktime(0, 0, 0, $month, $day, $year);
        }
        $holidays=$slave->select("SELECT * FROM Holidays WHERE Holiday_Date>NOW() ORDER BY Holiday_Date");
        $usedate=date("Y-m-d", $datecalc);
        $sub=0;
        foreach ($holidays as $h) {
            if ($h['Holiday_Date']==$usedate) {
                $sub++;
            }
            //echo $h['Holiday_Date']."?=$usedate";
        }
        if ($sub>0) {
            $day=$day-$sub;
            $datecalc=mktime(0, 0, 0, $month, $day, $year);
        }
        //$date=date("Y-m-d",$datecalc);
    }
    //echo "<br />$day?=$start";
    //if ($day!=$start) calcworkday(date("Y-m-d",$datecalc));
    return date("Y-m-d", $datecalc);
}
