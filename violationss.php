<?php
// violationss.php
// A modern student violations tracker interface
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Modern Violations Tracker</title>
  <link rel="stylesheet" href="violations.css">
</head>
<body>
  <div class="content">
    <header class="page-header">
      <h1>📋 Student Violations Tracker</h1>
    </header>
    
    <div class="container">
      <div class="left-panel">
        <!-- Profile Card -->
        <div class="profile-card">
          <div class="profile-img">👤</div>
          <div class="profile-name">Student Name</div>
          <div class="student-info">ID: S001<br>Grade 10 - Section A</div>
        </div>

        <!-- Current Violations Card -->
        <div class="current-violations-card">
          <h3>📋 Current Violations</h3>
          <div id="currentViolationsList">
            <div class="no-violations">No Violations Recorded</div>
          </div>
          <div class="action-buttons">
            <div class="attendance-buttons">
              <button class="attendance-btn present" id="presentBtn">✓ Present</button>
              <button class="attendance-btn absent" id="absentBtn">✗ Absent</button>
            </div>
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
            <div class="violation-item">Uniform not worn or worn improperly</div>
            <div class="violation-item">School ID not worn properly</div>
            <div class="violation-item">Playing non-educational games</div>
            <div class="violation-item">Sleeping during class or activities</div>
            <div class="violation-item">Borrowing or lending money</div>
            <div class="violation-item">Loitering or shouting in hallways</div>
            <div class="violation-item">Leaving classroom messy</div>
            <div class="violation-item">Use of mobile gadgets without permission</div>
            <div class="violation-item">Excessive jewelry or inappropriate appearance</div>
            <div class="violation-item">Wearing inappropriate clothing</div>
            <div class="violation-item">Using vulgar language</div>
            <div class="violation-item">Minor bullying or teasing</div>
            <div class="violation-item">Fighting or causing disturbances</div>
            <div class="violation-item">Horseplay inside classroom</div>
            <div class="violation-item">Making inappropriate jokes</div>
            <div class="violation-item">Not completing clearance requirements</div>
            <div class="violation-item">Taking food without consent</div>
            <div class="violation-item">Not joining required school activities</div>
            <div class="violation-item">Chewing gum or spitting in public</div>
            <div class="violation-item">Buying food during class hours</div>
            <div class="violation-item">Using school properties without permission</div>
            <div class="violation-item">Forging parent's signature</div>
            <div class="violation-item">Misbehavior in the canteen</div>
            <div class="violation-item">Disturbing class sessions</div>
            <div class="violation-item">Unauthorized playing in the gym</div>
            <div class="violation-item">Other similar minor offenses</div>
          </div>
        </div>

        <div class="category serious">
          <button class="category-btn">
            🟡 Serious Offenses
            <span class="arrow">▼</span>
          </button>
          <div class="violations-list">
            <div class="violation-item">Cheating or academic dishonesty</div>
            <div class="violation-item">Plagiarism</div>
            <div class="violation-item">Rough or dangerous play</div>
            <div class="violation-item">Lying or misleading statements</div>
            <div class="violation-item">Possession of smoking items</div>
            <div class="violation-item">Vulgar or malicious acts</div>
            <div class="violation-item">Bribery or dishonest acts</div>
            <div class="violation-item">Theft or aiding theft</div>
            <div class="violation-item">Gambling or unauthorized collection</div>
            <div class="violation-item">Not delivering official communications</div>
            <div class="violation-item">Rude behavior to visitors or parents</div>
            <div class="violation-item">Disrespect to school authorities</div>
            <div class="violation-item">Use of vulgar language repeatedly</div>
            <div class="violation-item">Causing chaos during events</div>
            <div class="violation-item">Disrespect during flag ceremonies</div>
            <div class="violation-item">Property damage from mischief</div>
            <div class="violation-item">Skipping class / truancy</div>
            <div class="violation-item">Entering/exiting without permission</div>
            <div class="violation-item">Public display of affection</div>
            <div class="violation-item">Minor vandalism</div>
            <div class="violation-item">Posting unauthorized notices</div>
            <div class="violation-item">Posting offensive content online</div>
            <div class="violation-item">Removing school announcements</div>
            <div class="violation-item">Gang-like behavior</div>
            <div class="violation-item">Cyberbullying</div>
            <div class="violation-item">Gross misconduct</div>
          </div>
        </div>

        <div class="category major">
          <button class="category-btn">
            🔴 Major Offenses
            <span class="arrow">▼</span>
          </button>
          <div class="violations-list">
            <div class="violation-item">Forgery or document tampering</div>
            <div class="violation-item">Using fake receipts or forms</div>
            <div class="violation-item">Major vandalism</div>
            <div class="violation-item">Physical assault</div>
            <div class="violation-item">Stealing school or class funds</div>
            <div class="violation-item">Extortion or blackmail</div>
            <div class="violation-item">Immoral or scandalous behavior</div>
            <div class="violation-item">Possessing pornography</div>
            <div class="violation-item">Criminal charges or conviction</div>
            <div class="violation-item">Harassment of students/staff</div>
            <div class="violation-item">Causing injury requiring hospitalization</div>
            <div class="violation-item">Sexual harassment</div>
            <div class="violation-item">Lewd or immoral acts</div>
            <div class="violation-item">Inappropriate social media use</div>
            <div class="violation-item">Leaking confidential school info</div>
            <div class="violation-item">Misusing school logo or name</div>
            <div class="violation-item">Bribing school officials</div>
            <div class="violation-item">Drinking alcohol in school</div>
            <div class="violation-item">Drug-related activities</div>
            <div class="violation-item">Bringing deadly weapons</div>
            <div class="violation-item">Joining fraternities/sororities</div>
          </div>
        </div>

        <div class="selected-section">
          <h3>📝 Selected Violations</h3>
          <div id="selectedList"></div>
          <button class="record-btn" id="recordBtn">RECORD VIOLATIONS</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    let selectedViolations = [];
    let currentViolations = [];
    let attendanceStatus = null;

    // Initialize event listeners
    document.getElementById('recordBtn').addEventListener('click', recordViolations);
    document.getElementById('presentBtn').addEventListener('click', () => setAttendance('present'));
    document.getElementById('absentBtn').addEventListener('click', () => setAttendance('absent'));
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

    function toggleCategory(btn) {
      btn.parentElement.classList.toggle('open');
      const arrow = btn.querySelector('.arrow');
      arrow.textContent = btn.parentElement.classList.contains('open') ? '▲' : '▼';
    }

    function addViolation(item) {
      const text = item.textContent.trim();
      
      if (selectedViolations.some(v => v.text === text)) {
        return;
      }

      const violation = {
        text: text,
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
            <div class="violation-timestamp">${violation.timestamp}</div>
          </div>
          <button class="remove-btn">×</button>
        `;
        
        const removeBtn = div.querySelector('.remove-btn');
        removeBtn.addEventListener('click', () => removeViolation(index));
        
        list.appendChild(div);
      });

      recordBtn.style.display = selectedViolations.length > 0 ? 'block' : 'none';
    }

    function updateCurrentViolationsList() {
      const list = document.getElementById('currentViolationsList');
      
      if (currentViolations.length === 0) {
        list.innerHTML = '<div class="no-violations">No Violations Recorded</div>';
      } else {
        list.innerHTML = '';
        currentViolations.forEach((violation, index) => {
          const div = document.createElement('div');
          div.className = 'current-violation-item';
          div.innerHTML = `
            <div class="violation-info">
              <div class="violation-text">${violation.text}</div>
              <div class="violation-date">${violation.timestamp}</div>
            </div>
            <button class="remove-current-btn" onclick="removeCurrentViolation(${index})">×</button>
          `;
          list.appendChild(div);
        });
      }
    }

    function removeCurrentViolation(index) {
      currentViolations.splice(index, 1);
      updateCurrentViolationsList();
    }

    function recordViolations() {
      if (selectedViolations.length === 0) {
        alert('No violations to record!');
        return;
      }

      const confirmation = confirm(`Record ${selectedViolations.length} violation(s)?`);
      
      if (confirmation) {
        currentViolations.push(...selectedViolations);
        
        console.log('Recording violations:', {
          violations: selectedViolations,
          timestamp: new Date().toISOString()
        });
        
        alert(`✅ Successfully recorded ${selectedViolations.length} violation(s)!`);
        
        selectedViolations = [];
        updateSelectedList();
        updateCurrentViolationsList();
      }
    }

    function setAttendance(status) {
      attendanceStatus = status;
      
      const presentBtn = document.getElementById('presentBtn');
      const absentBtn = document.getElementById('absentBtn');
      
      presentBtn.classList.toggle('active', status === 'present');
      absentBtn.classList.toggle('active', status === 'absent');
      
      console.log('Attendance set to:', status);
    }

    function printRecord() {
      const studentName = document.querySelector('.profile-name').textContent;
      const studentId = document.querySelector('.student-info').textContent;
      
      let printContent = `STUDENT VIOLATION RECORD\n\n`;
      printContent += `Student: ${studentName}\n`;
      printContent += `${studentId}\n`;
      printContent += `Date: ${new Date().toLocaleDateString()}\n`;
      printContent += `Attendance: ${attendanceStatus || 'Not Set'}\n\n`;
      
      if (currentViolations.length > 0) {
        printContent += `CURRENT VIOLATIONS:\n`;
        currentViolations.forEach((violation, index) => {
          printContent += `${index + 1}. ${violation.text}\n   Date: ${violation.timestamp}\n\n`;
        });
      } else {
        printContent += `No violations recorded.\n`;
      }
      
      alert('Print Preview:\n\n' + printContent);
      console.log('Print content:', printContent);
    }

    function scheduleMeeting() {
      const meetingDate = prompt('Enter meeting date (YYYY-MM-DD):');
      const meetingTime = prompt('Enter meeting time (HH:MM):');
      
      if (meetingDate && meetingTime) {
        const studentName = document.querySelector('.profile-name').textContent;
        alert(`✅ Meeting scheduled for ${studentName}\nDate: ${meetingDate}\nTime: ${meetingTime}`);
        
        console.log('Meeting scheduled:', {
          student: studentName,
          date: meetingDate,
          time: meetingTime,
          violations: currentViolations.length
        });
      }
    }

    // Initialize the current violations display
    updateCurrentViolationsList();
  </script>
</body>
</html>