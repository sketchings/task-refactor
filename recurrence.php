<?php
require_once"../global.php";

if ($frequency==0) {
    $frequency=1;
}
if ($_GET['occurs']>0) {
    echo "<input type=\"checkbox\" value=1 name=\"workdays\"";
    if ($workdays!==0) {
        echo " CHECKED";
    }
    echo "> Workdays only, skip weekends and hollidays<br />\n";
}

if ($_GET['occurs']==1) {
    //daily
    echo "<input type=\"radio\" name=\"every\" value=\"number\"";
    if ($monday!=1) {
        echo " checked";
    }
    echo " /> Every <input type=\"text\" size=\"3\" name=\"frequency\" value=\"$frequency\" /> days<br />
    <input type=\"radio\" name=\"every\" value=\"weekday\"";
    if ($monday==1) {
        echo " checked";
    }
    echo " /> Every weekday";
} elseif ($_GET['occurs']==2) {
    //weekly
    echo "Every <input type=\"text\" size=\"3\" name=\"frequency\" value=\"$frequency\" /> weeks on<br />";
    echo "<input type=\"checkbox\" name=\"monday\" value=1 ";
    if (($task_id!="new" and $monday==1) or date("w")==1) {
        echo "checked";
    }
    echo " /> Monday";
    echo "<input type=\"checkbox\" name=\"tuesday\" value=1 ";
    if (($task_id!="new" and $tuesday==1) or date("w")==2) {
        echo "checked";
    }
    echo " /> Tuesday";
    echo "<input type=\"checkbox\" name=\"wednesday\" value=1 ";
    if (($task_id!="new" and $wednesday==1) or date("w")==3) {
        echo "checked";
    }
    echo " /> Wednesday";
    echo "<input type=\"checkbox\" name=\"thursday\" value=1 ";
    if (($task_id!="new" and $thurseday==1) or date("w")==4) {
        echo "checked";
    }
    echo " /> Thursday<br />";
    echo "<input type=\"checkbox\" name=\"friday\" value=1 ";
    if (($task_id!="new" and $friday==1) or date("w")==5) {
        echo "checked";
    }
    echo " /> Friday";
    echo "<input type=\"checkbox\" name=\"saturday\" value=1 ";
    if (($task_id!="new" and $saturday==1) or date("w")==6) {
        echo "checked";
    }
    echo " /> Saturday";
    echo "<input type=\"checkbox\" name=\"sunday\" value=1 ";
    if (($task_id!="new" and $sunday==1) or date("w")==0) {
        echo "checked";
    }
    echo " /> Sunday";
} elseif ($_GET['occurs']==3) {
    //monthly
    echo "Every <input type=\"text\" size=\"3\" name=\"frequency\" value=\"$frequency\" /> months on:<br />";
    echo "<input type=\"radio\" name=\"every\" value=\"month_day\" ".($month_day>0?"checked":"")."> The <select name=\"month_day\">\n";
    echo "<option value=\"1\" ";
    if ($month_day==1 or ($month_day==0 and date("j")==1)) {
        echo "selected";
    }
    echo ">1st</option>\n";
    echo "<option value=\"2\" ";
    if ($month_day==1 or ($month_day==0 and date("j")==1)) {
        echo "selected";
    }
    echo ">2nd</option>\n";
    echo "<option value=\"3\" ";
    if ($month_day==1 or ($month_day==0 and date("j")==1)) {
        echo "selected";
    }
    echo ">3rd</option>\n";
    for ($num=4;$num<32;$num++) {
        echo "<option value=\"$num\" ";
        if ($month_day==$num or ($month_day==0 and date("j")==$num)) {
            echo "selected";
        }
        echo ">".$num."th</option>\n";
    }
    echo "</select><br />\n";
    echo "<input type=\"radio\" name=\"every\" value=\"week\" ".($week>0?"checked":"")."> The <select name=\"week\">\n";
    echo "<option value=\"1\">First</option>\n";
    echo "<option value=\"2\"".($week==2?"selected":"").">Second</option>\n";
    echo "<option value=\"3\"".($week==3?"selected":"").">Third</option>\n";
    echo "<option value=\"4\"".($week==4?"selected":"").">Fourth</option>\n";
    echo "<option value=\"5\"".($week==5?"selected":"").">Fifth</option>\n";
    echo "</select> <select name=\"weekday\">\n";
    echo "<option value=\"monday\" ";
    $weekday=0;
    if ($monday==1 or $tuesday==1 or $wednesday==1 or $thursday==1 or $friday==1 or $saturday==1 or $sunday==1) {
        $weekday=1;
    }
    if ($monday==1 or ($weekday==0 and date("w")==1)) {
        echo "selected";
    }
    echo ">Monday</option>\n";
    echo "<option value=\"tuesday\" ";
    if ($tuesday==1 or ($weekday==0 and date("w")==2)) {
        echo "selected";
    }
    echo ">Tuesday</option>\n";
    ;
    echo "<option value=\"wednesday\" ";
    if ($wednesday==1 or ($weekday==0 and date("w")==3)) {
        echo "selected";
    }
    echo ">Wednesday</option>\n";
    echo "<option value=\"thursday\" ";
    if ($thursday==1 or ($weekday==0 and date("w")==4)) {
        echo "selected";
    }
    echo ">Thursday</option>\n";
    echo "<option value=\"friday\" ";
    if ($friday==1 or ($weekday==0 and date("w")==5)) {
        echo "selected";
    }
    echo ">Friday</option>\n";
    echo "<option value=\"saturday\" ";
    if ($saturday==1 or ($weekday==0 and date("w")==6)) {
        echo "selected";
    }
    echo ">Saturday</option>\n";
    echo "<option value=\"sunday\" ";
    if ($sunday==1 or ($weekday==0 and date("w")==0)) {
        echo "selected";
    }
    echo ">Sunday</option>\n";
    echo "</select>\n";
} elseif ($_GET['occurs']==4) {
    //yearly
    echo "Every <input type=\"text\" size=\"3\" name=\"frequency\" value=\"$frequency\" /> years<br />";
    echo "<input type=\"radio\" name=\"every\" value=\"month_day\" ".($month_day>0?"checked":"")."> on <select name=\"month1\">\n";
    echo "<option value=\"1\" ";
    if ($month==1 or ($month==0 and date("n")==1)) {
        echo "selected";
    }
    echo ">January</option>\n";
    echo "<option value=\"2\" ";
    if ($month==2 or ($month==0 and date("n")==2)) {
        echo "selected";
    }
    echo ">February</option>\n";
    ;
    echo "<option value=\"3\" ";
    if ($month==3 or ($month==0 and date("n")==3)) {
        echo "selected";
    }
    echo ">March</option>\n";
    echo "<option value=\"4\" ";
    if ($month==4 or ($month==0 and date("n")==4)) {
        echo "selected";
    }
    echo ">April</option>\n";
    echo "<option value=\"5\" ";
    if ($month==5 or ($month==0 and date("n")==5)) {
        echo "selected";
    }
    echo ">May</option>\n";
    echo "<option value=\"6\" ";
    if ($month==6 or ($month==0 and date("n")==6)) {
        echo "selected";
    }
    echo ">June</option>\n";
    echo "<option value=\"7\" ";
    if ($month==7 or ($month==0 and date("n")==7)) {
        echo "selected";
    }
    echo ">July</option>\n";
    echo "<option value=\"8\" ";
    if ($month==8 or ($month==0 and date("n")==8)) {
        echo "selected";
    }
    echo ">August</option>\n";
    echo "<option value=\"9\" ";
    if ($month==9 or ($month==0 and date("n")==9)) {
        echo "selected";
    }
    echo ">September</option>\n";
    echo "<option value=\"10\" ";
    if ($month==10 or ($month==0 and date("n")==10)) {
        echo "selected";
    }
    echo ">October</option>\n";
    echo "<option value=\"11\" ";
    if ($month==11 or ($month==0 and date("n")==11)) {
        echo "selected";
    }
    echo ">November</option>\n";
    echo "<option value=\"12\" ";
    if ($month==12 or ($month==0 and date("n")==12)) {
        echo "selected";
    }
    echo ">December</option>\n";
    echo "</select> <select name=\"month_day\">\n";
    echo "<option value=\"1\" ";
    if ($month_day==1 or ($month_day==0 and date("j")==1)) {
        echo "selected";
    }
    echo ">1st</option>\n";
    echo "<option value=\"2\" ";
    if ($month_day==1 or ($month_day==0 and date("j")==1)) {
        echo "selected";
    }
    echo ">2nd</option>\n";
    echo "<option value=\"3\" ";
    if ($month_day==1 or ($month_day==0 and date("j")==1)) {
        echo "selected";
    }
    echo ">3rd</option>\n";
    for ($num=4;$num<32;$num++) {
        echo "<option value=\"$num\" ";
        if ($month_day==$num or ($month_day==0 and date("j")==$num)) {
            echo "selected";
        }
        echo ">".$num."th</option>\n";
    }
    echo "</select><br />\n";
    echo "<input type=\"radio\" name=\"every\" value=\"week\" ".($week>0?"checked":"")."> on the <select name=\"week\">\n";
    echo "<option value=\"1\">First</option>\n";
    echo "<option value=\"2\"".($week==2?"selected":"").">Second</option>\n";
    echo "<option value=\"3\"".($week==3?"selected":"").">Third</option>\n";
    echo "<option value=\"4\"".($week==4?"selected":"").">Fourth</option>\n";
    echo "<option value=\"5\"".($week==5?"selected":"").">Fifth</option>\n";
    echo "</select> <select name=\"weekday\">\n";
    echo "<option value=\"monday\" ";
    $weekday=0;
    if ($monday==1 or $tuesday==1 or $wednesday==1 or $thursday==1 or $friday==1 or $saturday==1 or $sunday==1) {
        $weekday=1;
    }
    if ($monday==1 or ($weekday==0 and date("w")==1)) {
        echo "selected";
    }
    echo ">Monday</option>\n";
    echo "<option value=\"tuesday\" ";
    if ($tuesday==1 or ($weekday==0 and date("w")==2)) {
        echo "selected";
    }
    echo ">Tuesday</option>\n";
    ;
    echo "<option value=\"wednesday\" ";
    if ($wednesday==1 or ($weekday==0 and date("w")==3)) {
        echo "selected";
    }
    echo ">Wednesday</option>\n";
    echo "<option value=\"thursday\" ";
    if ($thursday==1 or ($weekday==0 and date("w")==4)) {
        echo "selected";
    }
    echo ">Thursday</option>\n";
    echo "<option value=\"friday\" ";
    if ($friday==1 or ($weekday==0 and date("w")==5)) {
        echo "selected";
    }
    echo ">Friday</option>\n";
    echo "<option value=\"saturday\" ";
    if ($saturday==1 or ($weekday==0 and date("w")==6)) {
        echo "selected";
    }
    echo ">Saturday</option>\n";
    echo "<option value=\"sunday\" ";
    if ($sunday==1 or ($weekday==0 and date("w")==0)) {
        echo "selected";
    }
    echo ">Sunday</option>\n";
    echo "</select> of <select name=\"month2\">\n";
    echo "<option value=\"1\" ";
    if ($month==1 or ($month==0 and date("n")==1)) {
        echo "selected";
    }
    echo ">January</option>\n";
    echo "<option value=\"2\" ";
    if ($month==2 or ($month==0 and date("n")==2)) {
        echo "selected";
    }
    echo ">February</option>\n";
    ;
    echo "<option value=\"3\" ";
    if ($month==3 or ($month==0 and date("n")==3)) {
        echo "selected";
    }
    echo ">March</option>\n";
    echo "<option value=\"4\" ";
    if ($month==4 or ($month==0 and date("n")==4)) {
        echo "selected";
    }
    echo ">April</option>\n";
    echo "<option value=\"5\" ";
    if ($month==5 or ($month==0 and date("n")==5)) {
        echo "selected";
    }
    echo ">May</option>\n";
    echo "<option value=\"6\" ";
    if ($month==6 or ($month==0 and date("n")==6)) {
        echo "selected";
    }
    echo ">June</option>\n";
    echo "<option value=\"7\" ";
    if ($month==7 or ($month==0 and date("n")==7)) {
        echo "selected";
    }
    echo ">July</option>\n";
    echo "<option value=\"8\" ";
    if ($month==8 or ($month==0 and date("n")==8)) {
        echo "selected";
    }
    echo ">August</option>\n";
    echo "<option value=\"9\" ";
    if ($month==9 or ($month==0 and date("n")==9)) {
        echo "selected";
    }
    echo ">September</option>\n";
    echo "<option value=\"10\" ";
    if ($month==10 or ($month==0 and date("n")==10)) {
        echo "selected";
    }
    echo ">October</option>\n";
    echo "<option value=\"11\" ";
    if ($month==11 or ($month==0 and date("n")==11)) {
        echo "selected";
    }
    echo ">November</option>\n";
    echo "<option value=\"12\" ";
    if ($month==12 or ($month==0 and date("n")==12)) {
        echo "selected";
    }
    echo ">December</option>\n";
    echo "</select>\n";
} else {
    echo "";
}

if ($_GET['occurs']>0) {
    echo '<fieldset><legend>Range</legend>
    <table><tr>
    <td>Starts:</td>
    <td><input type="text" name="start_range" value="';
    if ($start_range) {
        echo display_date($start_range);
    } else {
        echo date('m/d/Y');
    }
    echo '" onfocus="this.select();lcs(this)" onclick="event.cancelBubble=true;this.select();lcs(this)" /></td>
    <td><input type="radio" name="ends" value="0"';
    if (!$end_range) {
        echo ' checked="checked"';
    }
    echo ' />No end date<br />
    <input type="radio" name="ends" value="1"';
    if ($end_range) {
        echo ' checked="checked"';
    }
    echo ' />Ends: <input type="text" name="end_range"';
    if ($end_range) {
        echo " value=\"".display_date($end_range)."\"";
    }
    echo ' onfocus="this.select();lcs(this);document.getElementsByName(\'ends\')[1].checked=true;" onclick="event.cancelBubble=true;this.select();lcs(this);" /></td>
    </tr></table>
    </fieldset>';
}
