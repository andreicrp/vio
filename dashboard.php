<?php
// dashboard.php
// VIOTRACK System - Enhanced Dashboard with Dynamic Calendar
include 'db.php';

// Handle appointment management actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_appointment') {
        $appointment_id = $_POST['appointment_id'] ?? '';
        $date = $_POST['date'];
        $title = $_POST['title'];
        $time = $_POST['time'];
        $description = $_POST['description'];
        $type = $_POST['type'];
        $status = $_POST['status'] ?? 'pending';
        
        if (empty($appointment_id)) {
            // Create new appointment
            $sql = "INSERT INTO appointments (appointment_date, title, time, description, type, status) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $date, $title, $time, $description, $type, $status);
        } else {
            // Update existing appointment
            $sql = "UPDATE appointments SET appointment_date=?, title=?, time=?, description=?, type=?, status=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssi", $date, $title, $time, $description, $type, $status, $appointment_id);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        exit;
    }
    
    if ($action === 'delete_appointment') {
        $appointment_id = $_POST['appointment_id'];
        $sql = "DELETE FROM appointments WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $appointment_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        exit;
    }
    
    if ($action === 'mark_done') {
        $appointment_id = $_POST['appointment_id'];
        $sql = "UPDATE appointments SET status = 'done' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $appointment_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        exit;
    }
    
    if ($action === 'schedule_meeting') {
        $student_id = $_POST['student_id'];
        $student_name = $_POST['student_name'];
        $meeting_date = $_POST['meeting_date'];
        $meeting_time = $_POST['meeting_time'];
        $violation_count = $_POST['violation_count'] ?? 0;
        
        $title = "Meeting: " . $student_name;
        $description = "Violation meeting with " . $student_name . " (" . $student_id . "). Current violations: " . $violation_count;
        $type = 'violation_meeting';
        $status = 'pending';
        
        $sql = "INSERT INTO appointments (appointment_date, title, time, description, type, status, student_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $meeting_date, $title, $meeting_time, $description, $type, $status, $student_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'appointment_id' => $conn->insert_id]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        exit;
    }
        $year = $_POST['year'] ?? date('Y');
        $month = $_POST['month'] ?? date('m');
        
        $sql = "SELECT * FROM appointments WHERE YEAR(appointment_date) = ? AND MONTH(appointment_date) = ? ORDER BY appointment_date, time";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $year, $month);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $appointments = [];
        while ($row = $result->fetch_assoc()) {
            $appointments[$row['appointment_date']][] = $row;
        }
        
        echo json_encode(['success' => true, 'appointments' => $appointments]);
        exit;
    }
    

// Get total students count from database
$student_count_result = $conn->query("SELECT COUNT(*) as total FROM students");
$student_count = $student_count_result->fetch_assoc()['total'];

// Get active violations count
$active_violations_result = $conn->query("SELECT COUNT(*) as total FROM violations WHERE status = 'Active'");
$active_violations_count = $active_violations_result->fetch_assoc()['total'];

// Get recent activity (violations and attendance)
$recent_activity_query = "
    SELECT 'violation' as type, v.student_id, s.name, v.violation_type as activity, 
           v.violation_date as activity_time, v.status
    FROM violations v 
    JOIN students s ON v.student_id = s.student_id 
    WHERE v.violation_date >= DATE_SUB(NOW(), INTERVAL 1 DAY)
    
    UNION ALL
    
    SELECT 'attendance' as type, a.student_id, s.name, 
           CONCAT('Attendance Check-in (', a.status, ')') as activity,
           TIMESTAMP(a.attendance_date, '09:00:00') as activity_time, 
           CASE WHEN a.status = 'Present' THEN 'Complete' ELSE 'Pending' END as status
    FROM attendance a 
    JOIN students s ON a.student_id = s.student_id 
    WHERE a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
    
    ORDER BY activity_time DESC 
    LIMIT 10";

$recent_activity_result = $conn->query($recent_activity_query);

