<?php
define('FPDF_FONTPATH', 'font/');
require('fpdf.php');
require_once('db-open.php');
include('local-dls.php');
require_once('session.inc');
include('functions.php');

class PDF extends FPDF {
    function Header() 
    {
        global $REPORTS_LOGO;
        $this->Image($REPORTS_LOGO, 175, 8, 20);
        $this->SetFillColor(230);

        // top row
		global $REPORTS_TITLE;
        $this->SetY(12);
        $this->SetFont('Arial','B',14);
        $this->Cell(160,5,$REPORTS_TITLE,0,0);

        $this->SetFont('Arial','',12);
        // bottom row
        $this->SetY(19);
        $this->Cell(80,5,'Report written at: '.NOW,0,0,'L');
        $this->Cell(80,5,$this->showcriteria,0,0,'L');
        $this->Ln(8);
    }

    function Footer()     {
    // Position at 1.5 cm from bottom
    $this->SetY(-15);
    // Arial italic 8
    $this->SetFont('Arial', '', 8);
    // Page number
    $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
}

}

$subsys = "reports";

if (!CheckAuthByLevel('reports', $_SESSION["access_level"])) {
    include('include-title.php');
    header_html('Dispatch :: Access Restricted');
    print "Access level too low to access Reports page.";
    exit;
}

$filter_set_name = MysqlClean($_GET, "filterset", 80);
$incident_types_selector = MysqlClean($_GET, "incidenttypes", 80);
$startdate = MysqlClean($_GET,"startdate",20);
$enddate = MysqlClean($_GET,"enddate",20);
$daterange = $startdate;
if ($startdate != $enddate) {
    $daterange .= " - $enddate";
}

$call_types = "";

if (array_key_exists('typesselected', $_GET) && sizeof($_GET['typesselected']) > 0) {
    // ... call types logic ...
} elseif ($incident_types_selector == 'filter') {
    // ... form for incident types selection ...
} else {
    $call_types = "call_type != 'TRAINING'";
}

$query_daily_base = "SELECT DATE(dispatch_time) as dispatch_date, 
    TIME_FORMAT(SEC_TO_TIME(AVG(TIME_TO_SEC(TIMEDIFF(arrival_time,dispatch_time)))),'%i:%s') AS avg_response_time 
    FROM incident_units iu JOIN incidents i ON iu.incident_id=i.incident_id 
    WHERE DATE(dispatch_time)>='$startdate' 
    AND DATE(dispatch_time)<='$enddate' 
    AND arrival_time IS NOT NULL 
    AND $call_types ";

$query_daily_suffix = "GROUP BY dispatch_date";

// FPDF initialization
$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 12);

// PDF content generation
$pdf->Cell(0, 10, 'Response Times Report', 0, 1, 'C');
$pdf->Cell(50, 10, 'Dispatch Date', 1, 0, 'C');
$pdf->Cell(50, 10, 'Avg Response Time', 1, 1, 'C');

$result = MysqlQuery($query_daily_base . $query_daily_suffix) or die("Query error: " . mysqli_error());

while ($row = mysqli_fetch_assoc($result)) {
    $dispatch_date = $row['dispatch_date'];
    $avg_response_time = $row['avg_response_time'];
    
    $pdf->Cell(50, 10, $dispatch_date, 1, 0, 'C');
    $pdf->Cell(50, 10, $avg_response_time, 1, 1, 'C');
}

mysqli_free_result($result);

// Output the PDF
$pdf->Output();
?>
