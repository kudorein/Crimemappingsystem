<?php
require_once 'conn.php';
date_default_timezone_set('Asia/Manila');

// Route unauthorized traffic instantly back to validation gate
if (!isset($_SESSION['user'])) {
    header("Location: index.php"); // <--- This redirects to your new login page
    exit();
}

$userRow = $_SESSION['user'];
$uid = isset($userRow['uid']) ? $userRow['uid'] : 1;
$fullname = trim(($userRow['fname'] ?? '') . ' ' . ($userRow['lname'] ?? ''));
if (empty($fullname)) {
    $fullname = "System Administrator";
}

// 1. Process Actions: Inbound Record Form Logs Execution Target
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_incident') {
    $barangay = mysqli_real_escape_string($conn, $_POST['barangay']);
    
    $crime_type = $_POST['crime_type'];
    if ($crime_type === 'Others' && !empty($_POST['crime_type_custom'])) {
        $crime_type = trim($_POST['crime_type_custom']);
    }
    $crime_type = mysqli_real_escape_string($conn, $crime_type);

    $latitude = mysqli_real_escape_string($conn, $_POST['latitude']);
    $longitude = mysqli_real_escape_string($conn, $_POST['longitude']);
    $datetime = mysqli_real_escape_string($conn, $_POST['datetime']);
    $severity = mysqli_real_escape_string($conn, $_POST['severity']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    $safe_fullname = mysqli_real_escape_string($conn, $fullname);

    $insertQuery = "INSERT INTO incidents (barangay, crime_type, latitude, longitude, incident_date, severity, notes, is_archived, reported_by_uid, reported_by_name) 
                    VALUES ('$barangay', '$crime_type', '$latitude', '$longitude', '$datetime', '$severity', '$notes', 0, '$uid', '$safe_fullname')";
    mysqli_query($conn, $insertQuery);
    header("Location: dashboard.php");
    exit;
}

// 2. Clear Operational Record Archival Updates Actions Toggles
if (isset($_GET['archive_id'])) {
    $archive_id = mysqli_real_escape_string($conn, $_GET['archive_id']);
    mysqli_query($conn, "UPDATE incidents SET is_archived = 1 WHERE id = '$archive_id'");
    header("Location: dashboard.php");
    exit;
}
if (isset($_GET['restore_id'])) {
    $restore_id = mysqli_real_escape_string($conn, $_GET['restore_id']);
    mysqli_query($conn, "UPDATE incidents SET is_archived = 0 WHERE id = '$restore_id'");
    header("Location: dashboard.php");
    exit;
}

// 3. Evaluation Arrays: Filtering parameters for view selections
$view_archived = isset($_GET['view_archived']) && $_GET['view_archived'] == '1' ? 1 : 0;
$search_barangay = isset($_GET['search_barangay']) ? mysqli_real_escape_string($conn, $_GET['search_barangay']) : '';
$search_crime = isset($_GET['search_crime']) ? mysqli_real_escape_string($conn, $_GET['search_crime']) : 'All Crimes';
$stats_frame = isset($_GET['stats_frame']) ? $_GET['stats_frame'] : 'monthly'; 

$whereClauses = ["is_archived = $view_archived"];
if (!empty($search_barangay)) {
    $whereClauses[] = "barangay LIKE '%$search_barangay%'";
}
if ($search_crime !== 'All Crimes') {
    $whereClauses[] = "crime_type = '$search_crime'";
}
$whereSQL = "WHERE " . implode(' AND ', $whereClauses);

// -------------------------------------------------------------------------
// PAGINATION CALCULATOR MATRIX
// -------------------------------------------------------------------------
$limit = 10; 
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) { $current_page = 1; }
$offset = ($current_page - 1) * $limit;

// Total Count Query for calculation blocks
$countQuery = "SELECT COUNT(*) as total FROM incidents $whereSQL";
$countResult = mysqli_query($conn, $countQuery);
$total_rows = mysqli_fetch_assoc($countResult)['total'] ?? 0;
$total_pages = ceil($total_rows / $limit);
if ($total_pages < 1) { $total_pages = 1; }
if ($current_page > $total_pages) { $current_page = $total_pages; $offset = ($current_page - 1) * $limit; }

// 4. Hotspots aggregation matrices query loop calculation
$hotspotQuery = "SELECT barangay, COUNT(*) as total FROM incidents WHERE is_archived = 0 GROUP BY barangay ORDER BY total DESC LIMIT 3";
$hotspotResult = mysqli_query($conn, $hotspotQuery);

// 5. Gather Logs feed data array records (WITH PAGINATION ENFORCED)
$incidentQuery = "SELECT * FROM incidents $whereSQL ORDER BY incident_date DESC LIMIT $limit OFFSET $offset";
$incidentResult = mysqli_query($conn, $incidentQuery);
$incidentRows = [];

// For the map plot context, gather all filtered markers without pagination limit restrictions
$mapIncidentQuery = "SELECT * FROM incidents $whereSQL ORDER BY incident_date DESC";
$mapIncidentResult = mysqli_query($conn, $mapIncidentQuery);
$mapIncidentRows = [];
if ($mapIncidentResult) {
    while ($mRow = mysqli_fetch_assoc($mapIncidentResult)) {
        $mapIncidentRows[] = $mRow;
    }
}

