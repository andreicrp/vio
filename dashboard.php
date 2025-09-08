<?php
// dashboard.php
// VIOTRACK System - Dashboard Page
include 'db.php';

// Get total students count from database
$student_count_result = $conn->query("SELECT COUNT(*) as total FROM students");
$student_count = $student_count_result->fetch_assoc()['total'];

// Get active violations count
$active_violations_result = $conn->query("SELECT COUNT(*) as total FROM violations WHERE status = 'Active'");
$active_violations_count = $active_violations_result->fetch_assoc()['total'];

// Get violation statistics by category
$violation_stats_query = "SELECT violation_category, COUNT(*) as count FROM violations WHERE status = 'Active' GROUP BY violation_category";
$violation_stats_result = $conn->query($violation_stats_query);
$violation_stats = [];
while ($row = $violation_stats_result->fetch_assoc()) {
    $violation_stats[$row['violation_category']] = $row['count'];
}

// Set default values if no violations exist
$minor_count = isset($violation_stats['Minor']) ? $violation_stats['Minor'] : 0;
$serious_count = isset($violation_stats['Serious']) ? $violation_stats['Serious'] : 0;
$major_count = isset($violation_stats['Major']) ? $violation_stats['Major'] : 0;

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIOTRACK</title>
     <link rel="stylesheet" href="styles.css">
    
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
        <!-- Dashboard Page -->
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
                        <div class="donut-chart" id="violationChart"></div>
                        <div class="legend">
                            <div class="legend-item">
                                <div class="legend-color color-minor"></div>
                                <span>Minor Offenses (<?php echo $minor_count; ?>)</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color color-serious"></div>
                                <span>Serious Offenses (<?php echo $serious_count; ?>)</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color color-major"></div>
                                <span>Major Offenses (<?php echo $major_count; ?>)</span>
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

    <!-- Tooltip for calendar appointments -->
    <div id="tooltip" class="tooltip">
        <div class="tooltip-title"></div>
        <div class="tooltip-time"></div>
        <div class="tooltip-description"></div>
    </div>

    <!-- External JavaScript -->
    <script>
        // Violation statistics data from PHP
        const violationStats = {
            minor: <?php echo $minor_count; ?>,
            serious: <?php echo $serious_count; ?>,
            major: <?php echo $major_count; ?>
        };

        // Update donut chart based on actual data
        function updateViolationChart() {
            const chart = document.getElementById('violationChart');
            const total = violationStats.minor + violationStats.serious + violationStats.major;
            
            if (total === 0) {
                chart.style.background = '#e5e7eb';
                return;
            }
            
            const minorPercent = (violationStats.minor / total) * 100;
            const seriousPercent = (violationStats.serious / total) * 100;
            const majorPercent = (violationStats.major / total) * 100;
            
            const minorEnd = minorPercent;
            const seriousEnd = minorPercent + seriousPercent;
            const majorEnd = seriousEnd + majorPercent;
            
            chart.style.background = `conic-gradient(
                #10b981 0% ${minorEnd}%, 
                #f59e0b ${minorEnd}% ${seriousEnd}%, 
                #ef4444 ${seriousEnd}% ${majorEnd}%,
                #e5e7eb ${majorEnd}% 100%
            )`;
        }

        // Calendar functionality
        class CalendarSystem {
            constructor() {
                this.currentDate = new Date();
                this.currentMonth = this.currentDate.getMonth();
                this.currentYear = this.currentDate.getFullYear();
                this.appointments = this.generateAppointments();
                this.tooltip = document.getElementById('tooltip');
                
                this.init();
            }

            init() {
                this.render();
                this.attachEventListeners();
            }

            generateAppointments() {
                return {
                    '2024-01-15': { title: 'Parent-Teacher Conference', time: '2:00 PM', description: 'Annual meeting with parents' },
                    '2024-01-22': { title: 'Academic Board Meeting', time: '10:00 AM', description: 'Monthly board review' },
                    '2024-02-05': { title: 'Student Orientation', time: '9:00 AM', description: 'New student welcome program' },
                    '2024-02-14': { title: 'Valentine\'s Day Event', time: '3:00 PM', description: 'Student activity day' },
                    '2024-03-08': { title: 'Women\'s Day Celebration', time: '1:00 PM', description: 'Special assembly' },
                    '2024-03-25': { title: 'Science Fair', time: '10:00 AM', description: 'Annual science exhibition' },
                    '2024-04-10': { title: 'Spring Break Starts', time: 'All Day', description: 'School holiday begins' },
                    '2024-04-18': { title: 'Easter Monday', time: 'All Day', description: 'Public holiday' },
                    '2024-05-01': { title: 'Labor Day', time: 'All Day', description: 'National holiday' },
                    '2024-05-15': { title: 'Sports Day', time: '8:00 AM', description: 'Annual athletics competition' },
                    '2024-06-12': { title: 'Independence Day Program', time: '9:00 AM', description: 'National celebration' },
                    '2024-06-30': { title: 'End of School Year', time: 'All Day', description: 'Final day of classes' },
                    '2024-07-04': { title: 'Summer Program Begins', time: '8:00 AM', description: 'Optional summer classes' },
                    '2024-07-20': { title: 'Teacher Training Workshop', time: '9:00 AM', description: 'Professional development' },
                    '2024-08-15': { title: 'School Registration', time: '8:00 AM', description: 'New academic year enrollment' },
                    '2024-08-28': { title: 'First Day of School', time: '7:00 AM', description: 'Academic year begins' },
                    '2024-09-05': { title: 'Faculty Meeting', time: '3:00 PM', description: 'Monthly staff meeting' },
                    '2024-09-21': { title: 'International Peace Day', time: '10:00 AM', description: 'Special assembly' },
                    '2024-10-31': { title: 'Halloween Activities', time: '2:00 PM', description: 'Student costume party' },
                    '2024-11-01': { title: 'All Saints\' Day', time: 'All Day', description: 'Public holiday' },
                    '2024-11-15': { title: 'Mid-term Exams Begin', time: '8:00 AM', description: 'Examination period starts' },
                    '2024-12-08': { title: 'Immaculate Conception', time: 'All Day', description: 'Religious holiday' },
                    '2024-12-25': { title: 'Christmas Day', time: 'All Day', description: 'Christmas holiday' }
                };
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
                this.render();
            }

            nextMonth() {
                this.currentMonth++;
                if (this.currentMonth > 11) {
                    this.currentMonth = 0;
                    this.currentYear++;
                }
                this.render();
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
                                cell.classList.add('appointment');
                                const indicator = document.createElement('div');
                                indicator.classList.add('appointment-indicator');
                                cell.appendChild(indicator);
                                
                                this.addTooltipEvents(cell, this.appointments[dateKey]);
                            }
                            
                            date++;
                        }
                        
                        row.appendChild(cell);
                    }
                    
                    calendarBody.appendChild(row);
                    
                    if (date > daysInMonth && i > 3) break;
                }
            }

            addTooltipEvents(cell, appointment) {
                cell.addEventListener('mouseenter', (e) => {
                    this.showTooltip(e, appointment);
                });

                cell.addEventListener('mouseleave', () => {
                    this.hideTooltip();
                });

                cell.addEventListener('mousemove', (e) => {
                    this.updateTooltipPosition(e);
                });
            }

            showTooltip(event, appointment) {
                const tooltip = this.tooltip;
                tooltip.querySelector('.tooltip-title').textContent = appointment.title;
                tooltip.querySelector('.tooltip-time').textContent = `Time: ${appointment.time}`;
                tooltip.querySelector('.tooltip-description').textContent = appointment.description;
                
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
                
                let left = event.pageX + 10;
                let top = event.pageY - rect.height - 10;
                
                if (left + rect.width > viewportWidth) {
                    left = event.pageX - rect.width - 10;
                }
                
                if (top < 0) {
                    top = event.pageY + 10;
                }
                
                tooltip.style.left = `${left}px`;
                tooltip.style.top = `${top}px`;
            }
        }

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

        // Auto-refresh functions
        function refreshDashboardData() {
            fetch('dashboard_api.php?action=get_stats')
                .then(response => response.json())
                .then(data => {
                    // Update stats
                    document.querySelector('.stat-card .stat-number').textContent = data.total_students.toLocaleString();
                    document.querySelectorAll('.stat-card .stat-number')[1].textContent = data.active_violations.toLocaleString();
                    
                    // Update violation breakdown
                    violationStats.minor = data.violation_breakdown.Minor;
                    violationStats.serious = data.violation_breakdown.Serious;
                    violationStats.major = data.violation_breakdown.Major;
                    
                    // Update legend with counts
                    const legendItems = document.querySelectorAll('.legend-item span');
                    legendItems[0].textContent = `Minor Offenses (${data.violation_breakdown.Minor})`;
                    legendItems[1].textContent = `Serious Offenses (${data.violation_breakdown.Serious})`;
                    legendItems[2].textContent = `Major Offenses (${data.violation_breakdown.Major})`;
                    
                    // Update chart
                    updateViolationChart();
                })
                .catch(error => console.error('Error refreshing stats:', error));
        }

        function refreshRecentActivity() {
            fetch('dashboard_api.php?action=get_recent_activity')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.querySelector('.activity-table tbody');
                    tbody.innerHTML = '';
                    
                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: #64748b;">No recent activity</td></tr>';
                        return;
                    }
                    
                    data.forEach(activity => {
                        const row = document.createElement('tr');
                        
                        // Determine status class
                        let statusClass = 'pending';
                        if (activity.type === 'violation') {
                            statusClass = activity.status === 'Active' ? 'pending' : 'resolved';
                        } else {
                            statusClass = activity.status === 'Complete' ? 'active' : 'pending';
                        }
                        
                        row.innerHTML = `
                            <td>${activity.time}</td>
                            <td>${activity.student_name} (${activity.student_id})</td>
                            <td>${activity.activity}</td>
                            <td><span class="status-badge status-${statusClass}">${activity.status}</span></td>
                        `;
                        
                        tbody.appendChild(row);
                    });
                })
                .catch(error => console.error('Error refreshing activity:', error));
        }

        // Initialize the application when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            const activePage = document.querySelector('.page.active');
            if (!activePage) {
                showPage('dashboard');
            }
            
            // Initialize calendar
            new CalendarSystem();
            
            // Update violation chart
            updateViolationChart();
            
            // Set up auto-refresh (every 30 seconds)
            setInterval(refreshDashboardData, 30000);
            setInterval(refreshRecentActivity, 15000);
            
            // Add manual refresh button functionality
            const refreshBtn = document.createElement('button');
            refreshBtn.textContent = '🔄 Refresh';
            refreshBtn.className = 'btn btn-secondary';
            refreshBtn.style.position = 'fixed';
            refreshBtn.style.top = '20px';
            refreshBtn.style.right = '20px';
            refreshBtn.style.zIndex = '1001';
            refreshBtn.addEventListener('click', function() {
                refreshDashboardData();
                refreshRecentActivity();
                this.textContent = '✅ Updated';
                setTimeout(() => {
                    this.textContent = '🔄 Refresh';
                }, 2000);
            });
            document.body.appendChild(refreshBtn);
        });

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
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
    </script>
</body>
</html>
