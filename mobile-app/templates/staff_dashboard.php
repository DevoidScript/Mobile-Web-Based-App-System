<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    header('Location: ../index.php');
    exit;
}

$user = $_SESSION['user'];

// Get all donations for staff view
$donations = [];
$params = [
    'order' => 'created_at.desc',
    'limit' => 50
];

$result = get_records('donations', $params);
if ($result['success']) {
    $donations = $result['data'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Blood Donations</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .staff-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background-color: #FF0000;
            color: white;
            padding: 15px;
            text-align: center;
            margin-bottom: 20px;
            border-radius: 10px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #FF0000;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .donations-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            background: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .table-header h3 {
            margin: 0;
            color: #333;
        }
        
        .table-content {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-registered { background: #e3f2fd; color: #1976d2; }
        .status-sample { background: #fff3e0; color: #f57c00; }
        .status-screening { background: #e8f5e8; color: #388e3c; }
        .status-testing { background: #fce4ec; color: #c2185b; }
        .status-complete { background: #e8f5e8; color: #388e3c; }
        .status-processed { background: #e0f2f1; color: #00796b; }
        .status-ready { background: #e8f5e8; color: #388e3c; }
        
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin: 2px;
        }
        
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: #212529; }
        
        .navigation-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: #000000;
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
            box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
            z-index: 1000;
            height: 60px;
            box-sizing: border-box;
        }
        
        .nav-button {
            color: white;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 33.33%;
            padding: 5px 0;
            touch-action: manipulation;
            text-decoration: none;
        }
        
        .nav-button:active {
            opacity: 0.7;
        }
        
        .nav-icon {
            font-size: 24px;
            margin-bottom: 2px;
        }
        
        .nav-label {
            font-size: 10px;
            text-align: center;
        }
        
        .nav-button.active {
            color: #FF0000;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Staff Dashboard - Blood Donations</h1>
    </div>
    
    <div class="staff-container">
        <!-- Statistics -->
        <div class="stats-grid">
            <?php
            $total_donations = count($donations);
            $registered = 0;
            $sample_collected = 0;
            $screening = 0;
            $testing = 0;
            $testing_complete = 0;
            $processed = 0;
            $ready = 0;
            
            foreach ($donations as $donation) {
                switch ($donation['current_status']) {
                    case 'Registered': $registered++; break;
                    case 'Sample Collected': $sample_collected++; break;
                    case 'Medical Screening': $screening++; break;
                    case 'Testing': $testing++; break;
                    case 'Testing Complete': $testing_complete++; break;
                    case 'Processed': $processed++; break;
                    case 'Ready for Use': $ready++; break;
                }
            }
            ?>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_donations; ?></div>
                <div class="stat-label">Total Donations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $registered; ?></div>
                <div class="stat-label">Registered</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $sample_collected; ?></div>
                <div class="stat-label">Sample Collected</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $screening; ?></div>
                <div class="stat-label">Medical Screening</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $testing; ?></div>
                <div class="stat-label">Testing</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $ready; ?></div>
                <div class="stat-label">Ready for Use</div>
            </div>
        </div>
        
        <!-- Donations Table -->
        <div class="donations-table">
            <div class="table-header">
                <h3>Recent Donations</h3>
            </div>
            <div class="table-content">
                <table>
                    <thead>
                        <tr>
                            <th>Donor ID</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Blood Type</th>
                            <th>Units</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($donations as $donation): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($donation['donor_id']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($donation['donation_date'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $donation['current_status'])); ?>">
                                        <?php echo htmlspecialchars($donation['current_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($donation['blood_type'] ?? 'Pending'); ?></td>
                                <td><?php echo htmlspecialchars($donation['units_collected']); ?></td>
                                <td>
                                    <button class="action-btn btn-primary" onclick="viewDetails('<?php echo $donation['donation_id']; ?>')">
                                        View
                                    </button>
                                    <button class="action-btn btn-success" onclick="updateStatus('<?php echo $donation['donation_id']; ?>')">
                                        Update
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Mobile-optimized bottom navigation bar -->
    <div class="navigation-bar">
        <a href="dashboard.php" class="nav-button">
            <div class="nav-icon">🏠</div>
            <div class="nav-label">Home</div>
        </a>
        <a href="staff_dashboard.php" class="nav-button active">
            <div class="nav-icon">👨‍⚕️</div>
            <div class="nav-label">Staff</div>
        </a>
        <a href="profile.php" class="nav-button">
            <div class="nav-icon">👤</div>
            <div class="nav-label">Profile</div>
        </a>
    </div>
    
    <script>
        function viewDetails(donationId) {
            // Fetch donation details via API
            fetch(`../api/staff_tracker.php?action=get_donation_details&donation_id=${donationId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`Donation Details:\nStatus: ${data.data.current_status}\nBlood Type: ${data.data.blood_type || 'Pending'}\nUnits: ${data.data.units_collected}`);
                    } else {
                        alert('Error fetching donation details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error fetching donation details');
                });
        }
        
        function updateStatus(donationId) {
            const newStatus = prompt('Enter new status (Registered, Sample Collected, Medical Screening, Testing, Testing Complete, Processed, Ready for Use):');
            if (newStatus) {
                const formData = new FormData();
                formData.append('donation_id', donationId);
                formData.append('new_status', newStatus);
                
                fetch('../api/staff_tracker.php?action=update_stage', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Status updated successfully');
                        location.reload();
                    } else {
                        alert('Error updating status: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating status');
                });
            }
        }
        
        // Auto-refresh every 2 minutes
        setInterval(() => {
            location.reload();
        }, 120000);
    </script>
</body>
</html>