$valid_crimes = [
    'Murder', 'Homicide', 'Physical Injury', 'Rape', 
    'Robbery', 'Theft', 'Carnapping of MV', 'Carnapping of Motorcycles', 'Others'
];
$chartTypes = array_fill_keys($valid_crimes, 0);

$timeLabels = [];
$timeData = [];

if ($stats_frame === 'weekly') {
    $timeLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    $timeData = array_fill(0, 7, 0);
} elseif ($stats_frame === 'yearly') {
    $timeLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $timeData = array_fill(0, 12, 0);
} else { 
    for ($i = 1; $i <= 31; $i++) { $timeLabels[] = "Day " . $i; }
    $timeData = array_fill(0, 31, 0);
}

// Read from unpaginated pool for charts to reflect complete statistics dashboard accurate data
foreach ($mapIncidentRows as $row) {
    $c_type = $row['crime_type'];
    if (array_key_exists($c_type, $chartTypes)) {
        $chartTypes[$c_type]++;
    } else {
        $chartTypes['Others']++;
    }

    $timestamp = strtotime($row['incident_date']);
    if ($stats_frame === 'weekly') {
        $day_index = (int)date('w', $timestamp); 
        $timeData[$day_index]++;
    } elseif ($stats_frame === 'yearly') {
        $month_index = (int)date('n', $timestamp) - 1; 
        $timeData[$month_index]++;
    } else {
        $day_of_month = (int)date('j', $timestamp); 
        if ($day_of_month <= 31) {
            $timeData[$day_of_month - 1]++;
        }
    }
}

if ($incidentResult) {
    while ($row = mysqli_fetch_assoc($incidentResult)) {
        $incidentRows[] = $row;
    }
}

$allLocationsResult = mysqli_query($conn, "SELECT DISTINCT barangay FROM incidents ORDER BY barangay ASC");
$allCrimesResult = mysqli_query($conn, "SELECT DISTINCT crime_type FROM incidents ORDER BY crime_type ASC");