// Create appointments table if it doesn't exist

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIOTRACK</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Modal Styles for Appointment Management */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(2px);
        }
        
        .modal-content {
            background-color: #ffffff;
            margin: 5% auto;
            padding: 30px;
            border: none;
            border-radius: 16px;
            width: 500px;
            max-width: 90%;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            animation: modalSlideIn 0.3s ease-out;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .close {
            color: #9ca3af;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
            line-height: 1;
        }
        
        .close:hover {
            color: #ef4444;
            transform: scale(1.1);
        }
        
        .modal-title {
            color: #1e293b;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            box-sizing: border-box;
            font-size: 14px;
            transition: all 0.3s ease;
            background-color: #f9fafb;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
            font-family: inherit;
        }
        
        .form-buttons {
            display: flex;
            gap: 12px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        
        .btn-modal {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 120px;
            justify-content: center;
        }
        
        .btn-save {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }
        
        .btn-delete {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        
        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(239, 68, 68, 0.3);
        }
        
        .btn-done {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
        }
        
        .btn-done:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(5, 150, 105, 0.3);
        }
        
        .btn-cancel {
            background: #f3f4f6;
            color: #6b7280;
            border: 1px solid #d1d5db;
        }
        
        .btn-cancel:hover {
            background: #e5e7eb;
            color: #4b5563;
        }
        
        /* Enhanced Calendar Styles */
        .calendar-table .appointment {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1d4ed8;
            font-weight: 600;
            position: relative;
            cursor: pointer;
        }
        
        .calendar-table .appointment:hover {
            background: linear-gradient(135deg, #bfdbfe 0%, #93c5fd 100%);
            transform: scale(1.02);
        }
        
        .calendar-table .appointment.done {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
        }
        
        .calendar-table .appointment.done:hover {
            background: linear-gradient(135deg, #a7f3d0 0%, #6ee7b7 100%);
        }
        
        .appointment-indicator {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #ef4444;
        }
        
        .appointment-indicator.done {
            background: #10b981;
        }
        
        .appointment-indicator.meeting {
            background: #f59e0b;
        }
        
        .appointment-count {
            position: absolute;
            bottom: 2px;
            right: 4px;
            font-size: 10px;
            background: rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            padding: 2px 6px;
            color: #4b5563;
        }
        
        /* Enhanced Tooltip */
        .tooltip {
            position: absolute;
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 12px;
            font-size: 13px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            max-width: 300px;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
            pointer-events: none;
        }
        
        .tooltip.show {
            opacity: 1;
            transform: translateY(0);
        }
        
        .tooltip::after {
            content: '';
            position: absolute;
            top: -8px;
            left: 50%;
            transform: translateX(-50%);
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-bottom: 8px solid #1f2937;
        }
        
        .tooltip-title {
            font-weight: 700;
            margin-bottom: 8px;
            color: #fbbf24;
            font-size: 15px;
        }
        
        .tooltip-time {
            font-size: 12px;
            color: #d1d5db;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .tooltip-description {
            font-size: 12px;
            color: #e5e7eb;
            margin-bottom: 8px;
            line-height: 1.4;
        }
        
        .tooltip-status {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 6px;
            display: inline-block;
        }
        
        .tooltip-status.pending {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
        }
        
        .tooltip-status.done {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }
        
        .tooltip-actions {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 11px;
            color: #9ca3af;
        }

        /* Add New Appointment Button */
        .add-appointment-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
            transition: all 0.3s ease;
            z-index: 999;
        }
        
        .add-appointment-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 15px 25px rgba(59, 130, 246, 0.4);
        }
    </style>
</head>
<body>
    <div class="menu-container">
        <div class="menu-header">
            <div class="logo">
                <img src="images/logo.png" alt="College Logo" class="college-logo-img">
            </div>
        </div>
        <div class="menu-items">
            <a href="#" class="menu-item active" data-page="dashboard">
                <div class="menu-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="9"/>
                        <rect x="14" y="3" width="7" height="5"/>
                        <rect x="14" y="12" width="7" height="9"/>
                        <rect x="3" y="16" width="7" height="5"/>
                    </svg>
                </div>
                <span class="menu-text">DASHBOARD</span>
            </a>
            <a href="students.php" class="menu-item" data-page="students">
                <div class="menu-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" 
                         width="22" height="22" viewBox="0 0 24 24" 
                         fill="none" stroke="currentColor" 
                         stroke-width="2" stroke-linecap="round" 
                         stroke-linejoin="round">
                        <path d="M4 10l8-4 8 4-8 4-8-4z"/>
                        <path d="M12 14v7"/>
                        <path d="M6 12v5c0 1 2 2 6 2s6-1 6-2v-5"/>
                    </svg>
                </div>
                <span class="menu-text">STUDENTS</span>
            </a>
            <a href="violations.php" class="menu-item" data-page="violations">
                <div class="menu-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                        <path d="M12 9v4"/>
                        <path d="m12 17 .01 0"/>
                    </svg>
                </div>
                <span class="menu-text">VIOLATIONS</span>
            </a>
            <a href="track.php" class="menu-item" data-page="track">
                <div class="menu-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                </div>
                <span class="menu-text">TRACK LOCATION</span>
            </a>
            <a href="reports.php" class="menu-item" data-page="reports">
                <div class="menu-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2-2V7.5L14.5 2z"/>
                        <polyline points="14,2 14,8 20,8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10,9 9,9 8,9"/>
                    </svg>
                </div>
                <span class="menu-text">REPORTS</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div id="dashboard" class="page active">
            <h1 class="page-header">Dashboard</h1>
            
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-header">Total Students</div>
                    <div class="stat-number"><?php echo number_format($student_count); ?></div>
                    <div class="stat-icon">👥</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">Active Violations</div>
                    <div class="stat-number"><?php echo number_format($active_violations_count); ?></div>
                    <div class="stat-icon">⚠️</div>
                </div>
            </div>

            <div class="content-row">
                <div class="activity-section">
                    <div class="section-header">Recent Activity</div>
                    <table class="activity-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Student</th>
                                <th>Activity</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_activity_result->num_rows > 0): ?>
                                <?php while($row = $recent_activity_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('h:i A', strtotime($row['activity_time'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['name']) . ' (' . htmlspecialchars($row['student_id']) . ')'; ?></td>
                                    <td><?php echo htmlspecialchars($row['activity']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php 
                                            echo $row['type'] === 'violation' ? 
                                                ($row['status'] === 'Active' ? 'pending' : 'resolved') : 
                                                ($row['status'] === 'Complete' ? 'active' : 'pending'); 
                                        ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #64748b;">No recent activity</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="chart-section">
                    <div class="section-header">Violation Types</div>
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        <div class="donut-chart"></div>
                        <div class="legend">
                            <div class="legend-item">
                                <div class="legend-color color-minor"></div>
                                <span>Minor Offenses</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color color-serious"></div>
                                <span>Serious Offenses</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color color-major"></div>
                                <span>Major Offenses</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Calendar Card -->
            <div class="calendar-section">
                <div class="section-header">Academic Calendar & Appointments</div>
                
                <div class="calendar-navigation">
                    <button class="calendar-nav-btn" id="prevMonth">← Previous</button>
                    <div class="current-month" id="currentMonth"></div>
                    <button class="calendar-nav-btn" id="nextMonth">Next →</button>
                </div>

                <div class="calendar-card">
                    <table class="calendar-table" id="calendarTable">
                        <thead>
                            <tr>
                                <th>Sun</th>
                                <th>Mon</th>
                                <th>Tue</th>
                                <th>Wed</th>
                                <th>Thu</th>
                                <th>Fri</th>
                                <th>Sat</th>
                            </tr>
                        </thead>
                        <tbody id="calendarBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Tooltip -->
    <div id="tooltip" class="tooltip">
        <div class="tooltip-title"></div>
        <div class="tooltip-time"></div>
        <div class="tooltip-description"></div>
        <div class="tooltip-status"></div>
        <div class="tooltip-actions">Click to edit • Right-click for options</div>
    </div>

    <!-- Appointment Modal -->
    <div id="appointmentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAppointmentModal()">&times;</span>
            <h2 class="modal-title">
                <span id="modalIcon">📅</span>
                <span id="modalTitle">Edit Appointment</span>
            </h2>
            <form id="appointmentForm">
                <input type="hidden" id="appointmentId" name="appointment_id">
                <input type="hidden" id="appointmentDate" name="date">
                
                <div class="form-group">
                    <label for="appointmentTitle">Title:</label>
                    <input type="text" id="appointmentTitle" name="title" required placeholder="Enter appointment title...">
                </div>
                
                <div class="form-group">
                    <label for="appointmentTime">Time:</label>
                    <input type="time" id="appointmentTime" name="time" required>
                </div>
                
                <div class="form-group">
                    <label for="appointmentDescription">Description:</label>
                    <textarea id="appointmentDescription" name="description" placeholder="Enter description..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="appointmentType">Type:</label>
                    <select id="appointmentType" name="type">
                        <option value="meeting">📅 Meeting</option>
                        <option value="violation_meeting">⚠️ Violation Meeting</option>
                        <option value="event">🎉 Event</option>
                        <option value="academic">📚 Academic</option>
                        <option value="holiday">🏖️ Holiday</option>
                    </select>
                </div>
                
                <div class="form-buttons">
                    <button type="button" class="btn-modal btn-save" onclick="saveAppointment()">
                        💾 Save
                    </button>
                    <button type="button" class="btn-modal btn-done" onclick="markAsDone()">
                        ✅ Mark Done
                    </button>
                    <button type="button" class="btn-modal btn-delete" onclick="deleteAppointment()">
                        🗑️ Delete
                    </button>
                    <button type="button" class="btn-modal btn-cancel" onclick="closeAppointmentModal()">
                        ❌ Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Appointment Button -->
    <button class="add-appointment-btn" onclick="openNewAppointmentModal()" title="Add New Appointment">
        ➕
    </button>

    <script>
        // Enhanced Calendar System with Database Integration
        class CalendarSystem {
            constructor() {
                this.currentDate = new Date();
                this.currentMonth = this.currentDate.getMonth();
                this.currentYear = this.currentDate.getFullYear();
                this.appointments = {};
                this.tooltip = document.getElementById('tooltip');
                this.selectedDate = null;
                this.selectedAppointment = null;
                
                this.init();
            }

            init() {
                this.loadAppointments();
                this.attachEventListeners();
            }

            async loadAppointments() {
                try {
                    const formData = new FormData();
                    formData.append('action', 'get_appointments');
                    formData.append('year', this.currentYear);
                    formData.append('month', this.currentMonth + 1);

                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();
                    if (data.success) {
                        this.appointments = data.appointments;
                        this.render();
                    }
                } catch (error) {
                    console.error('Error loading appointments:', error);
                    this.render(); // Render empty calendar
                }
            }

            attachEventListeners() {
                document.getElementById('prevMonth').addEventListener('click', () => this.previousMonth());
                document.getElementById('nextMonth').addEventListener('click', () => this.nextMonth());
            }

            previousMonth() {
                this.currentMonth--;
                if (this.currentMonth < 0) {
                    this.currentMonth = 11;
                    this.currentYear--;
                }
                this.loadAppointments();
            }

            nextMonth() {
                this.currentMonth++;
                if (this.currentMonth > 11) {
                    this.currentMonth = 0;
                    this.currentYear++;
                }
                this.loadAppointments();
            }

            render() {
                const monthNames = [
                    'January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December'
                ];

                document.getElementById('currentMonth').textContent = 
                    `${monthNames[this.currentMonth]} ${this.currentYear}`;

                this.renderCalendarDays();
            }

            renderCalendarDays() {
                const calendarBody = document.getElementById('calendarBody');
                const firstDay = new Date(this.currentYear, this.currentMonth, 1);
                const lastDay = new Date(this.currentYear, this.currentMonth + 1, 0);
                const daysInMonth = lastDay.getDate();
                const startingDay = firstDay.getDay();

                calendarBody.innerHTML = '';

                let date = 1;
                for (let i = 0; i < 6; i++) {
                    const row = document.createElement('tr');

                    for (let j = 0; j < 7; j++) {
                        const cell = document.createElement('td');
                        
                        if (i === 0 && j < startingDay) {
                            const prevMonth = this.currentMonth === 0 ? 11 : this.currentMonth - 1;
                            const prevYear = this.currentMonth === 0 ? this.currentYear - 1 : this.currentYear;
                            const prevMonthDays = new Date(prevYear, prevMonth + 1, 0).getDate();
                            const prevDate = prevMonthDays - (startingDay - j - 1);
                            
                            cell.textContent = prevDate;
                            cell.classList.add('other-month');
                        } else if (date > daysInMonth) {
                            const nextDate = date - daysInMonth;
                            cell.textContent = nextDate;
                            cell.classList.add('other-month');
                            date++;
                        } else {
                            cell.textContent = date;
                            
                            const dateKey = `${this.currentYear}-${String(this.currentMonth + 1).padStart(2, '0')}-${String(date).padStart(2, '0')}`;
                            
                            if (this.appointments[dateKey]) {
                                const appointments = this.appointments[dateKey];
                                const firstAppointment = appointments[0];
                                
                                cell.classList.add('appointment');
                                if (firstAppointment.status === 'done') {
                                    cell.classList.add('done');
                                }
                                
                                const indicator = document.createElement('div');
                                indicator.classList.add('appointment-indicator');
                                if (firstAppointment.status === 'done') {
                                    indicator.classList.add('done');
                                } else if (firstAppointment.type === 'violation_meeting') {
                                    indicator.classList.add('meeting');
                                }
                                cell.appendChild(indicator);
                                
                                // Show count if multiple appointments
                                if (appointments.length > 1) {
                                    const count = document.createElement('div');
                                    count.classList.add('appointment-count');
                                    count.textContent = appointments.length;
                                    cell.appendChild(count);
                                }
                                
                                this.addAppointmentEvents(cell, dateKey, appointments);
                            }
                            
                            // Add double-click to create new appointment
                            cell.addEventListener('dblclick', () => {
                                if (!cell.classList.contains('other-month')) {
                                    this.openNewAppointmentModal(dateKey);
                                }
                            });
                            
                            date++;
                        }
                        
                        row.appendChild(cell);
                    }
                    
                    calendarBody.appendChild(row);
                    
                    if (date > daysInMonth && i > 3) break;
                }
            }

            addAppointmentEvents(cell, dateKey, appointments) {
                cell.addEventListener('mouseenter', (e) => {
                    this.showTooltip(e, appointments);
                });

                cell.addEventListener('mouseleave', () => {
                    this.hideTooltip();
                });

                cell.addEventListener('mousemove', (e) => {
                    this.updateTooltipPosition(e);
                });

                cell.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.openAppointmentModal(appointments[0], dateKey);
                });

                cell.addEventListener('contextmenu', (e) => {
                    e.preventDefault();
                    this.showContextMenu(e, appointments, dateKey);
                });
            }

            showTooltip(event, appointments) {
                const tooltip = this.tooltip;
                const appointment = appointments[0];
                
                tooltip.querySelector('.tooltip-title').textContent = appointment.title;
                tooltip.querySelector('.tooltip-time').innerHTML = `⏰ ${appointment.time}`;
                tooltip.querySelector('.tooltip-description').textContent = appointment.description || 'No description';
                
                const statusElement = tooltip.querySelector('.tooltip-status');
                statusElement.textContent = appointment.status;
                statusElement.className = `tooltip-status ${appointment.status}`;
                
                if (appointments.length > 1) {
                    tooltip.querySelector('.tooltip-description').textContent += ` (+${appointments.length - 1} more)`;
                }
                
                tooltip.classList.add('show');
                this.updateTooltipPosition(event);
            }

            hideTooltip() {
                this.tooltip.classList.remove('show');
            }

            updateTooltipPosition(event) {
                const tooltip = this.tooltip;
                const rect = tooltip.getBoundingClientRect();
                const viewportWidth = window.innerWidth;
                const viewportHeight = window.innerHeight;
                
                let left = event.pageX + 15;
                let top = event.pageY - rect.height - 15;
                
                if (left + rect.width > viewportWidth) {
                    left = event.pageX - rect.width - 15;
                }
                
                if (top < 0) {
                    top = event.pageY + 15;
                }
                
                tooltip.style.left = `${left}px`;
                tooltip.style.top = `${top}px`;
            }

            openAppointmentModal(appointment, dateKey) {
                const modal = document.getElementById('appointmentModal');
                const form = document.getElementById('appointmentForm');
                
                // Populate form
                document.getElementById('appointmentId').value = appointment.id;
                document.getElementById('appointmentDate').value = dateKey;
                document.getElementById('appointmentTitle').value = appointment.title;
                document.getElementById('appointmentTime').value = appointment.time;
                document.getElementById('appointmentDescription').value = appointment.description || '';
                document.getElementById('appointmentType').value = appointment.type;
                
                // Update modal title and icon
                const typeIcons = {
                    'meeting': '📅',
                    'violation_meeting': '⚠️',
                    'event': '🎉',
                    'academic': '📚',
                    'holiday': '🏖️'
                };
                
                document.getElementById('modalIcon').textContent = typeIcons[appointment.type] || '📅';
                document.getElementById('modalTitle').textContent = 'Edit Appointment';
                
                modal.style.display = 'block';
                this.selectedAppointment = appointment;
                this.selectedDate = dateKey;
            }

            openNewAppointmentModal(dateKey = null) {
                const modal = document.getElementById('appointmentModal');
                const form = document.getElementById('appointmentForm');
                
                // Clear form
                form.reset();
                document.getElementById('appointmentId').value = '';
                
                // Set date
                if (dateKey) {
                    document.getElementById('appointmentDate').value = dateKey;
                } else {
                    const today = new Date();
                    const todayStr = today.getFullYear() + '-' + 
                                  String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                                  String(today.getDate()).padStart(2, '0');
                    document.getElementById('appointmentDate').value = todayStr;
                }
                
                // Set default time
                document.getElementById('appointmentTime').value = '09:00';
                
                // Update modal title
                document.getElementById('modalIcon').textContent = '📅';
                document.getElementById('modalTitle').textContent = 'New Appointment';
                
                modal.style.display = 'block';
                this.selectedAppointment = null;
                this.selectedDate = dateKey;
            }
        }

        // Modal Functions
        function closeAppointmentModal() {
            document.getElementById('appointmentModal').style.display = 'none';
        }

        function openNewAppointmentModal() {
            calendar.openNewAppointmentModal();
        }

        async function saveAppointment() {
            const form = document.getElementById('appointmentForm');
            const formData = new FormData(form);
            formData.append('action', 'save_appointment');
            formData.append('status', 'pending');

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    closeAppointmentModal();
                    calendar.loadAppointments();
                    showNotification('✅ Appointment saved successfully!', 'success');
                } else {
                    showNotification('❌ Error saving appointment: ' + (data.error || 'Unknown error'), 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('❌ Network error saving appointment', 'error');
            }
        }

        async function deleteAppointment() {
            const appointmentId = document.getElementById('appointmentId').value;
            if (!appointmentId) {
                showNotification('❌ No appointment selected', 'error');
                return;
            }

            if (!confirm('Are you sure you want to delete this appointment?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete_appointment');
            formData.append('appointment_id', appointmentId);

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    closeAppointmentModal();
                    calendar.loadAppointments();
                    showNotification('✅ Appointment deleted successfully!', 'success');
                } else {
                    showNotification('❌ Error deleting appointment: ' + (data.error || 'Unknown error'), 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('❌ Network error deleting appointment', 'error');
            }
        }

        async function markAsDone() {
            const appointmentId = document.getElementById('appointmentId').value;
            if (!appointmentId) {
                showNotification('❌ No appointment selected', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'mark_done');
            formData.append('appointment_id', appointmentId);

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    closeAppointmentModal();
                    calendar.loadAppointments();
                    showNotification('✅ Appointment marked as done!', 'success');
                } else {
                    showNotification('❌ Error updating appointment: ' + (data.error || 'Unknown error'), 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('❌ Network error updating appointment', 'error');
            }
        }

        // Notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 10px;
                color: white;
                font-weight: 600;
                z-index: 10000;
                animation: slideIn 0.3s ease-out;
                max-width: 300px;
                word-wrap: break-word;
            `;

            if (type === 'success') {
                notification.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
            } else if (type === 'error') {
                notification.style.background = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
            } else {
                notification.style.background = 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)';
            }

            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateX(100%);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }
            @keyframes slideOut {
                from {
                    opacity: 1;
                    transform: translateX(0);
                }
                to {
                    opacity: 0;
                    transform: translateX(100%);
                }
            }
        `;
        document.head.appendChild(style);

        // Navigation system
        function showPage(pageId) {
            document.querySelectorAll('.page').forEach(page => {
                page.classList.remove('active');
            });
            
            document.getElementById(pageId).classList.add('active');
            
            document.querySelectorAll('.menu-item').forEach(item => {
                item.classList.remove('active');
            });
            
            document.querySelector(`[data-page="${pageId}"]`).classList.add('active');
            
            const pageTitles = {
                dashboard: 'VIOTRACK',
                students: 'Student Management - PHC',
                violations: 'Violation Management - PHC',
                track: 'Track Location - PHC',
                reports: 'Reports & Analytics - PHC'
            };
            
            document.title = pageTitles[pageId] || 'PHC System';
        }

        // Global calendar instance
        let calendar;

        // Initialize application
        document.addEventListener('DOMContentLoaded', function() {
            const activePage = document.querySelector('.page.active');
            if (!activePage) {
                showPage('dashboard');
            }
            
            // Initialize calendar
            calendar = new CalendarSystem();
            
            // Add click event listeners to menu items
            document.querySelectorAll('.menu-item').forEach(item => {
                item.addEventListener('click', function() {
                    const pageId = this.getAttribute('data-page');
                    if (pageId) {
                        showPage(pageId);
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                });
            });

            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                const modal = document.getElementById('appointmentModal');
                if (event.target === modal) {
                    closeAppointmentModal();
                }
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeAppointmentModal();
                }
                
                // Navigation shortcuts
                const menuItems = document.querySelectorAll('.menu-item');
                const currentActive = document.querySelector('.menu-item.active');
                const currentIndex = Array.from(menuItems).indexOf(currentActive);
                
                if (e.key === 'ArrowDown' && currentIndex < menuItems.length - 1) {
                    e.preventDefault();
                    const nextItem = menuItems[currentIndex + 1];
                    const pageId = nextItem.getAttribute('data-page');
                    showPage(pageId);
                } else if (e.key === 'ArrowUp' && currentIndex > 0) {
                    e.preventDefault();
                    const prevItem = menuItems[currentIndex - 1];
                    const pageId = prevItem.getAttribute('data-page');
                    showPage(pageId);
                }
            });
        });

        // Function to add appointment from violations page
        window.addViolationMeeting = function(studentName, studentId, violationCount) {
            const today = new Date();
            const dateStr = today.getFullYear() + '-' + 
                          String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                          String(today.getDate()).padStart(2, '0');
            
            calendar.openNewAppointmentModal(dateStr);
            
            // Pre-fill form with violation meeting data
            setTimeout(() => {
                document.getElementById('appointmentTitle').value = `Meeting: ${studentName}`;
                document.getElementById('appointmentDescription').value = `Violation meeting with ${studentName} (${studentId}). Current violations: ${violationCount}`;
                document.getElementById('appointmentType').value = 'violation_meeting';
            }, 100);
        };
    </script>
</body>
</html>
