<?php
require '../config/dbconfig.php';
// include '../eais/config.php';

$conn = sqlsrv_connect($serverName, $connectionInfo);
// $conn = sqlsrv_connect($DBHost, $DBInfo);
header("Content-Type: application/json");
header('Access-Control-Allow-Origin: *');

function utf8ize($d)
{
    if (is_array($d)) {
        foreach ($d as $k => $v) {
            $d[$k] = utf8ize($v);
        }
    } else if (is_string($d)) {
        $d = iconv(mb_detect_encoding($d, mb_detect_order(), true), "UTF-8", $d);
        return $d;
    }
    return $d;
}

switch ($_SERVER['REQUEST_METHOD']) {

    case 'POST': //Create new register in journal.
        $_POST = json_decode(file_get_contents('php://input'), true);
        $numColumns = count($_POST);
        if (isset($_GET['id_journal'])) {
            /*
            Counting amount of columns in db for the given journal and
            couting amount of elements in the body, they should match.
             */
            $idJournal = $_GET['id_journal'];
            $newRegisterBody = $_POST;
            $sql = "SELECT COUNT(*) FROM information_schema.columns where  table_name = 'jur_$idJournal'";
            $query = sqlsrv_query($conn, $sql);
            if ($query === false) {
                die(print_r(sqlsrv_errors(), true));
            }
            $row = sqlsrv_fetch_array($query);
            $numColumsInDb = array_unique($row)[0] - 1; // Without ID.
            $amountParamsBody = count($newRegisterBody);
            if ($numColumsInDb !== $amountParamsBody) {
                $response = array("Status" => "ERROR: Given more or less columns for the table to create.");
                die(print_r(json_encode($response), true));
            }

            //Creating the respective sql with respect to the number of columns in the table.
            $insertSql = "( ";
            for ($i = 1; $i <= $numColumsInDb; $i++) {
                if ($i === $numColumsInDb) {
                    $insertSql = $insertSql . "f$i ) ";
                } else {
                    $insertSql = $insertSql . "f$i , ";
                }
            }
            $values = " VALUES ( ";
            for ($i = 1; $i <= $numColumsInDb; $i++) {
                $columUpdate = $newRegisterBody["f$i"];
                if ($i === 1) {
                    $tiempo = date('Y-m-d', strtotime($columUpdate));
                    $values = $values . "'$tiempo', ";
                    continue;
                }
                if ($i === $numColumsInDb) {
                    $values = $values . " $columUpdate )";
                } else {
                    $values = $values . " $columUpdate , ";
                }
            }

            //Creating new register in journal in db.
            $sqlDate = "INSERT INTO jur_$idJournal " . $insertSql . $values;
            $newquery = sqlsrv_query($conn, $sqlDate);
            if ($newquery === false) {
                $response = array("Status" => "ERROR, register not created");
                die(print_r(json_encode($response), true));
            } else {
                $sqlDate = "SELECT  max(uid) as id_reg FROM jur_$idJournal";
                $query = sqlsrv_query($conn, $sqlDate);
                if ($query === false) {
                    die(print_r(sqlsrv_errors(), true));
                }
                $newRegister = sqlsrv_fetch_array($query);
                $newRegister = $newRegister['id_reg'];
                $response = array("Status" => "Success, register created: ", "id_rew" => "$newRegister");
                echo json_encode($response);
            }
        }
        break;

    case 'GET':
        if (isset($_GET['id_journal_graph'], $_GET['name_row'], $_GET['from'], $_GET['to'])){
            $idJournal = $_GET['id_journal_graph'];
            $nameRow = $_GET['name_row'];
            $from = $_GET['from'];
            $to = $_GET['to'];
            $sql = "SELECT f1, $nameRow from jur_$idJournal WHERE CONVERT(DATE, f1) >= '$from' and CONVERT(DATE, f1) <= '$to' order by f1";
            $query = sqlsrv_query($conn, $sql);
            $register = [];

            if ($query === false) {
                die(print_r(sqlsrv_errors(), true));
            }
            while ($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)) {
                $row['f1'] = date_format($row['f1'], 'Y-m');
                array_push($register, $row);
            }
            echo json_encode($register);
            
        }else if (isset($_GET['id_journal_info'])) {   //Get name and date about one journal.
            $idJournal = $_GET['id_journal_info'];
            $sql = "SELECT name  FROM jur_journal_list where uid = $idJournal";
            $query = sqlsrv_query($conn, $sql);
            $journal = [];

            if ($query === false) {
                die(print_r(sqlsrv_errors(), true));
            }
            while ($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)) {
                array_push($journal, $row);
            }

            //Getting minimun and maximun time date of journals.
                $sqlDate = "SELECT uid, uid_lj, mindt as datestart, maxdt as dateend FROM jur_journal_date where uid_lj = $idJournal";
                $newquery = sqlsrv_query($conn, $sqlDate);
                if ($newquery === false) {
                    die(print_r(sqlsrv_errors(), true));
                }
                $date = [];

                //Removing hours from datetime.
                while ($daterow = sqlsrv_fetch_array($newquery)) {
                    $datestart = date_format($daterow['datestart'], 'Y-m-d');
                    $dateend = date_format($daterow['dateend'], 'Y-m-d');
                    $date = array("datestart" => $datestart, "dateend" => $dateend);
                    array_push($journal[0],  $datestart,  $dateend);
                }
                $journal[0]['datestart'] = $journal[0]['0'];
                unset($journal[0]['0']);
                 $journal[0]['dateend'] = $journal[0]['1'];
                unset($journal[0]['1']);

            echo json_encode(utf8ize($journal));
        
        }else  if (isset($_GET['id_journal'], $_GET['from'], $_GET['to'])) {  //Get INFORMATION about one journal setting  minimun and maximun date.
            $idJournal = $_GET['id_journal'];
            $forQueryDateFrom = $_GET['from'];
            $forQueryDateTo = $_GET['to'];
            $sql = "SELECT *  FROM jur_$idJournal WHERE CONVERT(DATE, f1) >= '$forQueryDateFrom' and CONVERT(DATE, f1) <= '$forQueryDateTo'";
            $query = sqlsrv_query($conn, $sql);
            $journal = [];

            if ($query === false) {
                die(print_r(sqlsrv_errors(), true));
            }
            while ($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)) {
                $row['f1'] = date_format($row['f1'], 'Y-m-d');
                $row['id_journal'] = $row['uid'];
                unset($row['uid']);
                array_push($journal, $row);
            }
            echo json_encode($journal);
        
        } elseif (isset($_GET['struct'], $_GET['id_journal'])) { //Get STRUCTURE about journal by id.
                $idJournal = $_GET['id_journal'];
                $sql = "SELECT * FROM jur_journal_struct where uid_lj = $idJournal";
                $query = sqlsrv_query($conn, $sql);
                $struct = [];

                if ($query === false) {
                    die(print_r(sqlsrv_errors(), true));
                }
                while ($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)) {
                    array_push($struct, $row);
                }
                echo json_encode(utf8ize($struct));
            } elseif (isset($_GET['journals_list'])) { 
                //Get ALL journals.
                $journals = allJournals($conn);
                echo json_encode(utf8ize($journals));

            } elseif (isset($_GET['journals_list_bvu'])) { //Get ALL journals BVU.

                $sql = "SELECT uid, name_bvu FROM spr_bvu  order by name_bvu";
                $query = sqlsrv_query($conn, $sql);
                if ($query === false) {
                    die(print_r(sqlsrv_errors(), true));
                }
                $journals = [];

                while ($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)) {
                    array_push($journals, $row);
                }

                echo json_encode(utf8ize($journals));
            }elseif (isset($_GET['id_journal_bvu'])) { //Get journals by journal bvu
                $idJournalBvu =  $_GET['id_journal_bvu'];
                $sqlJournalBvu = "SELECT uid_rfs FROM [dbo].[spr_bvu_rfs] where uid_bvu = $idJournalBvu";
                $queryJournalBvu = sqlsrv_query($conn, $sqlJournalBvu);
                if ($queryJournalBvu === false) {
                    die(print_r(sqlsrv_errors(), true));
                }
                $journalsBvu = [];

                while ($row = sqlsrv_fetch_array($queryJournalBvu, SQLSRV_FETCH_ASSOC)) {
                    array_push($journalsBvu, $row);
                }
                $journals = allJournals($conn);
                $journalsByBvu = [];
               
                for ($var = 0; $var < count($journals); $var++) {
                    $idRfsJournal = $journals[$var]['id_rfs'];
                    foreach ($journalsBvu as $bvu) {
                        if ($bvu['uid_rfs'] === $idRfsJournal) {
                            array_push($journalsByBvu, $journals[$var]);
                        }
                    }
                }
                
                echo json_encode(utf8ize($journalsByBvu));
            }  
        break;

    case 'PUT':
        //Update register of journal.
        $_PUT = json_decode(file_get_contents('php://input'), true);
        $numColumns = count($_PUT);
        if (isset($_GET['id_journal'], $_GET['id_reg'])) {
            $idJournal = $_GET['id_journal'];
            $idRegister = $_GET['id_reg'];
            $journalUpdate = $_PUT;
            /*
            Counting amount of columns in db for the given journal and
            couting amount of elements in the body, they should match.
             */
            $sql = "SELECT COUNT(*) FROM information_schema.columns where  table_name = 'jur_$idJournal'";
            $query = sqlsrv_query($conn, $sql);
            if ($query === false) {
                die(print_r(sqlsrv_errors(), true));
            }
            $row = sqlsrv_fetch_array($query);
            $numColumsInDb = array_unique($row)[0] - 1; // Without ID.
            $amountParamsBody = count($journalUpdate);
            if ($numColumsInDb !== $amountParamsBody) {
                $response = array("Status" => "ERROR: Given more or less columns for the table to update.");
                die(print_r(json_encode($response), true));
            }
            //Creating the respective sql with respect to the number of columns in the table.
            $values = "SET ";
            for ($i = 1; $i <= $numColumsInDb; $i++) {
                $columUpdate = $journalUpdate["f$i"];
                if ($i === 1) {
                    $tiempo = date('Y-m-d', strtotime($columUpdate));
                    $values = $values . "f$i = '$tiempo', ";
                    continue;
                }
                if ($i === $numColumsInDb) {
                    $values = $values . "f$i = $columUpdate ";
                } else {
                    $values = $values . "f$i = $columUpdate, ";
                }
            }
            $sqlDate = "UPDATE jur_$idJournal " . $values . " WHERE uid = $idRegister";
            $newquery = sqlsrv_query($conn, $sqlDate);
            if ($newquery === false) {
                die(print_r(sqlsrv_errors(), true));
            } else {
                $response = array("Status" => "Success, table updated");
                echo json_encode($response);
            }
        }
        break;

    case 'DELETE': //Delete journal from jur_journal_list.
        $_DELETE = json_decode(file_get_contents('php://input'), true);
        if (isset($_DELETE['id_reg'], $_DELETE['id_journal'])) {
            $idJournal = $_DELETE['id_journal'];
            $idRegister = $_DELETE['id_reg'];
            $sql = "SELECT uid FROM jur_$idJournal where uid = $idRegister";
            $query = sqlsrv_query($conn, $sql);
            if ($query === false) {
                die(print_r(sqlsrv_errors(), true));
            }
            $reg = sqlsrv_fetch_array($query);
            if ($reg != null) {
                $deleteSql = "DELETE FROM jur_$idJournal where uid = $idRegister";
                $deleteQuery = sqlsrv_query($conn, $deleteSql);
                $response = array("status" => "Success");
                echo json_encode($response);
            } else {
                $response = array("status" => "Error: register in journal not found.");
                echo json_encode($response);
            }
        } elseif (isset($_DELETE['id_journal'])) {
            $idJournal = $_DELETE['id_journal'];
            $sql = "SELECT name FROM jur_journal_list where uid = $idJournal";
            $query = sqlsrv_query($conn, $sql);
            if ($query === false) {
                die(print_r(sqlsrv_errors(), true));
            }
            $journal = sqlsrv_fetch_array($query);
            if ($journal != null) {
                $deleteSql = "DELETE FROM jur_journal_list where uid = $idJournal";
                $deleteJournalQuery = sqlsrv_query($conn, $deleteSql);
                $response = array("status" => "Success");
                echo json_encode($response);
            } else {
                $response = array("status" => "Error: journal not found.");
                echo json_encode($response);
            }

            break;
        }
}


