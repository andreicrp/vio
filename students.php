<?php
// students.php
// VIOTRACK System - Students Page with QR Code Generation
include 'db.php';

// Function to generate QR code using online API
function generateQRCode($student_id, $base_url) {
    $violation_url = $base_url . "/violationss.php?student_id=" . urlencode($student_id);
    $qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($violation_url);
    return $qr_api_url;
}

// Get base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$base_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);

// Handle QR Code Regeneration Only
if (isset($_POST['regenerate_qr_only'])) {
    $id = $_POST['id'];
    $student_id = $_POST['student_id'];
    
    // Generate new QR code
    $qr_code_url = generateQRCode($student_id, $base_url);
    
    $sql = "UPDATE students SET qr_code='$qr_code_url' WHERE id='$id'";
    
    if ($conn->query($sql)) {
        header("Location: students.php?message=" . urlencode("QR code regenerated successfully"));
        exit();
    } else {
        $error = "Error regenerating QR code: " . $conn->error;
    }
}

// Handle Delete Student
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM students WHERE id = '$id'";
    if ($conn->query($sql)) {
        header("Location: students.php?message=Student deleted successfully");
        exit();
    }
}

// Handle Add Student
if (isset($_POST['add_student'])) {
    $student_id = $_POST['student_id'];
    $name       = $_POST['name'];
    $grade      = $_POST['grade'];
    $section    = $_POST['section'];
    $status     = $_POST['status'];
    
    // Generate QR code URL
    $qr_code_url = generateQRCode($student_id, $base_url);

    $sql = "INSERT INTO students (student_id, name, grade, section, status, qr_code) 
            VALUES ('$student_id', '$name', '$grade', '$section', '$status', '$qr_code_url')";
    
    if ($conn->query($sql)) {
        header("Location: students.php?message=Student added successfully with QR code generated");
        exit();
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Handle Update Student
if (isset($_POST['update_student'])) {
    $id = $_POST['id'];
    $student_id = $_POST['student_id'];
    $name = $_POST['name'];
    $grade = $_POST['grade'];
    $section = $_POST['section'];
    $status = $_POST['status'];

    // Regenerate QR code URL
    $qr_code_url = generateQRCode($student_id, $base_url);

    $sql = "UPDATE students SET student_id='$student_id', name='$name', grade='$grade', section='$section', status='$status', qr_code='$qr_code_url' WHERE id='$id'";
    
    if ($conn->query($sql)) {
        header("Location: students.php?message=Student updated successfully");
        exit();
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Fetch Students with search functionality
$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $result = $conn->query("SELECT * FROM students WHERE name LIKE '%$search%' OR student_id LIKE '%$search%' ORDER BY created_at DESC");
} else {
    $result = $conn->query("SELECT * FROM students ORDER BY created_at DESC");
}

// Get student for editing
$edit_student = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_result = $conn->query("SELECT * FROM students WHERE id = '$edit_id'");
    $edit_student = $edit_result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>VIOTRACK - Students</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }
    
    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border: none;
        border-radius: 8px;
        width: 600px;
        max-width: 90%;
        max-height: 80vh;
        overflow-y: auto;
    }
    
    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    
    .close:hover {
        color: black;
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
    
    .alert {
        padding: 10px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
    
    .alert-success {
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }
    
    .alert-error {
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }
    
    .btn-danger {
        background-color: #dc3545;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
    }
    
    .btn-danger:hover {
        background-color: #c82333;
    }
    
    .qr-code-container {
        text-align: center;
        margin: 15px 0;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }
    
    .qr-code-img {
        max-width: 150px;
        height: auto;
        border: 2px solid #007bff;
        border-radius: 8px;
    }
    
    .qr-code-label {
        font-weight: bold;
        margin-bottom: 10px;
        color: #495057;
    }
    
    .qr-code-url {
        font-size: 12px;
        color: #6c757d;
        margin-top: 8px;
        word-break: break-all;
    }
    
    .view-modal .modal-content {
        width: 500px;
    }
    
    .student-details {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .student-details h3 {
        margin-bottom: 15px;
        color: #343a40;
    }
    
    .detail-row {
        display: flex;
        margin-bottom: 10px;
    }
    
    .detail-label {
        font-weight: bold;
        width: 100px;
        color: #495057;
    }
    
    .detail-value {
        flex: 1;
        color: #212529;
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
            <a href="dashboard.php" class="menu-item" data-page="dashboard">
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
            <a href="#" class="menu-item active" data-page="students">
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
                        <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/>
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
      <div id="students" class="page active">
          <h1 class="page-header">Student Management</h1>
          
          <?php if (isset($_GET['message'])): ?>
              <div class="alert alert-success">
                  <?php echo htmlspecialchars($_GET['message']); ?>
              </div>
          <?php endif; ?>
          
          <?php if (isset($error)): ?>
              <div class="alert alert-error">
                  <?php echo htmlspecialchars($error); ?>
              </div>
          <?php endif; ?>
          
          <div class="content-card">
              <div class="content-title">Student Directory</div>
              <form method="GET" class="search-bar">
                  <input type="text" name="search" class="search-input" placeholder="Search students by name or ID..." 
                         value="<?php echo htmlspecialchars($search); ?>">
                  <button type="submit" class="btn btn-primary">Search</button>
                  <?php if ($search): ?>
                      <a href="students.php" class="btn btn-secondary">Clear</a>
                  <?php endif; ?>
              </form>
              <div class="content-section">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Grade</th>
                                <th>Section</th>
                                <th>Status</th>
                                <th>QR Code</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['grade']); ?></td>
                                    <td><?php echo htmlspecialchars($row['section']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['qr_code'])): ?>
                                            <img src="<?php echo htmlspecialchars($row['qr_code']); ?>" alt="QR Code" style="width: 50px; height: 50px; border: 1px solid #ddd; border-radius: 4px;">
                                        <?php else: ?>
                                            <span style="color: #999;">No QR</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary" onclick="viewStudent(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['student_id']); ?>', '<?php echo htmlspecialchars($row['name']); ?>', '<?php echo htmlspecialchars($row['grade']); ?>', '<?php echo htmlspecialchars($row['section']); ?>', '<?php echo htmlspecialchars($row['status']); ?>', '<?php echo htmlspecialchars($row['qr_code']); ?>')">View</button>
                                        <a href="?edit=<?php echo $row['id']; ?>" class="btn btn-secondary">Edit</a>
                                        <button class="btn btn-danger" onclick="deleteStudent(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['name']); ?>')">Delete</button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center;">No students found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
              </div>
              <div class="action-buttons">
                  <button class="btn btn-primary" onclick="openAddModal()">Add New Student</button>
                  <button class="btn btn-secondary" onclick="exportData()">Export Data</button>
              </div>
          </div>
      </div>
  </div>

  <!-- Add/Edit Student Modal -->
  <div id="addModal" class="modal">
      <div class="modal-content">
          <span class="close" onclick="closeAddModal()">&times;</span>
          <h2><?php echo $edit_student ? 'Edit Student' : 'Add New Student'; ?></h2>
          <form method="POST">
              <?php if ($edit_student): ?>
                  <input type="hidden" name="id" value="<?php echo $edit_student['id']; ?>">
              <?php endif; ?>
              
              <div class="form-group">
                  <label for="student_id">Student ID:</label>
                  <input type="text" id="student_id" name="student_id" required 
                         value="<?php echo $edit_student ? htmlspecialchars($edit_student['student_id']) : ''; ?>">
              </div>
              
              <div class="form-group">
                  <label for="name">Full Name:</label>
                  <input type="text" id="name" name="name" required 
                         value="<?php echo $edit_student ? htmlspecialchars($edit_student['name']) : ''; ?>">
              </div>
              
              <div class="form-group">
                  <label for="grade">Grade:</label>
                  <select id="grade" name="grade" required>
                      <option value="">Select Grade</option>
                      <option value="Grade 7" <?php echo ($edit_student && $edit_student['grade'] == 'Grade 7') ? 'selected' : ''; ?>>Grade 7</option>
                      <option value="Grade 8" <?php echo ($edit_student && $edit_student['grade'] == 'Grade 8') ? 'selected' : ''; ?>>Grade 8</option>
                      <option value="Grade 9" <?php echo ($edit_student && $edit_student['grade'] == 'Grade 9') ? 'selected' : ''; ?>>Grade 9</option>
                      <option value="Grade 10" <?php echo ($edit_student && $edit_student['grade'] == 'Grade 10') ? 'selected' : ''; ?>>Grade 10</option>
                      <option value="Grade 11" <?php echo ($edit_student && $edit_student['grade'] == 'Grade 11') ? 'selected' : ''; ?>>Grade 11</option>
                      <option value="Grade 12" <?php echo ($edit_student && $edit_student['grade'] == 'Grade 12') ? 'selected' : ''; ?>>Grade 12</option>
                  </select>
              </div>
              
              <div class="form-group">
                  <label for="section">Section:</label>
                  <input type="text" id="section" name="section" required 
                         value="<?php echo $edit_student ? htmlspecialchars($edit_student['section']) : ''; ?>">
              </div>
              
              <div class="form-group">
                  <label for="status">Status:</label>
                  <select id="status" name="status" required>
                      <option value="Active" <?php echo ($edit_student && $edit_student['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                      <option value="Inactive" <?php echo ($edit_student && $edit_student['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                      <option value="Suspended" <?php echo ($edit_student && $edit_student['status'] == 'Suspended') ? 'selected' : ''; ?>>Suspended</option>
                  </select>
              </div>
              
              <?php if ($edit_student && !empty($edit_student['qr_code'])): ?>
              <div class="qr-code-container">
                  <div class="qr-code-label">Current QR Code:</div>
                  <img src="<?php echo htmlspecialchars($edit_student['qr_code']); ?>" alt="Student QR Code" class="qr-code-img">
                  <div class="qr-code-url">QR Code will be regenerated upon update</div>
              </div>
              <?php endif; ?>
              
              <div class="form-group">
                  <button type="submit" name="<?php echo $edit_student ? 'update_student' : 'add_student'; ?>" class="btn btn-primary">
                      <?php echo $edit_student ? 'Update Student' : 'Add Student'; ?>
                  </button>
                  <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
              </div>
          </form>
      </div>
  </div>

  <!-- View Student Modal -->
  <div id="viewModal" class="modal view-modal">
      <div class="modal-content">
          <span class="close" onclick="closeViewModal()">&times;</span>
          <h2>Student Details</h2>
          <div id="studentDetailsContent">
              <!-- Content will be populated by JavaScript -->
          </div>
      </div>
  </div>

  <script>
        // Modal functions
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }

        function closeViewModal() {
            document.getElementById('viewModal').style.display = 'none';
        }

        // Show edit modal if editing
        <?php if ($edit_student): ?>
        document.addEventListener('DOMContentLoaded', function() {
            openAddModal();
        });
        <?php endif; ?>

        // Delete student function
        function deleteStudent(id, name) {
            if (confirm('Are you sure you want to delete student: ' + name + '?\n\nThis will also delete all associated violations and attendance records.')) {
                window.location.href = '?delete=' + id;
            }
        }

        // View student function
        function viewStudent(id, studentId, name, grade, section, status, qrCode) {
            const detailsContent = document.getElementById('studentDetailsContent');
            
            let qrCodeHtml = '';
            if (qrCode && qrCode !== '') {
                qrCodeHtml = `
                    <div class="qr-code-container">
                        <div class="qr-code-label">Student QR Code:</div>
                        <img src="${qrCode}" alt="Student QR Code" class="qr-code-img">
                        <div class="qr-code-url">Scan this QR code to access violation tracker</div>
                        <button class="btn btn-secondary" onclick="downloadQR('${qrCode}', '${studentId}')">Download QR Code</button>
                    </div>
                `;
            }
            
            detailsContent.innerHTML = `
                <div class="student-details">
                    <h3>Personal Information</h3>
                    <div class="detail-row">
                        <span class="detail-label">Student ID:</span>
                        <span class="detail-value">${studentId}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Name:</span>
                        <span class="detail-value">${name}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Grade:</span>
                        <span class="detail-value">${grade}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Section:</span>
                        <span class="detail-value">${section}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value"><span class="status-badge status-${status.toLowerCase()}">${status}</span></span>
                    </div>
                </div>
                ${qrCodeHtml}
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="openViolationTracker('${studentId}')">Open Violation Tracker</button>
                    <button class="btn btn-secondary" onclick="printStudentCard('${studentId}', '${name}', '${grade}', '${section}', '${qrCode}')">Print Student Card</button>
                </div>
            `;
            
            document.getElementById('viewModal').style.display = 'block';
        }

        // Open violation tracker
        function openViolationTracker(studentId) {
            const url = `violationss.php?student_id=${encodeURIComponent(studentId)}`;
            window.open(url, '_blank');
        }

        // Download QR Code
        function downloadQR(qrUrl, studentId) {
            const link = document.createElement('a');
            link.href = qrUrl;
            link.download = `qr_code_${studentId}.png`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Print student card
        function printStudentCard(studentId, name, grade, section, qrCode) {
            const printWindow = window.open('', '_blank');
            const printContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Student Card - ${studentId}</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        .card { border: 2px solid #000; padding: 20px; width: 300px; margin: 0 auto; text-align: center; }
                        .qr { margin: 10px 0; }
                        h2 { margin: 0 0 20px 0; }
                        .info { text-align: left; margin: 20px 0; }
                    </style>
                </head>
                <body>
                    <div class="card">
                        <h2>VIOTRACK Student Card</h2>
                        <div class="info">
                            <strong>Name:</strong> ${name}<br>
                            <strong>ID:</strong> ${studentId}<br>
                            <strong>Grade:</strong> ${grade}<br>
                            <strong>Section:</strong> ${section}
                        </div>
                        ${qrCode ? `<div class="qr"><img src="${qrCode}" alt="QR Code" style="width: 150px; height: 150px;"></div>` : ''}
                        <p><small>Scan QR code for violation tracking</small></p>
                    </div>
                </body>
                </html>
            `;
            
            printWindow.document.write(printContent);
            printWindow.document.close();
            printWindow.print();
        }

        // Export data function
        function exportData() {
            alert('Export functionality will be implemented here');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addModal');
            const viewModal = document.getElementById('viewModal');
            if (event.target == addModal) {
                closeAddModal();
            }
            if (event.target == viewModal) {
                closeViewModal();
            }
        }
    </script>
</body>
</html>
