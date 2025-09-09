<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

define('FPDF_FONTPATH', __DIR__ . '/font/');
require_once('cad.conf');
require_once('fpdf.php');

$link = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($link->connect_error) {
    header('Content-Type: text/plain');
    die("Database connection failed: " . $link->connect_error);
}

class PDF extends FPDF {
    function Header() {
        global $REPORTS_LOGO;
        if (!empty($REPORTS_LOGO) && file_exists($REPORTS_LOGO)) {
            $this->Image($REPORTS_LOGO, 175, 8, 20);
        }
        $this->SetFont('Arial','B',14);
        $this->Cell(0,10,'Unit Utilization Report',0,1,'L');
        $this->Ln(5);
    }
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

try {
    while (ob_get_level()) { ob_end_clean(); }
    $startdate = isset($_GET['startdate']) ? $_GET['startdate'] : '1900-01-01';
    $enddate   = isset($_GET['enddate']) ? $_GET['enddate'] : date('Y-m-d');

    $pdf = new PDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('Arial','',12);

    $sql = "SELECT u.unit, COUNT(*) AS total_calls
            FROM incidents i
            JOIN incident_units u ON u.incident_id = i.incident_id
            WHERE DATE(i.ts_opened) BETWEEN ? AND ?
            GROUP BY u.unit
            ORDER BY total_calls DESC";
    if ($stmt = $link->prepare($sql)) {
        $stmt->bind_param("ss", $startdate, $enddate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $pdf->SetFont('Arial','B',10);
            $pdf->Cell(80,8,'Unit',1);
            $pdf->Cell(40,8,'Total Calls',1);
            $pdf->Ln();
            $pdf->SetFont('Arial','',9);
            while ($row = $result->fetch_assoc()) {
                $pdf->Cell(80,6,$row['unit'],1);
                $pdf->Cell(40,6,$row['total_calls'],1);
                $pdf->Ln();
            }
        } else {
            $pdf->Cell(0,10,'No utilization data found for this date range.',1,1,'C');
        }
        $stmt->close();
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="CAD_Utilization_Report.pdf"');
    $pdf->Output('F','php://output');
    exit;
} catch (Exception $e) {
    if (!headers_sent()) { header('Content-Type: text/plain'); }
    echo "PDF generation failed: " . $e->getMessage();
}
?>
