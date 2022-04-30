<?php
require '../config/dbconfig.php';

$conn = sqlsrv_connect($serverName, $connectionInfo);
header("Content-Type: application/json");

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        $_POST = json_decode(file_get_contents('php://input'), true);
        //DOING
        break;
    case 'GET': //Get all journals || One journal by if its indicated journal id.
        if (isset($_GET['id'], $_GET['from'], $_GET['to'])) {
            $idJournal = $_GET['id'];
            $forQueryDateFrom = $_GET['from'];
            $forQueryDateTo = $_GET['to'];
            $sql = "SELECT *  FROM jur_$idJournal WHERE uid = $idJournal and CONVERT(DATE, f1) > '$forQueryDateFrom' and CONVERT(DATE, f1) < '$forQueryDateTo'";
            $query = sqlsrv_query($conn, $sql);
            $journal = [];
            if ($query === false) {
                die(print_r(sqlsrv_errors(), true));
            }
            while ($row = sqlsrv_fetch_array($query)) {
                array_shift($row);
                $row['f1'] = date_format($row['f1'], 'Y-m-d');
                $row['id_journal'] = $row['uid'];
                unset($row['uid']);
                array_push($journal, $row);
            }
            echo json_encode($journal, JSON_UNESCAPED_UNICODE);
        } elseif (isset($_GET['struct'], $_GET['id'])) {
            $idJournal = $_GET['id'];
            $sql = "SELECT * FROM jur_journal_struct where uid_lj = $idJournal";
            $query = sqlsrv_query($conn, $sql);
            $struct = [];

            if ($query === false) {
                die(print_r(sqlsrv_errors(), true));
            }
            while ($row = sqlsrv_fetch_array($query)) {
                array_push($struct, $row);
            }
            echo json_encode($struct);
        } else {
            //Getting all journals.
            $sql = "SELECT  name, uid FROM jur_journal_list  order by name";
            $query = sqlsrv_query($conn, $sql);
            if ($query === false) {
                die(print_r(sqlsrv_errors(), true));
            }
            $journals = [];
            while ($row = sqlsrv_fetch_array($query)) {
                array_push($journals, array('id_journal' => $row['uid'], 'name' => $row['name']));
            }

            //Getting minimun and maximun time date of journals.
            $newsql = "SELECT uid, uid_lj, mindt as datestart, maxdt as dateend FROM jur_journal_date order by uid_lj";
            $newquery = sqlsrv_query($conn, $newsql);
            if ($newquery === false) {
                die(print_r(sqlsrv_errors(), true));
            }
            $date = [];

            //Removing hours from datetime.
            while ($daterow = sqlsrv_fetch_array($newquery)) {
                $datestart = date_format($daterow['datestart'], 'Y-m-d');
                $dateend = date_format($daterow['dateend'], 'Y-m-d');
                array_push($date, array('id_journal' => $daterow['uid_lj'], 'datestart' => $datestart, 'dateend' => $dateend));
            }

            //Search data for journals and put into each journal.
            for ($var = 0; $var < count($journals); $var++) {
                $idJournal = $journals[$var]['id_journal'];
                foreach ($date as $reg) {
                    if ($reg['id_journal'] === $idJournal) {
                        $dateForJournal = array('datestart' => $reg['datestart'], "dateend" => $reg['dateend']);
                        array_push($journals[$var], $dateForJournal);
                        $journals[$var]['date'] = $journals[$var]['0'];
                        unset($journals[$var]['0']);
                    }
                }
            }
            echo json_encode($journals, JSON_UNESCAPED_UNICODE);
        }
        break;
    case 'PUT':
        $_POST = json_decode(file_get_contents('php://input'), true);
        if (!$_POST['jur_uid']) {
            $shifr = $_POST['shifr'];
            $sql = "insert jur_journal_list(name,type,shifr,report,kind,date_stop_calc) output inserted.uid values('$jurname','A','$shifr'," . ($is_report ? 1 : 0) . ",'$jurkind',$datestopcalc)";
            $ds = sqlsrv_query($conn, $sql);
            if (!$ds) {
                //ppre(array($sql, sql_error_show(false, true)));
                sqlsrv_rollback($conn);
                exit();
            }
            $r = sqlsrv_fetch_array($ds);
            echo $r;
        } else {
            $uid = round($_POST['jur_uid']);
            $sql = "update jur_journal_list set name='$jurname',kind='$jurkind',date_stop_calc=$datestopcalc where uid=$uid ";
            $ds = sqlsrv_query($conn, $sql);
            if (!$ds) {
                //ppre(array($sql, sql_error_show(false, true)));
                sqlsrv_rollback($conn);
                exit();
            } else {
                echo $ds;
            }
        }
        break;
    case 'DELETE':
        $_DELETE = json_decode(file_get_contents('php://input'), true);
        $idJournal = $_DELETE['id'];
        $sql = "SELECT name FROM jur_journal_list where uid = $idJournal";
        $query = sqlsrv_query($conn, $sql);
        if ($query === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        $journal = sqlsrv_fetch_array($query);
        if ($journal != null) {
            $deleteSql = "DELETE FROM jur_journal_list where uid = $idJournal";
            $deleteQuery = sqlsrv_query($conn, $deleteSql);
            $response = array("status" => "Success");
            echo json_encode($response);
        } else {
            $response = array("status" => "Error: journal not found.");
            echo json_encode($response);
        }
        break;
    default:
        echo 'doing...';
}
