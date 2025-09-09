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
class PDF extends FPDF
{
    private $title;

    function __construct($title)
    {
        parent::__construct();
        $this->title = $title;
    }

    function Header()
    {
        global $REPORTS_LOGO, $HEADER_TITLE;
        if (!empty($REPORTS_LOGO) && is_readable($REPORTS_LOGO)) {
            try {
                $this->Image($REPORTS_LOGO, 175, 8, 20);
            } catch (Exception $e) {
                // ignore logo errors
            }
        }

        if (!empty($HEADER_TITLE)) {
            $this->SetFont('Arial','B',16);
            $header_text = $HEADER_TITLE;
            $header_text = str_replace("<br>", "\n", $header_text);
            $header_text = html_entity_decode($header_text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $header_text = str_replace("\xC2\xA0", ' ', $header_text); // normalize &nbsp;
            $this->MultiCell(0,10,$header_text,0,'L');
        }

        $this->SetFont('Arial','B',14);
        $this->Cell(0,10, $this->title, 0, 1, 'L');
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

    $startdate = isset($_GET['startdate']) ? $_GET['startdate'] : '';
    $enddate   = isset($_GET['enddate']) ? $_GET['enddate'] : '';

    if (empty($startdate)) $startdate = '1900-01-01';
    if (empty($enddate))   $enddate   = date('Y-m-d');

    $title = "Incidents Report (" . $startdate . " to " . $enddate . ")";
    $pdf = new PDF($title);
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('Arial','',12);

    $sql = "SELECT call_number, call_type, call_details, ts_opened, ts_complete, disposition
            FROM incidents
            WHERE DATE(ts_opened) BETWEEN ? AND ?
            ORDER BY ts_opened ASC";

    if ($stmt = $link->prepare($sql)) {
        $stmt->bind_param('ss', $startdate, $enddate);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $pdf->SetFont('Arial','B',9);
            $pdf->Cell(20,8,'Call #',1);
            $pdf->Cell(30,8,'Type',1);
            $pdf->Cell(70,8,'Details',1);
            $pdf->Cell(35,8,'Opened',1);
            $pdf->Cell(35,8,'Completed',1);
            $pdf->Ln();

            $pdf->SetFont('Arial','',8);
            while ($row = $result->fetch_assoc()) {
                $pdf->Cell(20,6,$row['call_number'],1);
                $pdf->Cell(30,6,$row['call_type'],1);
                $pdf->Cell(70,6,substr($row['call_details'],0,45),1);
                $pdf->Cell(35,6,$row['ts_opened'],1);
                $pdf->Cell(35,6,$row['ts_complete'],1);
                $pdf->Ln();
            }
        } else {
            $pdf->Cell(0,10,'No incidents found for this date range.',1,1,'C');
        }

        $stmt->close();
    } else {
        $pdf->Cell(0,10,'Database query could not be prepared.',1,1,'C');
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="CAD_Incidents_Report.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    $pdf->Output('F','php://output');
    exit;

} catch (Exception $e) {
    if (!headers_sent()) { header('Content-Type: text/plain'); }
    echo "PDF generation failed: " . $e->getMessage();
}
?>
