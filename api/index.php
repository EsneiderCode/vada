<?php
require '../config/dbconfig.php';

$conn = sqlsrv_connect($serverName, $connectionInfo);
header("Content-Type: application/json");

switch ($_SERVER['REQUEST_METHOD']) {
     case 'POST':
          $_POST = json_decode(file_get_contents('php://input'), true);
          break;
     case 'GET': //Get all journals or one journal by id.
          //If in the request is missing the id of journal then return all journals.
          if (isset($_GET['id'])) {
               $id = $_GET['id'];
               $sql = "SELECT * FROM jur_journal_list where uid=$id";
               $query = sqlsrv_query($conn, $sql);
               while ($row = sqlsrv_fetch_array($query)) {
                    echo json_encode($row, JSON_UNESCAPED_UNICODE);
               }
               if ($query === false) {
                    die(print_r(sqlsrv_errors(), true));
               }
          } else {
               $sql = "SELECT * FROM jur_journal_list order by name";
               $query = sqlsrv_query($conn, $sql);
               while ($row = sqlsrv_fetch_array($query)) {
                    echo json_encode($row, JSON_UNESCAPED_UNICODE);
               }
               if ($query === false) {
                    die(print_r(sqlsrv_errors(), true));
               }
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
               }else {
                    echo $ds;
               }
          }
          break;
     default:
          echo 'doing...';
}
