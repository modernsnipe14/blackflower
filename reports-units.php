<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('FPDF_FONTPATH', __DIR__ . '/font/');
require_once('cad.conf');
require_once('fpdf.php');

// --- Database connect ---
$link = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($link->connect_error) {
    header('Content-Type: text/plain');
    die("Database connection failed: " . $link->connect_error);
}

class PDF extends FPDF
{
    function Header()
    {
        global $REPORTS_LOGO;
        if (!empty($REPORTS_LOGO) && file_exists($REPORTS_LOGO)) {
            $this->Image($REPORTS_LOGO, 175, 8, 20);
        }
        $this->SetFont('Arial','B',14);
        $this->Cell(0,10,'Unit Report',0,1,'L');
        $this->Ln(5);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

try {
    while (ob_get_level()) { ob_end_clean(); }

    $unit      = isset($_GET['unit']) ? $_GET['unit'] : '';
    $startdate = isset($_GET['startdate']) ? $_GET['startdate'] : '';
    $enddate   = isset($_GET['enddate']) ? $_GET['enddate'] : '';

    if (empty($startdate)) $startdate = '1900-01-01';
    if (empty($enddate))   $enddate   = date('Y-m-d');

    $pdf = new PDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('Arial','',12);

    $sql = "SELECT i.call_number, i.ts_opened, i.ts_complete, i.call_details,
                   u.unit, u.dispatch_time, u.arrival_time, u.cleared_time
            FROM incidents i
            JOIN incident_units u ON u.incident_id = i.incident_id
            WHERE u.unit = ?
              AND DATE(i.ts_opened) BETWEEN ? AND ?
            ORDER BY i.ts_opened ASC";

    if ($stmt = $link->prepare($sql)) {
        $stmt->bind_param("sss", $unit, $startdate, $enddate);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Table header
            $pdf->SetFont('Arial','B',9);
            $pdf->Cell(20,8,'Call #',1);
            $pdf->Cell(35,8,'Opened',1);
            $pdf->Cell(35,8,'Completed',1);
            $pdf->Cell(35,8,'Dispatch',1);
            $pdf->Cell(35,8,'Arrival',1);
            $pdf->Cell(35,8,'Cleared',1);
            $pdf->Ln();

            // Table body
            $pdf->SetFont('Arial','',8);
            while ($row = $result->fetch_assoc()) {
                $pdf->Cell(20,6,$row['call_number'],1);
                $pdf->Cell(35,6,$row['ts_opened'],1);
                $pdf->Cell(35,6,$row['ts_complete'],1);
                $pdf->Cell(35,6,$row['dispatch_time'],1);
                $pdf->Cell(35,6,$row['arrival_time'],1);
                $pdf->Cell(35,6,$row['cleared_time'],1);
                $pdf->Ln();
            }
        } else {
            $pdf->Cell(0,10,'No records found for this unit and date range.',1,1,'C');
        }

        $stmt->close();
    } else {
        $pdf->Cell(0,10,'Database query could not be prepared.',1,1,'C');
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="CAD_Unit_Report.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    $pdf->Output('F', 'php://output');
    exit;

} catch (Exception $e) {
    if (!headers_sent()) {
        header('Content-Type: text/plain');
    }
    echo "PDF generation failed: " . $e->getMessage();
}
?>

