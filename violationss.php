<?php
// violationss.php
// Enhanced Student Violations Tracker with Database Integration
include 'db.php';

// Get student ID from QR scan or URL parameter
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';
$student_data = null;
$current_violations = [];
$attendance_records = [];

// Fetch student data if student_id is provided
if (!empty($student_id)) {
    $student_result = $conn->query("SELECT * FROM students WHERE student_id = '$student_id' LIMIT 1");
    if ($student_result->num_rows > 0) {
        $student_data = $student_result->fetch_assoc();
        
        // Fetch current active violations
        $violations_result = $conn->query("SELECT * FROM violations WHERE student_id = '$student_id' AND status = 'Active' ORDER BY violation_date DESC");
        while ($row = $violations_result->fetch_assoc()) {
            $current_violations[] = $row;
        }
        
        // Fetch recent attendance (last 5 records)
        $attendance_result = $conn->query("SELECT * FROM attendance WHERE student_id = '$student_id' ORDER BY attendance_date DESC LIMIT 5");
        while ($row = $attendance_result->fetch_assoc()) {
            $attendance_records[] = $row;
        }
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'record_violations' && !empty($student_id)) {
        $violations = json_decode($_POST['violations'], true);
        $recorded_by = $_POST['recorded_by'] ?? 'System';
        
        $success_count = 0;
        foreach ($violations as $violation) {
            $violation_text = $conn->real_escape_string($violation['text']);
            $category = $conn->real_escape_string($violation['category']);
            $notes = $conn->real_escape_string($violation['notes'] ?? '');
            
            $sql = "INSERT INTO violations (student_id, violation_type, violation_category, recorded_by, notes) 
                    VALUES ('$student_id', '$violation_text', '$category', '$recorded_by', '$notes')";
            
            if ($conn->query($sql)) {
                $success_count++;
            }
        }
        
        echo json_encode(['success' => true, 'count' => $success_count]);
        exit;
    }
    
    if ($action === 'record_attendance' && !empty($student_id)) {
        $status = $_POST['status'];
        $date = date('Y-m-d');
        
        // Check if attendance already exists for today
        $check_sql = "SELECT id FROM attendance WHERE student_id = '$student_id' AND attendance_date = '$date'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            // Update existing record
            $sql = "UPDATE attendance SET status = '$status' WHERE student_id = '$student_id' AND attendance_date = '$date'";
        } else {
            // Insert new record
            $sql = "INSERT INTO attendance (student_id, attendance_date, status) VALUES ('$student_id', '$date', '$status')";
        }
        
        $success = $conn->query($sql);
        echo json_encode(['success' => $success]);
        exit;
    }
    
    if ($action === 'resolve_violation' && !empty($student_id)) {
        $violation_id = $_POST['violation_id'];
        $sql = "UPDATE violations SET status = 'Resolved' WHERE id = '$violation_id' AND student_id = '$student_id'";
        $success = $conn->query($sql);
        echo json_encode(['success' => $success]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VIOTRACK - Student Violations Tracker</title>
  <link rel="stylesheet" href="violations.css">
  <style>
    .student-not-found {
        text-align: center;
        padding: 50px 20px;
        background: white;
        border-radius: 12px;
        margin: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .student-not-found h2 {
        color: #dc3545;
        margin-bottom: 20px;
    }
    
    .manual-entry {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-top: 20px;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    
    .form-group input,
    .form-group select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
    }
    
    .attendance-history {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        padding: 15px;
        margin-top: 15px;
        border-radius: 8px;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .attendance-record {
        display: flex;
        justify-content: space-between;
        padding: 5px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        font-size: 0.9rem;
    }
    
    .attendance-record:last-child {
        border-bottom: none;
    }
    
    .loading {
        text-align: center;
        padding: 20px;
        color: #666;
    }
    
    .success-message {
        background: #d4edda;
        color: #155724;
        padding: 10px;
        border-radius: 4px;
        margin: 10px 0;
        border: 1px solid #c3e6cb;
    }
    
    .error-message {
        background: #f8d7da;
        color: #721c24;
        padding: 10px;
        border-radius: 4px;
        margin: 10px 0;
        border: 1px solid #f5c6cb;
    }
  </style>
</head>
<body>
  <div class="content">
    <header class="page-header">
      <h1>📋 Student Violations Tracker</h1>
    </header>
    
    <?php if (!$student_data && !empty($student_id)): ?>
        <div class="student-not-found">
            <h2>⚠️ Student Not Found</h2>
            <p>Student ID "<?php echo htmlspecialchars($student_id); ?>" was not found in the database.</p>
            <div class="manual-entry">
                <h3>Manual Student ID Entry</h3>
                <div class="form-group">
                    <label for="manual_student_id">Enter Student ID:</label>
                    <input type="text" id="manual_student_id" placeholder="Enter student ID...">
                    <button class="btn btn-primary" onclick="searchStudent()" style="margin-top: 10px;">Search Student</button>
                </div>
            </div>
        </div>
    <?php elseif (!$student_data): ?>
        <div class="student-not-found">
            <h2>🔍 No Student Selected</h2>
            <p>Please scan a QR code or enter a student ID to begin tracking violations.</p>
            <div class="manual-entry">
                <h3>Manual Student ID Entry</h3>
                <div class="form-group">
                    <label for="manual_student_id">Enter Student ID:</label>
                    <input type="text" id="manual_student_id" placeholder="Enter student ID...">
                    <button class="btn btn-primary" onclick="searchStudent()" style="margin-top: 10px;">Search Student</button>
                </div>
            </div>
        </div>
    <?php else: ?>
    <div class="container">
      <div class="left-panel">
        <!-- Profile Card -->
        <div class="profile-card">
          <div class="profile-img"><?php echo strtoupper(substr($student_data['name'], 0, 1)); ?></div>
          <div class="profile-name"><?php echo htmlspecialchars($student_data['name']); ?></div>
          <div class="student-info">
            ID: <?php echo htmlspecialchars($student_data['student_id']); ?><br>
            <?php echo htmlspecialchars($student_data['grade']); ?> - <?php echo htmlspecialchars($student_data['section']); ?><br>
            Status: <span class="status-badge status-<?php echo strtolower($student_data['status']); ?>">
              <?php echo htmlspecialchars($student_data['status']); ?>
            </span>
          </div>
        </div>

        <!-- Current Violations Card -->
        <div class="current-violations-card">
          <h3>📋 Current Violations (<?php echo count($current_violations); ?>)</h3>
          <div id="currentViolationsList">
            <?php if (empty($current_violations)): ?>
                <div class="no-violations">No Active Violations</div>
            <?php else: ?>
                <?php foreach ($current_violations as $violation): ?>
                <div class="current-violation-item">
                    <div class="violation-info">
                        <div class="violation-text"><?php echo htmlspecialchars($violation['violation_type']); ?></div>
                        <div class="violation-date">
                            <?php echo date('M j, Y g:i A', strtotime($violation['violation_date'])); ?>
                            <span style="color: #<?php echo $violation['violation_category'] == 'Minor' ? '059669' : ($violation['violation_category'] == 'Serious' ? 'eab308' : 'ef4444'); ?>;">
                                (<?php echo $violation['violation_category']; ?>)
                            </span>
                        </div>
                    </div>
                    <button class="remove-current-btn" onclick="resolveViolation(<?php echo $violation['id']; ?>)">✓</button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
          </div>
          
          <div class="action-buttons">
            <div class="attendance-buttons">
              <button class="attendance-btn present" id="presentBtn">✓ Present</button>
              <button class="attendance-btn absent" id="absentBtn">✗ Absent</button>
            </div>
            
            <?php if (!empty($attendance_records)): ?>
            <div class="attendance-history">
                <h4>Recent Attendance</h4>
                <?php foreach (array_slice($attendance_records, 0, 3) as $record): ?>
                <div class="attendance-record">
                    <span><?php echo date('M j', strtotime($record['attendance_date'])); ?></span>
                    <span class="status-badge status-<?php echo strtolower($record['status']); ?>">
                        <?php echo $record['status']; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <div class="action-buttons-row">
              <button class="action-btn print-btn" id="printBtn">🖨️ Print</button>
              <button class="action-btn meeting-btn" id="meetingBtn">📅 Schedule Meeting</button>
            </div>
          </div>
        </div>
      </div>

      <div class="violations-panel">
        <div class="category minor">
          <button class="category-btn">
            🟢 Minor Offenses
            <span class="arrow">▼</span>
          </button>
          <div class="violations-list">
            <div class="violation-item" data-category="Minor">Uniform not worn or worn improperly</div>
            <div class="violation-item" data-category="Minor">School ID not worn properly</div>
            <div class="violation-item" data-category="Minor">Playing non-educational games</div>
            <div class="violation-item" data-category="Minor">Sleeping during class or activities</div>
            <div class="violation-item" data-category="Minor">Borrowing or lending money</div>
            <div class="violation-item" data-category="Minor">Loitering or shouting in hallways</div>
            <div class="violation-item" data-category="Minor">Leaving classroom messy</div>
            <div class="violation-item" data-category="Minor">Use of mobile gadgets without permission</div>
            <div class="violation-item" data-category="Minor">Excessive jewelry or inappropriate appearance</div>
            <div class="violation-item" data-category="Minor">Wearing inappropriate clothing</div>
            <div class="violation-item" data-category="Minor">Using vulgar language</div>
            <div class="violation-item" data-category="Minor">Minor bullying or teasing</div>
            <div class="violation-item" data-category="Minor">Fighting or causing disturbances</div>
            <div class="violation-item" data-category="Minor">Horseplay inside classroom</div>
            <div class="violation-item" data-category="Minor">Making inappropriate jokes</div>
            <div class="violation-item" data-category="Minor">Not completing clearance requirements</div>
            <div class="violation-item" data-category="Minor">Taking food without consent</div>
            <div class="violation-item" data-category="Minor">Not joining required school activities</div>
            <div class="violation-item" data-category="Minor">Chewing gum or spitting in public</div>
            <div class="violation-item" data-category="Minor">Buying food during class hours</div>
            <div class="violation-item" data-category="Minor">Using school properties without permission</div>
            <div class="violation-item" data-category="Minor">Forging parent's signature</div>
            <div class="violation-item" data-category="Minor">Misbehavior in the canteen</div>
            <div class="violation-item" data-category="Minor">Disturbing class sessions</div>
            <div class="violation-item" data-category="Minor">Unauthorized playing in the gym</div>
            <div class="violation-item" data-category="Minor">Other similar minor offenses</div>
          </div>
        </div>

        <div class="category serious">
          <button class="category-btn">
            🟡 Serious Offenses
            <span class="arrow">▼</span>
          </button>
          <div class="violations-list">
            <div class="violation-item" data-category="Serious">Cheating or academic dishonesty</div>
            <div class="violation-item" data-category="Serious">Plagiarism</div>
            <div class="violation-item" data-category="Serious">Rough or dangerous play</div>
            <div class="violation-item" data-category="Serious">Lying or misleading statements</div>
            <div class="violation-item" data-category="Serious">Possession of smoking items</div>
            <div class="violation-item" data-category="Serious">Vulgar or malicious acts</div>
            <div class="violation-item" data-category="Serious">Bribery or dishonest acts</div>
            <div class="violation-item" data-category="Serious">Theft or aiding theft</div>
            <div class="violation-item" data-category="Serious">Gambling or unauthorized collection</div>
            <div class="violation-item" data-category="Serious">Not delivering official communications</div>
            <div class="violation-item" data-category="Serious">Rude behavior to visitors or parents</div>
            <div class="violation-item" data-category="Serious">Disrespect to school authorities</div>
            <div class="violation-item" data-category="Serious">Use of vulgar language repeatedly</div>
            <div class="violation-item" data-category="Serious">Causing chaos during events</div>
            <div class="violation-item" data-category="Serious">Disrespect during flag ceremonies</div>
            <div class="violation-item" data-category="Serious">Property damage from mischief</div>
            <div class="violation-item" data-category="Serious">Skipping class / truancy</div>
            <div class="violation-item" data-category="Serious">Entering/exiting without permission</div>
            <div class="violation-item" data-category="Serious">Public display of affection</div>
            <div class="violation-item" data-category="Serious">Minor vandalism</div>
            <div class="violation-item" data-category="Serious">Posting unauthorized notices</div>
            <div class="violation-item" data-category="Serious">Posting offensive content online</div>
            <div class="violation-item" data-category="Serious">Removing school announcements</div>
            <div class="violation-item" data-category="Serious">Gang-like behavior</div>
            <div class="violation-item" data-category="Serious">Cyberbullying</div>
            <div class="violation-item" data-category="Serious">Gross misconduct</div>
          </div>
        </div>

        <div class="category major">
          <button class="category-btn">
            🔴 Major Offenses
            <span class="arrow">▼</span>
          </button>
          <div class="violations-list">
            <div class="violation-item" data-category="Major">Forgery or document tampering</div>
            <div class="violation-item" data-category="Major">Using fake receipts or forms</div>
            <div class="violation-item" data-category="Major">Major vandalism</div>
            <div class="violation-item" data-category="Major">Physical assault</div>
            <div class="violation-item" data-category="Major">Stealing school or class funds</div>
            <div class="violation-item" data-category="Major">Extortion or blackmail</div>
            <div class="violation-item" data-category="Major">Immoral or scandalous behavior</div>
            <div class="violation-item" data-category="Major">Possessing pornography</div>
            <div class="violation-item" data-category="Major">Criminal charges or conviction</div>
            <div class="violation-item" data-category="Major">Harassment of students/staff</div>
            <div class="violation-item" data-category="Major">Causing injury requiring hospitalization</div>
            <div class="violation-item" data-category="Major">Sexual harassment</div>
            <div class="violation-item" data-category="Major">Lewd or immoral acts</div>
            <div class="violation-item" data-category="Major">Inappropriate social media use</div>
            <div class="violation-item" data-category="Major">Leaking confidential school info</div>
            <div class="violation-item" data-category="Major">Misusing school logo or name</div>
            <div class="violation-item" data-category="Major">Bribing school officials</div>
            <div class="violation-item" data-category="Major">Drinking alcohol in school</div>
            <div class="violation-item" data-category="Major">Drug-related activities</div>
            <div class="violation-item" data-category="Major">Bringing deadly weapons</div>
            <div class="violation-item" data-category="Major">Joining fraternities/sororities</div>
          </div>
        </div>

        <div class="selected-section">
          <h3>📝 Selected Violations</h3>
          <div id="selectedList"></div>
          <button class="record-btn" id="recordBtn">RECORD VIOLATIONS</button>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <script>
    const studentId = '<?php echo $student_data ? $student_data['student_id'] : ''; ?>';
    let selectedViolations = [];
    let attendanceStatus = null;

    // Initialize event listeners
    document.addEventListener('DOMContentLoaded', function() {
        if (studentId) {
            document.getElementById('recordBtn').addEventListener('click', recordViolations);
            document.getElementById('presentBtn').addEventListener('click', () => setAttendance('Present'));
            document.getElementById('absentBtn').addEventListener('click', () => setAttendance('Absent'));
            document.getElementById('printBtn').addEventListener('click', printRecord);
            document.getElementById('meetingBtn').addEventListener('click', scheduleMeeting);

            // Add event listeners for category buttons
            document.querySelectorAll('.category-btn').forEach(btn => {
                btn.addEventListener('click', () => toggleCategory(btn));
            });

            // Add event listeners for violation items
            document.querySelectorAll('.violation-item').forEach(item => {
                item.addEventListener('click', () => addViolation(item));
            });
        }
    });

    function searchStudent() {
        const studentId = document.getElementById('manual_student_id').value.trim();
        if (studentId) {
            window.location.href = `violationss.php?student_id=${encodeURIComponent(studentId)}`;
        } else {
            alert('Please enter a student ID');
        }
    }

    function toggleCategory(btn) {
        btn.parentElement.classList.toggle('open');
        const arrow = btn.querySelector('.arrow');
        arrow.textContent = btn.parentElement.classList.contains('open') ? '▲' : '▼';
    }

    function addViolation(item) {
        const text = item.textContent.trim();
        const category = item.getAttribute('data-category');
        
        if (selectedViolations.some(v => v.text === text)) {
            return;
        }

        const violation = {
            text: text,
            category: category,
            timestamp: new Date().toLocaleString()
        };

        selectedViolations.push(violation);
        updateSelectedList();
    }

    function removeViolation(index) {
        selectedViolations.splice(index, 1);
        updateSelectedList();
    }

    function updateSelectedList() {
        const list = document.getElementById('selectedList');
        const recordBtn = document.getElementById('recordBtn');

        list.innerHTML = '';
        
        selectedViolations.forEach((violation, index) => {
            const div = document.createElement('div');
            div.className = 'selected-item';
            div.innerHTML = `
                <div>
                    <div class="violation-text">${violation.text}</div>
                    <div class="violation-timestamp">${violation.timestamp} - ${violation.category}</div>
                </div>
                <button class="remove-btn" onclick="removeViolation(${index})">×</button>
            `;
            
            list.appendChild(div);
        });

        recordBtn.style.display = selectedViolations.length > 0 ? 'block' : 'none';
    }

    function recordViolations() {
        if (selectedViolations.length === 0) {
            alert('No violations to record!');
            return;
        }

        const recordedBy = prompt('Enter your name/position:') || 'Anonymous';
        
        if (!confirm(`Record ${selectedViolations.length} violation(s) for student ${studentId}?`)) {
            return;
        }

        // Show loading
        const recordBtn = document.getElementById('recordBtn');
        const originalText = recordBtn.textContent;
        recordBtn.textContent = 'Recording...';
        recordBtn.disabled = true;

        // Send to server
        const formData = new FormData();
        formData.append('action', 'record_violations');
        formData.append('violations', JSON.stringify(selectedViolations));
        formData.append('recorded_by', recordedBy);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`✅ Successfully recorded ${data.count} violation(s)!`);
                selectedViolations = [];
                updateSelectedList();
                location.reload(); // Refresh to show updated violations
            } else {
                alert('❌ Error recording violations. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Network error. Please try again.');
        })
        .finally(() => {
            recordBtn.textContent = originalText;
            recordBtn.disabled = false;
        });
    }

    function setAttendance(status) {
        attendanceStatus = status;
        
        const presentBtn = document.getElementById('presentBtn');
        const absentBtn = document.getElementById('absentBtn');
        
        presentBtn.classList.toggle('active', status === 'Present');
        absentBtn.classList.toggle('active', status === 'Absent');

        // Record attendance immediately
        const formData = new FormData();
        formData.append('action', 'record_attendance');
        formData.append('status', status);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log(`Attendance marked as ${status}`);
            } else {
                alert('Error recording attendance');
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    function resolveViolation(violationId) {
        if (!confirm('Mark this violation as resolved?')) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'resolve_violation');
        formData.append('violation_id', violationId);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload(); // Refresh to show updated violations
            } else {
                alert('Error resolving violation');
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    function printRecord() {
        const studentName = '<?php echo $student_data ? addslashes($student_data['name']) : ''; ?>';
        const studentInfo = '<?php echo $student_data ? addslashes($student_data['student_id'] . ' - ' . $student_data['grade'] . ' ' . $student_data['section']) : ''; ?>';
        
        let printContent = `STUDENT VIOLATION RECORD\n\n`;
        printContent += `Student: ${studentName}\n`;
        printContent += `${studentInfo}\n`;
        printContent += `Date: ${new Date().toLocaleDateString()}\n`;
        printContent += `Attendance: ${attendanceStatus || 'Not Set'}\n\n`;
        
        <?php if (!empty($current_violations)): ?>
        printContent += `CURRENT VIOLATIONS:\n`;
        <?php foreach ($current_violations as $index => $violation): ?>
        printContent += `<?php echo $index + 1; ?>. <?php echo addslashes($violation['violation_type']); ?>\n`;
        printContent += `   Date: <?php echo date('M j, Y g:i A', strtotime($violation['violation_date'])); ?>\n`;
        printContent += `   Category: <?php echo $violation['violation_category']; ?>\n\n`;
        <?php endforeach; ?>
        <?php else: ?>
        printContent += `No active violations recorded.\n`;
        <?php endif; ?>
        
        // Open print dialog
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head><title>Student Violation Record</title></head>
                <body style="font-family: monospace; white-space: pre-wrap; padding: 20px;">
                    ${printContent}
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }

    function scheduleMeeting() {
        const meetingDate = prompt('Enter meeting date (YYYY-MM-DD):');
        const meetingTime = prompt('Enter meeting time (HH:MM):');
        
        if (meetingDate && meetingTime) {
            const studentName = '<?php echo $student_data ? addslashes($student_data['name']) : ''; ?>';
            alert(`✅ Meeting scheduled for ${studentName}\nDate: ${meetingDate}\nTime: ${meetingTime}`);
            
            console.log('Meeting scheduled:', {
                student: studentName,
                student_id: studentId,
                date: meetingDate,
                time: meetingTime,
                violations: <?php echo count($current_violations); ?>
            });
        }
    }

    // Allow Enter key for manual search
    document.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && document.activeElement.id === 'manual_student_id') {
            searchStudent();
        }
    });
  </script>
</body>
</html>