function allJournals ($conn) {
    $sql = "select distinct
j.kind,
j.name,
j.uid,
j.report,
j.shifr,
j.uid_rfs,
j.list,
max(case when s.agr_func is not null and s.agr_func<>'' then 1 else 0 end) as agr_report ,
min(d.mindt) as mindt,
max(d.maxdt) as maxdt

from jur_journal_list j
join jur_journal_struct s on s.uid_lj=j.uid
left join jur_journal_struct rs on rs.uid=s.ref
left join jur_journal_list rj on rj.uid=rs.uid_lj
left join jur_journal_date d on d.uid_lj in (j.uid,rj.uid)


group by j.kind,j.name,j.uid,j.report,j.shifr,j.list,j.uid_rfs order by kind";
                $query = sqlsrv_query($conn, $sql);
                if ($query === false) {
                    die(print_r(sqlsrv_errors(), true));
                }
                $journals = [];

                while ($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)) {
                    if($row['mindt'] != null) $row['mindt'] = date_format($row['mindt'], 'Y-m-d');
                    if($row['maxdt'] != null) $row['maxdt'] = date_format($row['maxdt'], 'Y-m-d');
                    if ($row['kind'] === 'ter' && $row['agr_report'] === 0 || $row['kind'] === 'res'){
                            array_push($journals, array('id_journal' => $row['uid'], 'name' => $row['name'], 'id_rfs' => $row['uid_rfs'], 'kind' => $row['kind'], 'datestart'=> $row['mindt'], 'dateend' => $row['maxdt']));
                    }
                     }

                 //Adding BVU Id in Each Journal.
                $sqlBvu = "SELECT uid, uid_bvu, uid_rfs  FROM spr_bvu_rfs order by uid_rfs";
                $queryBvu = sqlsrv_query($conn, $sqlBvu);
                if ($queryBvu === false) {
                    die(print_r(sqlsrv_errors(), true));
                }
                $journalBvu = [];

                //Removing hours from datetime.
                while ($journalBvuRow = sqlsrv_fetch_array($queryBvu, SQLSRV_FETCH_ASSOC)) {
                    array_push(
                        $journalBvu,
                        array('id' => $journalBvuRow['uid'], 'id_bvu' => $journalBvuRow['uid_bvu'], 'id_rfs' => $journalBvuRow['uid_rfs'])
                    );
                }

                //Ading bvu into journals.
                for ($var = 0; $var < count($journals); $var++) {
                    $uidRfs = $journals[$var]['id_rfs'];
                    foreach ($journalBvu as $bvu) {
                        if ($bvu['id_rfs'] === $uidRfs) {
                            $journals[$var]['id_bvu'] = $bvu['id_bvu'];
                        }
                    }
                }
                echo count($journals);
    return $journals;
}