// Helper function to maintain filter persistence strings in url parameters
function maintainUrlFilters($pageNum, $view_archived, $stats_frame, $search_barangay, $search_crime) {
    return "?page=" . $pageNum . 
           "&view_archived=" . $view_archived . 
           "&stats_frame=" . $stats_frame . 
           "&search_barangay=" . urlencode($search_barangay) . 
           "&search_crime=" . urlencode($search_crime);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crime Mapping Command Station</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        .leaflet-tile { filter: brightness(0.6) invert(1) contrast(3) hue-rotate(200deg) saturate(0.3); }
        .leaflet-container { background: #0f172a !important; color: #f8fafc; }
        .leaflet-popup-content-wrapper, .leaflet-popup-tip { background: #1e293b !important; color: #f8fafc !important; border: 1px solid #334155; }
        @keyframes pulse-glow { 0%, 100% { opacity: 1; color: #34d399; } 50% { opacity: 0.4; color: #60a5fa; } }
        .resolving-text { animation: pulse-glow 1.5s infinite ease-in-out; }

        @media print {
    /* Hide everything on the screen except the report container */
    body * { 
        display: none !important; 
    }
    
    #printable_pdf_report_container, 
    #printable_pdf_report_container * { 
        display: block !important; 
    }
    
    #printable_pdf_report_container { 
        position: absolute; 
        left: 0; 
        top: 0; 
        width: 100%; 
        color: #000 !important; 
        background: #fff !important; 
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif !important; 
    }

    /* Force the data matrix table layout to behave */
    .pdf-report-table { 
        display: table !important; 
        width: 100% !important; 
        border-collapse: collapse !important; 
        margin-top: 20px !important; 
        font-size: 11px !important;
        page-break-inside: auto !important;
    }
    
    .pdf-report-table thead { 
        display: table-header-group !important; 
    }
    
    .pdf-report-table tbody { 
        display: table-row-group !important; 
    }
    
    .pdf-report-table tr { 
        display: table-row !important; 
        page-break-inside: avoid !important;
        page-break-after: auto !important;
    }
    
    /* Crucial Fix: Force headers and cells to align horizontally */
    .pdf-report-table th, 
    .pdf-report-table td { 
        display: table-cell !important; 
        border: 1px solid #94a3b8 !important; 
        padding: 6px 8px !important; 
        text-align: left !important; 
        vertical-align: middle !important; 
    }
    
    .pdf-report-table th { 
        background-color: #f1f5f9 !important; 
        font-weight: 700 !important; 
        color: #0f172a !important;
        text-transform: uppercase !important;
        font-size: 10px !important;
        letter-spacing: 0.05em !important;
    }
}
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen font-sans relative">

    <nav class="bg-slate-800 border-b border-slate-700 px-6 py-4 flex justify-between items-center sticky top-0 z-50">
        <div class="flex items-center space-x-3">
            <div class="bg-blue-600 p-2 rounded text-white font-bold tracking-wider text-xs">CMS</div>
            <h1 class="text-lg font-bold tracking-tight">Crime Mapping System</h1>
        </div>
        <div class="flex items-center space-x-6">
            <span class="text-sm text-slate-400">Logged Operator: <strong class="text-blue-400"><?php echo htmlspecialchars($fullname); ?></strong></span>
            <a href="lagout.php" class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-4 py-2 rounded transition shadow">
                Log Out
            </a>
        </div>
    </nav>

    <main class="p-6 grid grid-cols-1 xl:grid-cols-3 gap-6">
        
        <div class="space-y-6">
            <div class="bg-slate-800 p-5 rounded-lg border border-slate-700 shadow-xl">
                <h2 class="text-md font-bold mb-4 text-blue-400 uppercase tracking-wider">Log New Incident</h2>
                <form action="dashboard.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="add_incident">
                    
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">Incident Location (Street, Brgy, City)</label>
                        <input type="text" id="barangay_form_input" name="barangay" placeholder="🗺️ Click map matrix coordinates to auto-resolve..." class="w-full bg-slate-900 border border-slate-700 rounded px-3 py-2 text-sm text-emerald-400 font-semibold focus:outline-none focus:border-blue-500" required>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">Crime Classification Profile</label>
                        <select id="crime_type_select" name="crime_type" class="w-full bg-slate-900 border border-slate-700 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                            <option value="Murder">Murder</option>
                            <option value="Homicide">Homicide</option>
                            <option value="Physical Injury">Physical Injury</option>
                            <option value="Rape">Rape</option>
                            <option value="Robbery">Robbery</option>
                            <option value="Theft">Theft</option>
                            <option value="Carnapping of MV">Carnapping of Motor Vehicles (4+ wheels)</option>
                            <option value="Carnapping of Motorcycles">Carnapping of Motorcycles (Motornapping)</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>

                    <div id="custom_crime_container" class="hidden transition duration-200">
                        <label class="block text-xs font-medium text-amber-400 mb-1">Specify Crime Classification</label>
                        <input type="text" name="crime_type_custom" placeholder="e.g., Malicious Mischief, Swindling..." class="w-full bg-slate-900 border border-amber-500/50 rounded px-3 py-2 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-amber-500">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">Geospatial Latitude</label>
                            <input type="number" step="any" id="lat_form_input" name="latitude" placeholder="14.5995" class="w-full bg-slate-900 border border-slate-700 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">Geospatial Longitude</label>
                            <input type="number" step="any" id="lng_form_input" name="longitude" placeholder="120.9842" class="w-full bg-slate-900 border border-slate-700 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">Incident Time Stamp</label>
                        <input type="datetime-local" name="datetime" class="w-full bg-slate-900 border border-slate-700 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500" required>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">Threat Level Severity</label>
                        <select name="severity" class="w-full bg-slate-900 border border-slate-700 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                            <option value="Low">Low Threat</option>
                            <option value="Medium">Medium Alert</option>
                            <option value="High">High Threat Level</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">Notes / Tactical Narrative</label>
                        <textarea name="notes" rows="2" class="w-full bg-slate-900 border border-slate-700 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500 placeholder-slate-600" placeholder="Describe tactical intelligence parameters..."></textarea>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 rounded text-sm transition shadow-lg">
                        Submit Log Entry
                    </button>
                </form>
            </div>

            <div class="bg-slate-800 p-5 rounded-lg border border-slate-700 shadow-xl">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Top Sector Threat Hotspots</h3>
                <div class="space-y-2">
                    <?php if ($hotspotResult && mysqli_num_rows($hotspotResult) > 0): ?>
                        <?php while($hot = mysqli_fetch_assoc($hotspotResult)): ?>
                            <div class="flex justify-between items-center bg-slate-900 p-3 rounded border-l-4 border-amber-500">
                                <span class="font-semibold text-sm"><?php echo htmlspecialchars($hot['barangay']); ?></span>
                                <span class="bg-amber-500/10 text-amber-400 px-2 py-0.5 rounded text-xs font-bold"><?php echo $hot['total']; ?> Active Logs</span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-xs text-slate-500 italic">No geographic hotspot matrices analyzed yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="xl:col-span-2 space-y-6">
            
            <div class="bg-slate-800 p-4 rounded-lg border border-slate-700 flex flex-wrap gap-4 items-center justify-between shadow-xl">
                <form method="GET" action="dashboard.php" class="flex flex-wrap gap-3 items-center w-full sm:w-auto">
                    <input type="hidden" name="view_archived" value="<?php echo $view_archived; ?>">
                    <input type="hidden" name="stats_frame" value="<?php echo $stats_frame; ?>">
                    
                    <input type="text" name="search_barangay" value="<?php echo htmlspecialchars($search_barangay); ?>" placeholder="Filter by Sector Name..." class="bg-slate-900 border border-slate-700 rounded px-3 py-1.5 text-xs text-slate-200 placeholder-slate-600 focus:outline-none focus:border-blue-500">
                    
                    <select name="search_crime" class="bg-slate-900 border border-slate-700 rounded px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-blue-500">
                        <option value="All Crimes">All Classifications</option>
                        <?php foreach($valid_crimes as $crimeOption): ?>
                            <option value="<?php echo $crimeOption; ?>" <?php echo $search_crime === $crimeOption ? 'selected' : ''; ?>><?php echo $crimeOption; ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold px-4 py-1.5 rounded transition">
                        Apply Filter
                    </button>
                </form>

                <div class="flex space-x-2">
                    <a href="<?php echo maintainUrlFilters(1, 0, $stats_frame, $search_barangay, $search_crime); ?>" class="px-3 py-1.5 rounded text-xs font-bold transition <?php echo !$view_archived ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-400 hover:bg-slate-600'; ?>">Active Records</a>
                    <a href="<?php echo maintainUrlFilters(1, 1, $stats_frame, $search_barangay, $search_crime); ?>" class="px-3 py-1.5 rounded text-xs font-bold transition <?php echo $view_archived ? 'bg-amber-600 text-white' : 'bg-slate-700 text-slate-400 hover:bg-slate-600'; ?>">Archives Log</a>
                </div>
            </div>

            <div class="bg-slate-800 p-5 rounded-lg border border-slate-700 shadow-xl">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Live Incident Tactical GIS Map Layer</h3>
                <div id="metroManilaMapBox" class="w-full h-80 rounded border border-slate-700 z-10"></div>
                <p class="text-[11px] text-slate-500 mt-2 italic">💡 Context Optimization: Point and click directly on the grid layer above to map coordinates and parse sector structures instantly.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <div class="bg-slate-800 p-5 rounded-lg border border-slate-700 shadow-xl flex flex-col">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Crime Profiling Metrics Composition</h3>
                    <div class="h-64 relative flex items-center justify-center my-auto">
                        <canvas id="crimeCategoryPieChart"></canvas>
                    </div>
                </div>

                <div class="bg-slate-800 p-5 rounded-lg border border-slate-700 shadow-xl flex flex-col">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Dynamic Trend Velocity Metrics</h3>
                        <div class="bg-slate-900 border border-slate-700 rounded p-0.5 flex space-x-1 text-[10px]">
                            <a href="<?php echo maintainUrlFilters($current_page, $view_archived, 'weekly', $search_barangay, $search_crime); ?>" class="px-2 py-1 rounded font-semibold <?php echo $stats_frame === 'weekly' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-slate-200'; ?>">Weekly</a>
                            <a href="<?php echo maintainUrlFilters($current_page, $view_archived, 'monthly', $search_barangay, $search_crime); ?>" class="px-2 py-1 rounded font-semibold <?php echo $stats_frame === 'monthly' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-slate-200'; ?>">Monthly</a>
                            <a href="<?php echo maintainUrlFilters($current_page, $view_archived, 'yearly', $search_barangay, $search_crime); ?>" class="px-2 py-1 rounded font-semibold <?php echo $stats_frame === 'yearly' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-slate-200'; ?>">Yearly</a>
                        </div>
                    </div>
                    <div class="h-64 relative my-auto">
                        <canvas id="crimeTemporalBarChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="bg-slate-800 p-5 rounded-lg border border-slate-700 shadow-xl">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-700 pb-3 mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-emerald-400 uppercase tracking-wider">Generate & Export Operational Reports (PDF)</h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">Isolate tactical parameters inside individual matrix intervals to build standard print layouts.</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <button type="button" onclick="launchReportFilterWizard('weekly')" class="flex items-center justify-between bg-slate-900 border border-slate-700 hover:border-emerald-500/50 p-4 rounded-lg group text-left transition duration-200 shadow-md">
                        <div>
                            <span class="text-xs font-bold text-slate-200 block">Weekly Matrix Data</span>
                            <span class="text-[10px] text-slate-500 group-hover:text-slate-400">Custom 7-Day Range Profile</span>
                        </div>
                        <span class="bg-emerald-500/10 text-emerald-400 text-xs font-bold px-2.5 py-1.5 rounded border border-emerald-500/20 group-hover:bg-emerald-600 group-hover:text-white transition">📄 PDF</span>
                    </button>

                    <button type="button" onclick="launchReportFilterWizard('monthly')" class="flex items-center justify-between bg-slate-900 border border-slate-700 hover:border-emerald-500/50 p-4 rounded-lg group text-left transition duration-200 shadow-md">
                        <div>
                            <span class="text-xs font-bold text-slate-200 block">Monthly Matrix Data</span>
                            <span class="text-[10px] text-slate-500 group-hover:text-slate-400">Custom 31-Day Range Profile</span>
                        </div>
                        <span class="bg-emerald-500/10 text-emerald-400 text-xs font-bold px-2.5 py-1.5 rounded border border-emerald-500/20 group-hover:bg-emerald-600 group-hover:text-white transition">📄 PDF</span>
                    </button>

                    <button type="button" onclick="launchReportFilterWizard('yearly')" class="flex items-center justify-between bg-slate-900 border border-slate-700 hover:border-emerald-500/50 p-4 rounded-lg group text-left transition duration-200 shadow-md">
                        <div>
                            <span class="text-xs font-bold text-slate-200 block">Yearly Matrix Data</span>
                            <span class="text-[10px] text-slate-500 group-hover:text-slate-400">12-Month Tactical Scope</span>
                        </div>
                        <span class="bg-emerald-500/10 text-emerald-400 text-xs font-bold px-2.5 py-1.5 rounded border border-emerald-500/20 group-hover:bg-emerald-600 group-hover:text-white transition">📄 PDF</span>
                    </button>
                </div>
            </div>

            <div class="bg-slate-800 rounded-lg border border-slate-700 overflow-hidden shadow-xl">
                <div class="px-5 py-4 border-b border-slate-700 bg-slate-800/50 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-blue-400 uppercase tracking-wider">Active Strategic Incident Logs</h3>
                    <span class="text-xs bg-slate-900 text-slate-400 px-3 py-1 rounded border border-slate-700">
                        Showing <strong><?php echo min($total_rows, $offset + 1); ?>-<?php echo min($total_rows, $offset + $limit); ?></strong> of <strong><?php echo $total_rows; ?></strong> Logs
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-900/50 text-slate-400 border-b border-slate-700 uppercase font-semibold">
                                <th class="p-3">Location Block</th>
                                <th class="p-3">Classification Profile</th>
                                <th class="p-3">Timeline Matrix</th>
                                <th class="p-3">Threat Alert</th>
                                <th class="p-3">Notes</th>
                                <th class="p-3">Logged By</th>
                                <th class="p-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700">
                            <?php if (!empty($incidentRows)): ?>
                                <?php foreach ($incidentRows as $row): ?>
                                    <tr class="hover:bg-slate-700/30 transition cursor-pointer" onclick="recenterMapMatrix(<?php echo $row['latitude']; ?>, <?php echo $row['longitude']; ?>)">
                                        <td class="p-3 font-medium text-slate-200"><?php echo htmlspecialchars($row['barangay']); ?> 🔍</td>
                                        <td class="p-3">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider 
                                                <?php 
                                                    echo match($row['crime_type']) {
                                                        'Murder', 'Homicide' => 'bg-red-600/20 text-red-400 border border-red-500/30',
                                                        'Rape', 'Assault' => 'bg-pink-600/20 text-pink-400 border border-pink-500/30',
                                                        'Robbery', 'Theft' => 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
                                                        'Carnapping of MV', 'Carnapping of Motorcycles' => 'bg-purple-500/10 text-purple-400 border border-purple-500/20',
                                                        default => 'bg-slate-500/10 text-slate-400'
                                                    };
                                                ?>">
                                                <?php echo htmlspecialchars($row['crime_type']); ?>
                                            </span>
                                        </td>
                                        <td class="p-3 text-slate-400"><?php echo date('M d, Y • h:i A', strtotime($row['incident_date'])); ?></td>
                                        <td class="p-3">
                                            <span class="font-semibold <?php echo $row['severity'] === 'High' ? 'text-red-400' : ($row['severity'] === 'Medium' ? 'text-amber-400' : 'text-emerald-400'); ?>">
                                                ● <?php echo htmlspecialchars($row['severity']); ?>
                                            </span>
                                        </td>
                                        <td class="p-3 text-slate-400 max-w-xs truncate" title="<?php echo htmlspecialchars($row['notes']); ?>">
                                            <?php echo htmlspecialchars($row['notes']); ?>
                                        </td>
                                        <td class="p-3 font-semibold text-slate-300">
                                            👤 <?php echo htmlspecialchars($row['reported_by_name'] ?? 'System Administrator'); ?>
                                        </td>
                                        <td class="p-3 text-right space-x-2" onclick="event.stopPropagation();">
                                            <?php if ($row['is_archived'] == 0): ?>
                                                <a href="?archive_id=<?php echo $row['id']; ?>" class="text-amber-400 hover:text-amber-300 font-semibold underline transition">Archive</a>
                                            <?php else: ?>
                                                <a href="?restore_id=<?php echo $row['id']; ?>" class="text-emerald-400 hover:text-emerald-300 font-semibold underline transition">Restore</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="p-8 text-center text-slate-500 italic">No operational logs fit the parameters specified within this current query stack view.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="bg-slate-900/60 border-t border-slate-700 px-5 py-3.5 flex justify-between items-center text-xs">
                        <div class="text-slate-400">
                            Page <span class="text-slate-100 font-semibold"><?php echo $current_page; ?></span> of <span class="text-slate-400 font-semibold"><?php echo $total_pages; ?></span>
                        </div>
                        <div class="flex space-x-1">
                            <?php if ($current_page > 1): ?>
                                <a href="<?php echo maintainUrlFilters($current_page - 1, $view_archived, $stats_frame, $search_barangay, $search_crime); ?>" class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 px-3 py-1.5 rounded transition font-semibold">
                                    &larr; Previous
                                </a>
                            <?php else: ?>
                                <button class="bg-slate-800/40 text-slate-600 border border-slate-800/80 px-3 py-1.5 rounded cursor-not-allowed font-semibold" disabled>
                                    &larr; Previous
                                </button>
                            <?php endif; ?>

                            <?php 
                            $startPage = max(1, $current_page - 2);
                            $endPage = min($total_pages, $current_page + 2);
                            for ($i = $startPage; $i <= $endPage; $i++): 
                            ?>
                                <a href="<?php echo maintainUrlFilters($i, $view_archived, $stats_frame, $search_barangay, $search_crime); ?>" class="px-3 py-1.5 rounded border transition font-semibold <?php echo $i === $current_page ? 'bg-blue-600 text-white border-blue-500 shadow' : 'bg-slate-800 text-slate-300 border-slate-700 hover:bg-slate-700'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($current_page < $total_pages): ?>
                                <a href="<?php echo maintainUrlFilters($current_page + 1, $view_archived, $stats_frame, $search_barangay, $search_crime); ?>" class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 px-3 py-1.5 rounded transition font-semibold">
                                    Next &rarr;
                                </a>
                            <?php else: ?>
                                <button class="bg-slate-800/40 text-slate-600 border border-slate-800/80 px-3 py-1.5 rounded cursor-not-allowed font-semibold" disabled>
                                    Next &rarr;
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <div id="pdfFilterWizardModal" class="hidden fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center z-[100] p-4">
        <div class="bg-slate-800 border border-slate-700 rounded-lg shadow-2xl max-w-md w-full overflow-hidden">
            <div class="bg-slate-900 px-5 py-4 border-b border-slate-700 flex justify-between items-center">
                <h4 class="text-sm font-bold uppercase tracking-wider text-emerald-400">Configure PDF Report Scope</h4>
                <button type="button" onclick="closeReportFilterWizard()" class="text-slate-400 hover:text-slate-100 font-bold">&times;</button>
            </div>
            <div class="p-5 space-y-4">
                <input type="hidden" id="modal_target_frame">
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-400 mb-1">Start Timeline Date</label>
                        <input type="date" id="modal_start_date" class="w-full bg-slate-900 border border-slate-700 rounded px-3 py-1.5 text-xs focus:outline-none focus:border-blue-500 text-slate-100">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-400 mb-1">End Timeline Date</label>
                        <input type="date" id="modal_end_date" class="w-full bg-slate-900 border border-slate-700 rounded px-3 py-1.5 text-xs focus:outline-none focus:border-blue-500 text-slate-100">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-400 mb-1">Sector / Barangay Target Location</label>
                    <select id="modal_location_select" class="w-full bg-slate-900 border border-slate-700 rounded px-3 py-1.5 text-xs focus:outline-none focus:border-blue-500 text-slate-100">
                        <option value="ALL">All Active Target Sectors</option>
                        <?php 
                        mysqli_data_seek($allLocationsResult, 0);
                        while($lRow = mysqli_fetch_assoc($allLocationsResult)): 
                        ?>
                            <option value="<?php echo htmlspecialchars($lRow['barangay']); ?>"><?php echo htmlspecialchars($lRow['barangay']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-400 mb-1">Crime Classification Profile</label>
                    <select id="modal_crime_select" class="w-full bg-slate-900 border border-slate-700 rounded px-3 py-1.5 text-xs focus:outline-none focus:border-blue-500 text-slate-100">
                        <option value="ALL">All Classification Profiles</option>
                        <?php 
                        mysqli_data_seek($allCrimesResult, 0);
                        while($cRow = mysqli_fetch_assoc($allCrimesResult)): 
                        ?>
                            <option value="<?php echo htmlspecialchars($cRow['crime_type']); ?>"><?php echo htmlspecialchars($cRow['crime_type']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="bg-slate-900 px-5 py-3 border-t border-slate-700 flex justify-end space-x-2">
                <button type="button" onclick="closeReportFilterWizard()" class="px-4 py-1.5 bg-slate-700 hover:bg-slate-600 text-xs font-semibold rounded text-slate-300">Cancel</button>
                <button type="button" onclick="executePrintReportPipeline()" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-xs font-semibold rounded text-white shadow">Generate Document</button>
            </div>
        </div>
    </div>

    <div id="printable_pdf_report_container" style="display: none;"></div>

    <script>
        // TOGGLE LOGIC FOR DYNAMIC "OTHERS" SELECTION
        const crimeSelect = document.getElementById('crime_type_select');
        const customCrimeContainer = document.getElementById('custom_crime_container');
        const customCrimeInput = customCrimeContainer.querySelector('input');

        crimeSelect.addEventListener('change', function() {
            if (this.value === 'Others') {
                customCrimeContainer.classList.remove('hidden');
                customCrimeInput.setAttribute('required', 'required');
                customCrimeInput.focus();
            } else {
                customCrimeContainer.classList.add('hidden');
                customCrimeInput.removeAttribute('required');
                customCrimeInput.value = '';
            }
        });

        // MULTI-DIMENSIONAL PDF FILTER AND GENERATION ARCHITECTURE VIA DATA MATRICES
        const rawIncidentPool = <?php echo json_encode($mapIncidentRows); ?>; // Note: Uses entire unpaginated pool so PDF reports catch everything matching filters
        const mappedOperatorName = <?php echo json_encode($fullname); ?>;

        function launchReportFilterWizard(frameType) {
            document.getElementById('modal_target_frame').value = frameType;
            
            const startInput = document.getElementById('modal_start_date');
            const endInput = document.getElementById('modal_end_date');
            
            const targetEndDate = new Date();
            let targetStartDate = new Date();
            
            if (frameType === 'weekly') {
                targetStartDate.setDate(targetEndDate.getDate() - 7);
            } else if (frameType === 'monthly') {
                targetStartDate.setMonth(targetEndDate.getMonth() - 1);
            } else if (frameType === 'yearly') {
                targetStartDate.setFullYear(targetEndDate.getFullYear() - 1);
            }
            
            startInput.value = targetStartDate.toISOString().split('T')[0];
            endInput.value = targetEndDate.toISOString().split('T')[0];
            
            document.getElementById('pdfFilterWizardModal').classList.remove('hidden');
        }

        function closeReportFilterWizard() {
            document.getElementById('pdfFilterWizardModal').classList.add('hidden');
        }

        function executePrintReportPipeline() {
            const frame = document.getElementById('modal_target_frame').value;
            const startStr = document.getElementById('modal_start_date').value;
            const endStr = document.getElementById('modal_end_date').value;
            const chosenLoc = document.getElementById('modal_location_select').value;
            const chosenCrime = document.getElementById('modal_crime_select').value;

            const startTime = startStr ? new Date(startStr).setHours(0,0,0,0) : 0;
            const endTime = endStr ? new Date(endStr).setHours(23,59,59,999) : Infinity;

            const filteredCollection = rawIncidentPool.filter(item => {
                const itemTime = new Date(item.incident_date).getTime();
                if (itemTime < startTime || itemTime > endTime) return false;
                if (chosenLoc !== 'ALL' && item.barangay !== chosenLoc) return false;
                if (chosenCrime !== 'ALL' && item.crime_type !== chosenCrime) return false;
                return true;
            });

            let targetHTML = `
                <div style="padding: 20px; border-bottom: 2px solid #0f172a;">
                    <div style="text-align: center; margin-bottom: 15px;">
                        <h2 style="margin: 0; font-size: 18px; font-weight: bold; text-transform: uppercase;">Crime Mapping Command Station</h2>
                        <h3 style="margin: 4px 0 0 0; font-size: 12px; color: #475569; text-transform: uppercase;">Tactical Operations Ledger (${frame})</h3>
                    </div>
                    <table style="width: 100%; font-size: 11px; margin-top: 15px; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 3px 0;"><strong>Generated By:</strong> ${mappedOperatorName}</td>
                            <td style="text-align: right; padding: 3px 0;"><strong>Extraction Timeline:</strong> ${startStr || 'Beginning'} to ${endStr || 'Present'}</td>
                        </tr>
                        <tr>
                            <td style="padding: 3px 0;"><strong>Target Sector Scope:</strong> ${chosenLoc}</td>
                            <td style="text-align: right; padding: 3px 0;"><strong>Classification Filter:</strong> ${chosenCrime}</td>
                        </tr>
                        <tr>
                            <td style="padding: 3px 0; color: #0284c7;"><strong>Total Records Processed:</strong> ${filteredCollection.length} Match Entries</td>
                            <td style="text-align: right; padding: 3px 0;"><strong>System Date:</strong> ${new Date().toLocaleString()}</td>
                        </tr>
                    </table>
                </div>
                <div style="padding: 10px 20px;">
                    <table class="pdf-report-table">
                        <thead>
                            <tr>
                                <th style="width: 22%;">Location Block</th>
                                <th style="width: 15%;">Classification</th>
                                <th style="width: 15%;">Incident Time</th>
                                <th style="width: 10%;">Threat Level</th>
                                <th style="width: 28%;">Tactical Notes / Narrative</th>
                                <th style="width: 10%;">Logged By</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            if (filteredCollection.length > 0) {
                filteredCollection.forEach(row => {
                    const formattedDate = new Date(row.incident_date).toLocaleString('en-US', { 
                        month: 'short', day: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true 
                    });
                    targetHTML += `
                        <tr>
                            <td><strong>${row.barangay}</strong></td>
                            <td>${row.crime_type}</td>
                            <td>${formattedDate}</td>
                            <td>${row.severity}</td>
                            <td style="white-space: pre-wrap;">${row.notes || 'None'}</td>
                            <td>${row.reported_by_name || 'System'}</td>
                        </tr>
                    `;
                });
            } else {
                targetHTML += `<tr><td colspan="6" style="text-align: center; color: #64748b; font-style: italic; padding: 20px;">No operational logs tracked corresponding to selected custom matrix options.</td></tr>`;
            }

            targetHTML += `
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 40px; padding: 15px 20px; font-size: 9px; border-top: 1px solid #cbd5e1; text-align: center; color: #64748b;">
                    CRIME MAPPING SYSTEM TACTICAL REPORT • SECURITY CLASSIFIED CONTENT GENERATED ELECTRONICALLY.
                </div>
            `;

            const sandbox = document.getElementById('printable_pdf_report_container');
            sandbox.innerHTML = targetHTML;
            
            closeReportFilterWizard();
            
            setTimeout(() => {
                window.print();
                sandbox.innerHTML = ''; 
            }, 250);
        }

        // Setup Map Matrix Coordinates View
        const metroMap = L.map('metroManilaMapBox').setView([14.599512, 120.984222], 12);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19, attribution: '© OpenStreetMap parameters'
        }).addTo(metroMap);

        metroMap.on('click', function(mapEvent) {
            const lat = mapEvent.latlng.lat;
            const lng = mapEvent.latlng.lng;

            document.getElementById('lat_form_input').value = lat.toFixed(6);
            document.getElementById('lng_form_input').value = lng.toFixed(6);

            const addressField = document.getElementById('barangay_form_input');
            addressField.classList.add('resolving-text');
            addressField.value = "⏳ Resolving target location block data via GIS queries...";

            fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`)
                .then(response => response.json())
                .then(data => {
                    addressField.classList.remove('resolving-text');
                    if (data && data.address) {
                        const addr = data.address;
                        let street = addr.road || addr.building || addr.amenity || "";
                        let barangay = addr.subdistrict || addr.neighbourhood || addr.village || addr.suburb || "";
                        let municipality = addr.city || addr.town || "";

                        if (barangay && !barangay.toLowerCase().includes('barangay') && !barangay.toLowerCase().includes('brgy')) {
                            barangay = "Brgy. " + barangay;
                        }

                        let compiled = [];
                        if (street) compiled.push(street);
                        if (barangay) compiled.push(barangay);
                        if (municipality) compiled.push(municipality);

                        addressField.value = compiled.length > 0 ? compiled.join(', ') : `Zone (${lat.toFixed(4)}, ${lng.toFixed(4)})`;
                    } else {
                        addressField.value = "Unknown Sector coordinate grid point.";
                    }
                })
                .catch(() => {
                    addressField.classList.remove('resolving-text');
                    addressField.value = "Network timeout - please enter sector data manually";
                });
        });

        function recenterMapMatrix(lat, lng) {
            metroMap.setView([lat, lng], 16);
            window.scrollTo({ top: document.getElementById('metroManilaMapBox').offsetTop - 80, behavior: 'smooth' });
        }

        // Plot data array variables directly to map view (Uses full filtered list so all markers render regardless of active page)
        const incidentRowsData = <?php echo json_encode($mapIncidentRows); ?>;
        incidentRowsData.forEach(function(incident) {
            if (incident.latitude && incident.longitude) {
                const colors = { 
                    'Murder': '#dc2626', 'Homicide': '#ef4444', 'Physical Injury': '#f43f5e',
                    'Rape': '#ec4899', 'Robbery': '#f59e0b', 'Theft': '#3b82f6', 
                    'Carnapping of MV': '#a855f7', 'Carnapping of Motorcycles': '#8b5cf6', 'Others': '#64748b' 
                };
                const dotColor = colors[incident.crime_type] || '#94a3b8';

                const markerDot = L.circleMarker([incident.latitude, incident.longitude], {
                    radius: 8, fillColor: dotColor, color: '#1e293b', weight: 2, opacity: 1, fillOpacity: 0.85
                }).addTo(metroMap);

                markerDot.bindPopup(`
                    <div style="font-family: sans-serif; font-size: 12px; line-height: 1.4; color: #f8fafc;">
                        <strong style="color: ${dotColor}; font-size: 13px; text-transform: uppercase;">${incident.crime_type}</strong><br>
                        <strong>Sector:</strong> ${incident.barangay}<br>
                        <strong>Severity:</strong> ${incident.severity}<br>
                        <strong>Operator:</strong> ${incident.reported_by_name}<br>
                        <strong>Log Note:</strong> ${incident.notes || 'None'}
                    </div>
                `);
            }
        });

        // Initialize Pie Chart Engine Interface
        const categoryPieCtx = document.getElementById('crimeCategoryPieChart').getContext('2d');
        new Chart(categoryPieCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_keys($chartTypes)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($chartTypes)); ?>,
                    backgroundColor: ['#dc2626', '#ef4444', '#f43f5e', '#ec4899', '#f59e0b', '#3b82f6', '#a855f7', '#8b5cf6', '#64748b'],
                    borderWidth: 2, borderColor: '#1e293b'
                }]
            },
            options: { 
                responsive: true, maintainAspectRatio: false, 
                plugins: { legend: { position: 'right', labels: { boxWidth: 10, color: '#94a3b8', font: { size: 10 } } } } 
            }
        });

        // Initialize Dynamic Temporal Bar Chart Engine Interface
        const temporalBarCtx = document.getElementById('crimeTemporalBarChart').getContext('2d');
        new Chart(temporalBarCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($timeLabels); ?>,
                datasets: [{
                    label: 'Trend Frequency Index Density',
                    data: <?php echo json_encode($timeData); ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.75)', borderColor: '#3b82f6', borderWidth: 1
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: '#64748b', font: { size: 9 } }, grid: { display: false } },
                    y: { ticks: { color: '#64748b', font: { size: 10 }, stepSize: 1 }, grid: { color: '#334155' } }
                }
            }
        });
    </script>
</body>
</html>