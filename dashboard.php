<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.html");
  exit;
}

$stmt = $pdo->prepare("
      SELECT *
      FROM users
      WHERE user_id = ?
  ");

$stmt->execute([$_SESSION['user_id']]);

$user = $stmt->fetch();

$fullName = $user['full_name'];
$email = $user['email'];
$country = $user['country'];
$joinedDate = $user['created_at'];
$passwordHash = $user['password_hash'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Parent Workspace - CareNest</title>

  <!-- favicon -->
  <link rel="icon" type="image/png" href="assets/images/logo.png" sizes="32x32">

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/2.3.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">

  <!-- Font Awesome 6 Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Chart.js for real-time visualization of child well-being metrics -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- Custom Stylesheet -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

  <!-- Alert notification overlay container -->
  <div class="container mt-4 px-3" id="alertContainer" style="position: fixed; top: 10px; right: 20px; z-index: 9999; max-width: 420px;"></div>

  <div class="dashboard-container">

    <!-- Left Sidebar Menu -->
    <aside class="side-menu">
      <div class="brand-header">
        <a class="brand-logo-container" href="index.html">
          <div class="brand-logo-frame bg-white" style="color: var(--primary);">
            <img src="assets/images/logo.png" alt="Logo" width="70" height="70" style="border-radius: 50%; object-fit: cover; margin-right: 20px;">
          </div>
          <span class="brand-text text-white">CareNest</span>
        </a>
        <div class="mt-3 text-white-50 small d-flex align-items-center gap-2">
          <i class="fa-solid fa-circle-user text-accent"></i>
          <span id="sidebarGuardianName"><?php echo htmlspecialchars($fullName); ?></span>
        </div>
      </div>

      <nav class="flex-grow-1">
        <a id="linkDashHome" onclick="switchDashSection('dashHome')" class="active">
          <i class="fa-solid fa-table-columns"></i> Dashboard
        </a>
        <a id="linkMyChildren" onclick="switchDashSection('dashMyChildren')">
          <i class="fa-solid fa-children"></i> Children Profiles
        </a>
        <a id="linkAddChild" onclick="switchDashSection('dashAddChild')">
          <i class="fa-solid fa-user-plus"></i> Add a New Child
        </a>
        <a id="linkChildProfile" onclick="switchDashSection('dashChildProfile')" style="display: none;">
          <i class="fa-solid fa-folder-open"></i> Profile Details
        </a>
        <a id="linkAssessment" onclick="switchDashSection('dashAssessment')">
          <i class="fa-solid fa-stethoscope"></i> Start Symptom Checker
        </a>
        <a id="linkResult" onclick="switchDashSection('dashResult')" style="display: none;">
          <i class="fa-solid fa-square-poll-vertical"></i> Assessment Result
        </a>
        <a id="linkHospitals" onclick="openNearestHospitals()">
          <i class="fa-solid fa-hospital"></i> Nearby Hospitals
        </a>
        <a id="linkAppointment" onclick="switchDashSection('dashAppointment')">
          <i class="fa-solid fa-calendar-check"></i> Book Consultation
        </a>
        <a id="linkProfile" onclick="switchDashSection('dashProfile')">
          <i class="fa-solid fa-gears"></i> Settings
        </a>
      </nav>

      <div class="mt-auto border-top border-secondary pt-3">
        <a onclick="handleLogout()" class="text-accent fw-bold">
          <i class="fa-solid fa-door-open"></i> Log Out
        </a>
      </div>
    </aside>

    <section class="content-area">

      <!-- Panel Sub-section: DASHBOARD HOME -->
      <div id="dashHome" class="dash-section active-section">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
          <div>
            <h2 class="fw-bold mb-1 text-dark" id="dashWelcomeTitle">Welcome Back!</h2>
            <p class="text-muted mb-0 small">Overview of your Dashboard</p>
          </div>
          <div>
            <button class="btn btn-primary-custom my-2" onclick="switchDashSection('dashAssessment')">
              <i class="fa-solid fa-heart-pulse me-1"></i> Start New Evaluation
            </button>
            <button class="btn btn-outline-danger my-2" onclick="openNearestHospitals()">
              <i class="fa-solid fa-hospital me-1"></i> Find Nearby Hospital
            </button>
          </div>
        </div>

        <!-- Quick Summary Cards -->
        <div class="row g-4 mb-4">
          <div class="col-md-4">
            <div class="summary-card">
              <span class="text-muted small text-uppercase fw-semibold">
                <i class="fa-solid fa-user-astronaut me-1"></i> Registered Kids
              </span>
              <h3 class="fw-bold text-dark mt-1" id="summaryChildCount">2</h3>
              <a href="#" class="small text-decoration-none fw-medium" onclick="switchDashSection('dashMyChildren')" style="color: var(--primary);">
                Manage Profiles &rarr;
              </a>
            </div>
          </div>
          <div class="col-md-4">
            <div class="summary-card" style="border-left-color: var(--accent-dark);">
              <span class="text-muted small text-uppercase fw-semibold">
                <i class="fa-solid fa-clipboard-check me-1"></i> Total Evaluations
              </span>
              <h3 class="fw-bold text-dark mt-1" id="summaryAssessmentCount">2</h3>
              <span class="small text-muted" id="summaryLastResult">Latest: Normal</span>
            </div>
          </div>
          <div class="col-md-4">
            <div class="summary-card" style="border-left-color: #5c7f80;">
              <span class="text-muted small text-uppercase fw-semibold">
                <i class="fa-solid fa-calendar-day me-1"></i> Bookings
              </span>
              <h3 class="fw-bold text-dark mt-1" id="summaryAppointmentCount">1</h3>
              <a href="#" class="small text-decoration-none fw-medium" onclick="switchDashSection('dashAppointment')" style="color: var(--primary);">
                View Schedule &rarr;
              </a>
            </div>
          </div>
        </div>

        <!-- Well-being Index Analytics Graph -->
        <div class="row g-4 mb-4">
          <div class="col-12">
            <div class="chart-card">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0">
                  <i class="fa-solid fa-chart-line text-teal me-2" style="color: var(--primary);"></i> Your Children's Well-being Analytics
                </h5>
                <span class="badge text-dark" style="background-color: var(--secondary);">Assessment summary</span>
              </div>
              <div style="position: relative; height: 320px; width: 100%;">
                <canvas id="wellbeingChart"></canvas>
              </div>

              <!-- STYLED GRAPH NOTE BAR -->
              <div class="mt-4 p-3 bg-light rounded-3 border border-1 border-light-subtle">
                <div class="row align-items-center g-3 text-center text-md-start">

                  <div class="col-md-12 col-lg-12">
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                      <span class="badge bg-success-subtle text-success px-2.5 py-1.5 border border-success-subtle rounded-pill" style="font-size: 0.75rem;">
                        <i class="fa-solid fa-face-smile me-1"></i> Low Severity = 95% Vitality
                      </span>
                      <span class="badge bg-warning-subtle text-dark px-2.5 py-1.5 border border-warning-subtle rounded-pill" style="font-size: 0.75rem;">
                        <i class="fa-solid fa-face-meh me-1"></i> Medium = 65% Mild Concern
                      </span>
                      <span class="badge bg-danger-subtle text-danger px-2.5 py-1.5 border border-danger-subtle rounded-pill" style="font-size: 0.75rem;">
                        <i class="fa-solid fa-face-sad-tear me-1"></i> High = 25% Immediate Action
                      </span>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>

        <!-- Recent Assessment Summary Logs -->
        <div class="card p-4 border mb-4 shadow-sm" style="border-radius: 16px;">
          <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
            <h5 class="fw-bold text-dark mb-0">
              <i class="fa-solid fa-clock-rotate-left me-2 text-teal" style="color: var(--primary);"></i> Recent Evaluations History
            </h5>
            <span class="text-muted small">Updated live</span>
          </div>

          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="recentAssessmentsDataTable">
              <thead>
                <tr class="table-light text-muted small" style="font-size: 0.82rem; letter-spacing: 0.3px; text-transform: uppercase;">
                  <th class="ps-3">Child Name</th>
                  <th>Assessment Date</th>
                  <th>Severity Status</th>
                  <th>Recommended Action</th>
                  <th class="text-end pe-3">Actions</th>
                </tr>
              </thead>
              <tbody id="recentAssessmentsTable">
                <!-- App JavaScript renders rows dynamically with custom initials avatars -->
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div id="dashAddChild" class="dash-section">
        <div class="card p-4 border mx-auto" style="max-width: 650px;">
          <h4 class="fw-bold mb-1" style="color: var(--primary);">
            <i class="fa-solid fa-user-plus me-1"></i> Register Child's Profile
          </h4>
          <p class="text-muted small mb-4">Add your child's personal details for tracking. Users must be age 12 or younger.</p>

          <form id="childForm" onsubmit="handleSaveChild(event)">
            <div class="mb-3">
              <label for="childName" class="form-label fw-semibold small">Child's First & Last Name</label>
              <input type="text" class="form-control" id="childName" name="child_name" placeholder="Full name" required>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="childDob" class="form-label fw-semibold small">Date of Birth</label>
                <input type="date" class="form-control" id="childDob" name="dob" required>
                <div class="form-text text-muted small" style="font-size: 0.75rem;">Age limit verification is checked on-submit.</div>
              </div>
              <div class="col-md-6 mb-3">
                <label for="childGender" class="form-label fw-semibold small">Gender</label>
                <select class="form-select form-control" id="childGender" name="gender" required>
                  <option value="">Select Gender</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                  <option value="Other">Other</option>
                </select>
              </div>
            </div>
            <div class="mb-4">
              <label for="previousDiagnoses" class="form-label fw-semibold small">Pre-Existing Conditions / Asthma / Allergies</label>
              <textarea class="form-control" id="previousDiagnoses" name="previous_diagnoses" rows="3" placeholder="Enter asthma history, food allergies, or type 'None' if clear"></textarea>
            </div>
            <div class="d-flex gap-3">
              <button type="submit" class="btn btn-primary-custom px-4">
                <i class="fa-solid fa-save me-1"></i> Save Profile
              </button>
              <button type="button" class="btn btn-outline-secondary px-4" onclick="switchDashSection('dashMyChildren')">Cancel</button>
            </div>
          </form>
        </div>
      </div>

      <div id="dashMyChildren" class="dash-section">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
          <div>
            <h2 class="fw-bold mb-1">Registered Children Profiles</h2>
            <p class="text-muted mb-0 small">Select any child's card below to update histories or view symptom progress timelines.</p>
          </div>
          <button class="btn btn-primary-custom my-2" onclick="switchDashSection('dashAddChild')">
            <i class="fa-solid fa-plus me-1"></i> Add New Profile
          </button>
        </div>

        <div class="row g-4" id="childrenGrid">
          <!-- Dynamically populated child cards -->
        </div>
      </div>

      <div id="dashChildProfile" class="dash-section">
        <div class="card p-4 border mb-4">
          <div class="d-flex justify-content-between align-items-center flex-wrap border-bottom pb-3 mb-4">
            <div>
              <h3 class="fw-bold text-dark mb-1" id="profileChildName">Sophia Vance</h3>
              <span class="badge" style="background-color: var(--secondary); color: var(--primary);" id="profileChildAgeGender">Age: 4 • Female</span>
            </div>
            <button class="btn btn-outline-secondary btn-sm" onclick="switchDashSection('dashMyChildren')">
              <i class="fa-solid fa-arrow-left me-1"></i> Back to List
            </button>
          </div>

          <div class="row g-4">
            <!-- Edit Details Form -->
            <div class="col-lg-5">
              <h5 class="fw-bold text-dark mb-3">
                <i class="fa-solid fa-address-book me-1"></i> Profile Parameters
              </h5>
              <form id="editChildForm" onsubmit="handleEditChild(event)">
                <input type="hidden" name="child_id" id="editChildId">
                <div class="mb-3">
                  <label for="editChildName" class="form-label fw-semibold small">Child's Name</label>
                  <input type="text" class="form-control" id="editChildName" name="child_name" required>
                </div>
                <div class="mb-3">
                  <label for="editChildDob" class="form-label fw-semibold small">Date of Birth</label>
                  <input type="date" class="form-control" id="editChildDob" name="dob" required>
                </div>
                <div class="mb-3">
                  <label for="editChildGender" class="form-label fw-semibold small">Gender</label>
                  <select class="form-select form-control" id="editChildGender" name="gender" required>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label for="editPreviousDiagnoses" class="form-label fw-semibold small">Pre-Existing Conditions</label>
                  <textarea class="form-control" id="editPreviousDiagnoses" name="previous_diagnoses" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary-custom btn-sm">
                  <i class="fa-solid fa-user-check me-1"></i> Update Profile
                </button>
              </form>
            </div>

            <!-- History Logs for Child -->
            <div class="col-lg-7">
              <h5 class="fw-bold text-dark mb-3">
                <i class="fa-solid fa-notes-medical me-1"></i> Evaluation Logs
              </h5>
              <div class="table-responsive">
                <table class="table table-sm align-middle" id="childHistoryDataTable">
                  <thead>
                    <tr>
                      <th>Date</th>
                      <th>Severity</th>
                      <th>Advice Provided</th>
                    </tr>
                  </thead>
                  <tbody id="childIndividualHistory">
                    <!-- Rendered dynamically -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div id="dashAssessment" class="dash-section">
        <div class="card p-4 border mx-auto" style="max-width: 700px;">
          <div id="assessmentPreSetup">
            <h4 class="fw-bold mb-1 text-dark">
              <i class="fa-solid fa-clipboard-question me-1"></i> Smart Symptom Checker
            </h4>
            <p class="text-muted small mb-4">Complete the dynamic questions to assess immediate discomfort or physical changes. Select a child profile to get started:</p>

            <div class="mb-4">
              <label for="assessmentTargetChild" class="form-label fw-semibold small">Select Child Profile</label>
              <select class="form-select form-control" id="assessmentTargetChild">
                <!-- Loaded dynamically -->
              </select>
            </div>
            <button class="btn btn-primary-custom" onclick="beginAssessmentWizard()">
              <i class="fa-solid fa-play me-1"></i> Begin Symptom Evaluation
            </button>
          </div>

          <!-- Active Wizard Panel -->
          <!-- Active Assessment Wizard -->
          <div id="assessmentActiveWizard" style="display: none;">

            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="text-muted small fw-semibold">
                Patient:
                <strong class="text-dark" id="wizardChildLabel"></strong>
              </span>

              <span class="small text-muted" id="wizardProgressLabel">
                Question 1
              </span>
            </div>

            <!-- Progress Bar -->
            <div class="assessment-progress-container">
              <div class="assessment-progress-bar" id="wizardProgressBar" style="width: 0%;"></div>
            </div>

            <!-- Loading State -->
            <div id="assessmentLoading" class="text-center py-5" style="display: none;">
              <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>

              <h6 class="fw-bold"> Preparing the health assessment </h6>

              <p class="text-muted small mb-0"> CareNest is creating questions suitable for your child. </p>
            </div>

            <!-- Dynamic Question -->
            <div class="question-card" id="dynamicQuestionCard" style="display: none;">
              <form id="assessmentForm" onsubmit="event.preventDefault();">
                <div id="dynamicQuestionContainer"></div>
              </form>
            </div>

            <!-- Navigation Buttons -->
            <div
              class="d-flex justify-content-between mt-3"
              id="wizardNavigation"
              style="display: none;">
              <button
                type="button" class="btn btn-outline-secondary" id="btnPrevQuestion" onclick="navigateWizard(-1)">
                <i class="fa-solid fa-chevron-left me-1"></i>
                Back
              </button>

              <button type="button" class="btn btn-primary-custom" id="btnNextQuestion" onclick="navigateWizard(1)">
                Next
                <i class="fa-solid fa-chevron-right ms-1"></i>
              </button>
            </div>

          </div>
        </div>
      </div>

      <div id="dashResult" class="dash-section">
        <div class="card border-0 shadow-sm mx-auto" style="max-width: 780px; border-radius: 20px; overflow: hidden; background-color: var(--white);">

          <!-- Dynamic Alert Color Header Badge -->
          <div id="resultHeaderBanner" class="p-4 text-center text-white" style="background-color: var(--primary);">
            <div class="mb-2">
              <i class="fa-solid fa-circle-check" id="resultIcon" style="font-size: 3.5rem;"></i>
            </div>
            <h3 class="fw-bold mb-1" id="resultTitle">Low Risk Severity</h3>
            <div class="small opacity-75" id="resultEmailNotice">An automated clinical copy has been routed successfully.</div>
          </div>

          <div class="p-4 p-md-5">

            <!-- Dual-Column Structured Action Plan (Uncluttered layout) -->
            <h5 class="fw-bold text-dark mb-3">
              <i class="fa-solid fa-hand-holding-medical text-teal me-2"></i> Immediate Home Care Plan
            </h5>

            <div class="row g-3 mb-4">
              <!-- Column 1: Immediate Steps -->
              <div class="col-md-6">
                <div class="p-3 h-100 rounded-3 border-start border-4" style="background-color: #fcfcf9; border-color: var(--accent) !important;">
                  <h6 class="fw-bold text-dark small mb-1.5"><i class="fa-solid fa-temperature-arrow-down me-1"></i> Comfort Actions</h6>
                  <p class="mb-0 text-muted leading-relaxed" id="resultDescription">Keep comfort levels high, monitor hydration, and offer restful opportunities.</p>
                </div>
              </div>

              <!-- Column 2: Safety Rules -->
              <div class="col-md-6">
                <div class="p-3 h-100 rounded-3 border-start border-4" style="background-color: #fcfcf9; border-color: var(--primary) !important;">
                  <h6 class="fw-bold text-dark small mb-1.5"><i class="fa-solid fa-eye me-1"></i> What to Monitor</h6>
                  <p class="mb-0 text-muted small leading-relaxed">Observe changes in alertness, fluid intake, and breathing patterns. Log another check if conditions alter.</p>
                </div>
              </div>
            </div>

            <!-- Parent Satisfaction Rating Collector -->
            <div class="p-4 rounded-3 text-center mb-4" style="background-color: var(--secondary); border: 1px solid rgba(15, 92, 94, 0.1);">
              <h6 class="fw-bold text-dark mb-1">How helpful was this assessment?</h6>
              <p class="text-muted small mb-3">Help us improve by rating your home health guidance experience below.</p>

              <!-- Interactive Stars Row -->
              <div class="d-flex justify-content-center gap-2 mb-2">
                <i class="fa-regular fa-star star-rating-btn" data-value="1" onclick="handlePostAssessmentRating(1)" style="font-size: 1.8rem; color: var(--accent-dark); cursor: pointer; transition: color 0.15s ease;"></i>
                <i class="fa-regular fa-star star-rating-btn" data-value="2" onclick="handlePostAssessmentRating(2)" style="font-size: 1.8rem; color: var(--accent-dark); cursor: pointer; transition: color 0.15s ease;"></i>
                <i class="fa-regular fa-star star-rating-btn" data-value="3" onclick="handlePostAssessmentRating(3)" style="font-size: 1.8rem; color: var(--accent-dark); cursor: pointer; transition: color 0.15s ease;"></i>
                <i class="fa-regular fa-star star-rating-btn" data-value="4" onclick="handlePostAssessmentRating(4)" style="font-size: 1.8rem; color: var(--accent-dark); cursor: pointer; transition: color 0.15s ease;"></i>
                <i class="fa-regular fa-star star-rating-btn" data-value="5" onclick="handlePostAssessmentRating(5)" style="font-size: 1.8rem; color: var(--accent-dark); cursor: pointer; transition: color 0.15s ease;"></i>
              </div>
              <div id="ratingFeedbackStatus" class="small fw-semibold text-teal"></div>
            </div>

            <!-- Contextual Navigation and Actions -->
            <div class="d-flex justify-content-center gap-3 flex-wrap">
              <button class="btn btn-secondary-custom py-2 px-4" onclick="switchDashSection('dashHome')">
                <i class="fa-solid fa-table-columns me-1.5"></i> Return Dashboard
              </button>
              <button class="btn btn-primary-custom py-2 px-4 d-none" id="btnResultBookAppointment" onclick="openAppointmentFromResult()">
                <i class="fa-solid fa-calendar-check me-1.5"></i> Schedule Consultation
              </button>
              <button class="btn btn-danger py-2 px-4 text-white d-none" id="btnResultHospitals" onclick="openNearestHospitals()" style="border-radius: 8px;">
                <i class="fa-solid fa-location-crosshairs me-1.5"></i> Find Nearest Hospital
              </button>
            </div>

          </div>
        </div>
      </div>

      <div id="dashHospitals" class="dash-section">
        <div class="card border-0 shadow-sm mx-auto" style="max-width: 920px; border-radius: 20px; overflow: hidden; background-color: var(--white);">

          <!-- Header -->
          <div style="background-color: var(--primary);" class="p-4 text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
              <div class="d-flex align-items-center gap-2 mb-1">
                <h3 class="fw-bold mb-1"><i class="fa-solid fa-truck-medical me-2"></i>Nearby Emergency Care</h3>
              </div>
              <p class="mb-0 small opacity-90">CareNest is using your current location to find the closest hospitals and the distance.</p>
            </div>
            <button class="btn btn-sm btn-light text-danger fw-semibold ms-auto" id="btnHospitalsBack" onclick="switchDashSection('dashResult')">
              <i class="fa-solid fa-arrow-left me-1"></i> Back to Result
            </button>
          </div>

          <div class="p-4 p-md-5">

            <!-- Emergency Status Card -->
            <div class="card border-2 mb-4 rounded-3 p-3 text-dark" style="border-color: var(--primary) !important; background-color: var(--primary-subtle) !important;">
              <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                <span class="badge text-white px-2.5 py-1.5 rounded-pill" style="background-color: var(--primary) !important;"><i class="fa-solid fa-triangle-exclamation me-1"></i> <span class="small text-muted fw-semibold" id="hospitalAssessmentTime" style="color: white !important;">--</span></span>

              </div>
              <h5 class="fw-bold mb-1 text-danger" id="hospitalChildName">Hospital Search</h5>
              <p class="mb-0 small fw-medium text-danger-emphasis" id="hospitalUrgencyMessage">
                Select a nearby hospital to call, contact, or navigate there.
              </p>
            </div>

            <!-- Emergency Summary Card -->
            <div id="doctorSummaryCard" class="card border border-1 border-secondary-subtle p-3 p-md-4 rounded-3 bg-light mb-4">
              <div class="d-flex justify-content-between align-items-center flex-wrap mb-2 gap-2">
                <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-file-medical text-teal me-2" style="color: var(--primary);"></i> What to Tell the Doctor</h5>
                <div class="d-flex gap-2">
                  <button class="btn btn-sm btn-outline-secondary py-1 px-3" onclick="copyEmergencySummary()"><i class="fa-solid fa-copy me-1"></i> Copy Summary</button>
                  <button class="btn btn-sm btn-success py-1 px-3 text-white" onclick="shareEmergencySummaryWhatsApp()"><i class="fa-brands fa-whatsapp me-1"></i> Share on WhatsApp</button>
                  <button class="btn btn-sm btn-primary-custom py-1 px-3" onclick="shareEmergencySummary()">
                    <i class="fa-solid fa-share-nodes me-1"></i> Share
                  </button>
                </div>
              </div>
              <p class="text-muted small mb-2">Show or send this structured assessment summary when arriving at the facility:</p>

              <div class="form-control bg-white text-dark" id="doctorEmergencySummary" style="font-size: 0.85rem; border-color: var(--border); height: 150px; overflow-y: auto; scroll-behavior: smooth; white-space: pre-wrap;"></div>
            </div>

            <!-- Location Permission and Loading / Error States -->
            <div id="hospitalLoadingState" class="text-center py-4 p-3 rounded-3 mb-4 bg-light border">
              <div class="spinner-border text-danger mb-2" role="status"><span class="visually-hidden">Loading...</span></div>
              <p class="mb-0 text-muted small fw-semibold" id="hospitalLoadingText"><i class="fa-solid fa-location-dot me-1 text-danger"></i> Finding your exact location and nearby hospitals…</p>
            </div>

            <div id="hospitalErrorState" class="alert alert-warning border-warning d-none text-center p-3 mb-4 rounded-3">
              <i class="fa-solid fa-triangle-exclamation text-warning mb-2" style="font-size: 2rem;"></i>
              <p class="mb-2 small fw-semibold text-dark" id="hospitalErrorMessage">Location permission denied or search failed.</p>
              <button class="btn btn-sm btn-danger px-3 py-1.5" onclick="openNearestHospitals()">
                <i class="fa-solid fa-rotate-right me-1"></i> Try Again
              </button>
            </div>

            <!-- Nearby Hospital Cards Container -->

            <div class="row g-3 mb-4" id="nearbyHospitalsList">
              <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-hospital-user text-danger me-2"></i> Closest Emergency Facilities</h5>
            </div>
            <div class="text-center mt-3">
              <button
                id="loadMoreHospitalsBtn"
                class="btn btn-outline-success d-none"
                onclick="loadMoreHospitals()">

                <i class="fa-solid fa-plus me-1"></i>
                Load More Hospitals
              </button>
            </div>
            <div id="hospitalAssessmentPrompt" class="alert text-center py-4 mb-4 d-none">

              <i class="fa-solid fa-clipboard-question fa-2x mb-3 text-primary"></i>

              <h6 class="fw-bold mb-2">
                No assessment selected
              </h6>

              <p class="small text-muted mb-3">
                Complete an assessment today to generate a detailed doctor summary.
              </p>

              <button class="btn btn-primary-custom"
                onclick="switchDashSection('dashAssessment')">
                <i class="fa-solid fa-stethoscope me-1"></i>
                Start Assessment
              </button>

            </div>

          </div>
        </div>
      </div>



      <div id="dashAppointment" class="dash-section">
        <div class="row g-4">

          <div class="col-lg-6">
            <div class="card p-4 border h-100">
              <h4 class="fw-bold mb-1" style="color: var(--primary);">
                <i class="fa-solid fa-calendar-plus me-1"></i> Book Consultation
              </h4>
              <p class="text-muted small mb-4">Request a dedicated checkup slot with your pediatrician.</p>

              <form id="appointmentForm" onsubmit="handleBookAppointment(event)">
                <div class="mb-3">
                  <label for="apptChild" class="form-label fw-semibold small">Choose Child Profile</label>
                  <select class="form-select form-control" id="apptChild" name="child_id" required>
                    <!-- Loaded dynamically -->
                  </select>
                </div>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="apptDate" class="form-label fw-semibold small">Date</label>
                    <input type="date" class="form-control" id="apptDate" name="appointment_date" required>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="apptTime" class="form-label fw-semibold small">Preferred Time</label>
                    <input type="time" class="form-control" id="apptTime" name="appointment_time" required>
                  </div>
                </div>
                <div class="mb-4">
                  <label for="apptReason" class="form-label fw-semibold small">Reason / Symptoms Checklist</label>
                  <textarea class="form-control" id="apptReason" name="reason" rows="7" placeholder="Describe any symptoms, temperatures, or checkup goals..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary-custom w-100 py-2">
                  <i class="fa-solid fa-check-to-slot me-1"></i> Request Consultation
                </button>
              </form>
            </div>
          </div>

          <div class="col-lg-6">
            <div class="card p-4 border h-100">
              <h5 class="fw-bold text-dark mb-3">
                <i class="fa-solid fa-calendar-days me-1"></i> Scheduled Consultations
              </h5>
              <div class="table-responsive">
                <table class="table table-hover align-middle" id="appointmentsDataTable">
                  <thead>
                    <tr class="table-light">
                      <th>Child Name</th>
                      <th>Date & Time</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody id="appointmentList">
                    <!-- Loaded dynamically -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>

        </div>
      </div>

      <div id="dashProfile" class="dash-section">
        <div class="card p-4 border mx-auto" style="max-width: 550px;">
          <h4 class="fw-bold mb-1" style="color: var(--primary);">
            <i class="fa-solid fa-user-gear me-1"></i> Account Settings
          </h4>
          <p class="text-muted small mb-4">Modify your account information.</p>

          <form id="profileForm" onsubmit="handleUpdateProfile(event)">
            <div class="mb-3">
              <label for="profName" class="form-label fw-semibold small">Your Name</label>
              <input type="text" class="form-control" id="profName" name="full_name" value="<?php echo "$fullName"; ?>" required>
            </div>
            <div class="mb-3">
              <label for="profEmail" class="form-label fw-semibold small">Email Address</label>
              <input type="email" class="form-control" id="profEmail" disabled value="<?php echo "$email"; ?>" required>
            </div>
            <div class="mb-3">
              <label for="profDateJoined" class="form-label fw-semibold small">Date Joined</label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted" style="border-radius: 8px 0 0 8px; border-color: var(--border);">
                  <i class="fa-solid fa-calendar-day"></i>
                </span>
                <input type="text" class="form-control bg-light border-start-0 text-muted" id="profDateJoined" disabled value="<?php echo isset($joinedDate) ? date('F j, Y', strtotime($joinedDate)) : 'N/A'; ?>" style="border-radius: 0 8px 8px 0; border-color: var(--border);">
              </div>
            </div>
            <div class="mb-3">
              <label for="profCountry" class="form-label fw-semibold small">Country</label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted" style="border-radius: 8px 0 0 8px; border-color: var(--border);">
                  <i class="fa-solid fa-globe"></i>
                </span>
                <input type="text" class="form-control border-start-0" id="profCountry" readonly name="country" value="<?php echo isset($country) ? htmlspecialchars($country) : 'Unknown'; ?>" required style="border-color: var(--border);">
                <button class="btn btn-secondary-custom" type="button" id="btnUpdateCountry" onclick="detectCountryForProfile()" style="border-radius: 0 8px 8px 0; border: 1px solid var(--border); border-left: none; padding: 0 16px;">
                  <i class="fa-solid fa-location-crosshairs me-1"></i> Detect
                </button>
              </div>
              <div id="countryStatus" class="form-text small text-muted mt-1"></div>
            </div>
            <div class="mb-4 border-top pt-3">
              <h6 class="fw-bold text-dark mb-3">Update Password</h6>
              <div class="mb-3">
                <label for="profCurrentPass" class="form-label fw-semibold small">Current Password</label>
                <input type="password" class="form-control" id="profCurrentPass" name="current_password" value="<?php echo $passwordHash; ?>" disabled>
              </div>
              <div class="mb-3">
                <label for="profNewPass" class="form-label fw-semibold small">New Password</label>
                <input type="password" class="form-control" id="profNewPass" name="new_password" placeholder="Minimum 6 characters">
              </div>
            </div>
            <button type="submit" class="btn btn-primary-custom">
              <i class="fa-solid fa-key me-1"></i> Save Settings
            </button>
          </form>
        </div>
      </div>

    </section>
  </div>

  <!-- Bootstrap 5 bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/2.3.8/js/dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/2.3.8/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
  <script>
    emailjs.init({
      publicKey: "Sd2JlDEa-A0biWBO2"
    });
  </script>

  <!-- Shared JS Code -->
  <script src="assets/js/app.js"></script>

  <script>
    const GEOAPIFY_API_KEY = "80c02f4074064f80826d21c6c60b7cbf";
    let currentUserLatitude = null;
    let currentUserLongitude = null;
    let selectedHospitalName = null;
    let selectedHospitalPhone = null;
    let selectedHospitalDuration = null;
    let activeHospitalAssessment = null;
    let activeHospitalChild = null;

    //variables to store the list of nearby hospitals and the number of displayed hospitals
    let allNearbyHospitals = [];
    let displayedHospitals = 0;

    function openNearestHospitals() {
      const assessments = (typeof getData === "function" ? getData("assessments") : null) || (typeof assessmentsList !== "undefined" ? assessmentsList : []);
      const children = (typeof getData === "function" ? getData("children") : null) || (typeof childrenList !== "undefined" ? childrenList : []);

      activeHospitalAssessment = null;
      activeHospitalChild = null;

      if (typeof selectedAssessmentId !== "undefined" && selectedAssessmentId) {
        activeHospitalAssessment = assessments.find(a =>
          Number(a.assessment_id || a.id) === Number(selectedAssessmentId)
        ) || null;
      }

      const hasAssessment = !!activeHospitalAssessment;

      document.getElementById("doctorSummaryCard").classList.toggle("d-none", !hasAssessment);
      document.getElementById("hospitalAssessmentPrompt").classList.toggle("d-none", hasAssessment);
      document.getElementById("btnHospitalsBack").classList.toggle("d-none", !hasAssessment);

      if (activeHospitalAssessment) {
        activeHospitalChild = children.find(c =>
          Number(c.child_id || c.id) === Number(activeHospitalAssessment.child_id)
        ) || null;
      }

      const childName = activeHospitalChild ?
        activeHospitalChild.child_name || activeHospitalChild.name || "Child" :
        "No assessment selected";

      const assessmentTime = activeHospitalAssessment ?
        activeHospitalAssessment.assessment_date || activeHospitalAssessment.date || "Recent assessment" :
        "Hospital search without assessment";

      document.getElementById("hospitalChildName").textContent = childName;
      document.getElementById("hospitalAssessmentTime").textContent = assessmentTime;
      document.getElementById("hospitalUrgencyMessage").textContent =
        activeHospitalAssessment ?
        "Immediate medical attention is recommended. Select a hospital below and leave as soon as possible." :
        " Complete an assessment today to prepare a structured summary for the doctor.";

      document.getElementById("hospitalAssessmentPrompt")
        ?.classList.toggle("d-none", Boolean(activeHospitalAssessment));

      switchDashSection("dashHospitals");
      document.getElementById("hospitalLoadingState").classList.remove("d-none");
      document.getElementById("hospitalErrorState").classList.add("d-none");
      document.getElementById("nearbyHospitalsList").innerHTML = "";

      selectedHospitalName = null;
      selectedHospitalPhone = null;
      selectedHospitalDuration = null;
      if (activeHospitalAssessment) {
        buildEmergencySummary();
      }

      if (!navigator.geolocation) {
        showHospitalError("Geolocation is not supported by your browser.");
        return;
      }

      navigator.geolocation.getCurrentPosition(
        function(position) {
          currentUserLatitude = position.coords.latitude;
          currentUserLongitude = position.coords.longitude;
          findNearbyHospitals(currentUserLatitude, currentUserLongitude);
        },
        function(error) {
          let msg = "Unable to retrieve your current location.";
          if (error.code === error.PERMISSION_DENIED) {
            msg = "Location permission was denied. Please allow access to find nearby emergency care.";
          } else if (error.code === error.POSITION_UNAVAILABLE) {
            msg = "Location information is unavailable.";
          } else if (error.code === error.TIMEOUT) {
            msg = "Location request timed out.";
          }
          showHospitalError(msg);
        }, {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 30000
        }
      );
    }

    function showHospitalError(message) {
      document.getElementById("hospitalLoadingState").classList.add("d-none");
      document.getElementById("hospitalErrorState").classList.remove("d-none");
      document.getElementById("hospitalErrorMessage").textContent = message;
    }

    async function findNearbyHospitals(lat, lng) {
      const loadingText = document.getElementById("hospitalLoadingText");

      loadingText.innerHTML = `
        <i class="fa-solid fa-spinner fa-spin me-1 text-danger"></i>
        Searching for nearby hospitals…
      `;

      const params = new URLSearchParams({
        categories: "healthcare.hospital",
        filter: `circle:${lng},${lat},10000`,
        bias: `proximity:${lng},${lat}`,
        limit: "10",
        lang: "en",
        apiKey: GEOAPIFY_API_KEY
      });

      const url = `https://api.geoapify.com/v2/places?${params.toString()}`;

      try {
        const response = await fetch(url);

        if (!response.ok) {
          throw new Error(`Geoapify error: ${response.status}`);
        }

        const data = await response.json();
        const features = data.features || [];

        if (features.length === 0) {
          showHospitalError(
            "No hospitals were found within 10 km. Try again or open your maps application."
          );
          return;
        }

        const hospitals = features.map(feature => {
          const properties = feature.properties || {};
          const coordinates = feature.geometry?.coordinates || [];

          const hospitalLat = Number(
            properties.lat ?? coordinates[1]
          );

          const hospitalLng = Number(
            properties.lon ?? coordinates[0]
          );

          const distanceMeters =
            Number(properties.distance) ||
            calculateHaversineDistance(
              lat,
              lng,
              hospitalLat,
              hospitalLng
            ) * 1000;

          return {
            name: properties.name ||
              properties.address_line1 ||
              "Hospital",

            latitude: hospitalLat,
            longitude: hospitalLng,

            type: "Hospital",

            address: properties.formatted || [properties.address_line1, properties.address_line2]
              .filter(Boolean)
              .join(", ") ||
              "Address unavailable",

            phone: properties.contact?.phone ||
              properties.phone ||
              "",

            website: properties.contact?.website ||
              properties.website ||
              "",

            placeId: properties.place_id || "",

            straightDistance: distanceMeters / 1000,
            distanceMeters,

            durationSeconds: (distanceMeters / 1000 / 40) * 3600
          };
        }).filter(hospital =>
          Number.isFinite(hospital.latitude) &&
          Number.isFinite(hospital.longitude)
        );

        hospitals.sort(
          (a, b) => a.distanceMeters - b.distanceMeters
        );

        const finalHospitals = hospitals.slice(0, 5);

        document
          .getElementById("hospitalLoadingState")
          .classList.add("d-none");

        allNearbyHospitals = hospitals;
        displayedHospitals = 0;

        loadMoreHospitals();

      } catch (error) {
        console.error(
          "Geoapify hospital search failed:",
          error
        );

        showHospitalError(
          "The hospital search service is temporarily unavailable. Please try again."
        );
      }
    }

    function normalizePhoneForCall(phone) {
      return String(phone || "").trim().replace(/[^\d+]/g, "");
    }

    function normalizePhoneForWhatsApp(phone) {
      let cleaned = String(phone || "").replace(/\D/g, "");
      if (cleaned.startsWith("00")) cleaned = cleaned.substring(2);
      return cleaned;
    }

    function callHospital(phone, hospitalName) {
      const cleanPhone = normalizePhoneForCall(phone);
      if (!cleanPhone) {
        displaySystemMessage?.(`No phone number is available for ${hospitalName}.`, "warning");
        return;
      }
      window.location.href = `tel:${cleanPhone}`;
    }

    function shareSummaryToHospitalWhatsApp(phone, hospitalName) {
      const cleanPhone = normalizePhoneForWhatsApp(phone);
      if (!cleanPhone) {
        displaySystemMessage?.(`No WhatsApp-compatible number is available for ${hospitalName}.`, "warning");
        return;
      }

      selectedHospitalName = hospitalName;
      selectedHospitalPhone = phone;
      const summary = buildEmergencySummary();
      const message = `Hello ${hospitalName},\n\n${summary}\n\nPlease let us know if the child can be received for urgent medical evaluation.`;

      window.open(`https://wa.me/${cleanPhone}?text=${encodeURIComponent(message)}`, "_blank");
    }

    function calculateHaversineDistance(lat1, lon1, lat2, lon2) {
      const R = 6371;
      const dLat = (lat2 - lat1) * Math.PI / 180;
      const dLon = (lon2 - lon1) * Math.PI / 180;
      const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLon / 2) * Math.sin(dLon / 2);
      const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
      return R * c;
    }

    function sanitizeText(str) {
      const temp = document.createElement('div');
      temp.textContent = str || '';
      return temp.innerHTML;
    }

    function renderNearbyHospitals(hospitals, append = false) {
      const container = document.getElementById("nearbyHospitalsList");
      if (!append) {
        container.innerHTML = "";
      }

      if (!hospitals || hospitals.length === 0) {
        showHospitalError("No hospital facilities were found nearby.");
        return;
      }

      hospitals.forEach((h, index) => {
        const km = (h.distanceMeters / 1000).toFixed(1);
        const mins = Math.max(1, Math.round(h.durationSeconds / 60));
        const hospitalPosition = (append ? displayedHospitals : 0) + index;
        const isClosest = hospitalPosition === 0;
        const safeName = sanitizeText(h.name);
        const safeType = sanitizeText(h.type);
        const safeAddress = sanitizeText(h.address);
        const phone = h.phone || "";
        const safePhone = sanitizeText(phone);
        const jsName = JSON.stringify(
          String(h.name || "Hospital")
        );

        const jsPhone = JSON.stringify(
          String(phone || "")
        );

        const cardHtml = `
        <div class="col-md-6">
          <div class="p-3 hospital-card ${isClosest?"closest-hospital":""} h-100 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                <h6 class="fw-bold text-dark mb-0">${safeName}</h6>
                ${isClosest?'<span class="badge text-white" style="background-color: var(--primary);"><i class="fa-solid fa-star me-1"></i>Closest</span>':""}
              </div>

              <div class="d-flex align-items-center gap-3 mb-2 small text-muted">
                <span class="fw-bold" style="color: var(--primary);">
                  <i class="fa-solid fa-clock me-1" ></i>About ${mins} mins
                </span>
                <span>
                  <i class="fa-solid fa-route me-1"></i>${km} km
                </span>
              </div>

              <p class="small text-muted mb-1">
                <i class="fa-solid fa-building-columns me-1"></i>${safeType}
              </p>

              <p class="small text-muted mb-2">
                <i class="fa-solid fa-location-dot me-1"></i>${safeAddress}
              </p>

              ${phone?`
              <p class="small text-dark fw-semibold mb-2">
                <i class="fa-solid fa-phone me-1 text-success"></i>${safePhone}
              </p>`:`
              <p class="small text-muted mb-2">
                <i class="fa-solid fa-phone-slash me-1"></i>Phone number unavailable
              </p>`}
            </div>

            <div class="d-grid gap-2 mt-2 pt-2 border-top">
              <button class="btn btn-sm fw-semibold" style="background-color: var(--accent);"
                onclick='navigateToHospital(${h.latitude},${h.longitude},${jsName},${jsPhone},${mins})'">
                <i class="fa-solid fa-diamond-turn-right me-1"></i>Navigate Now
              </button>

              ${phone?`
              <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-success flex-grow-1"
                  onclick='callHospital(${jsPhone},${jsName})'>
                  <i class="fa-solid fa-phone me-1"></i>Call
                </button>

                <button class="btn btn-sm btn-success flex-grow-1 text-white"
                  onclick='shareSummaryToHospitalWhatsApp(${jsPhone},${jsName})'>
                  <i class="fa-brands fa-whatsapp me-1"></i>WhatsApp
                </button>
              </div>`:""}

              <a href="https://www.google.com/maps/search/?api=1&query=${h.latitude},${h.longitude}"
                target="_blank" rel="noopener"
                class="btn btn-sm btn-outline-secondary">
                <i class="fa-solid fa-map-location-dot me-1"></i>View on Map
              </a>
            </div>
          </div>
        </div>`;
        container.innerHTML += cardHtml;
      });

      if (hospitals.length > 0 && !selectedHospitalName) {
        selectedHospitalName = hospitals[0].name;
        selectedHospitalPhone = hospitals[0].phone || null;
        selectedHospitalDuration = Math.max(1, Math.round(hospitals[0].durationSeconds / 60));
      }
      if (activeHospitalAssessment) {
        buildEmergencySummary();
      }
    }

    function navigateToHospital(lat, lng, hospitalName, hospitalPhone, durationMinutes) {
      selectedHospitalName = hospitalName || "Selected hospital";
      selectedHospitalPhone = hospitalPhone || null;
      selectedHospitalDuration = durationMinutes || null;
      if (activeHospitalAssessment) {
        buildEmergencySummary();
      }

      const origin = currentUserLatitude && currentUserLongitude ?
        `&origin=${currentUserLatitude},${currentUserLongitude}` :
        "";

      const navUrl = `https://www.google.com/maps/dir/?api=1${origin}&destination=${lat},${lng}&travelmode=driving`;
      window.open(navUrl, "_blank", "noopener");
    }

    function buildEmergencySummary() {
      const summaryEl = document.getElementById("doctorEmergencySummary");
      let summaryText = "";

      if (!activeHospitalAssessment) {
        summaryText =
          `CARENEST HOSPITAL VISIT NOTE

    No CareNest assessment has been completed or selected for this hospital search.

    `;
      } else {
        const child = activeHospitalChild;
        const childName = child ? (child.child_name || child.name || "Child") : "Child";
        const ageStr = getChildAgeText(child?.dob);
        const dateStr = activeHospitalAssessment.assessment_date ||
          activeHospitalAssessment.date ||
          new Date().toLocaleString();

        const severity = activeHospitalAssessment.severity || "Not stated";
        const conditions = child?.previous_diagnoses || "None reported";
        const recommendation = activeHospitalAssessment.recommendation ||
          "Please evaluate the child based on the reported symptoms.";

        summaryText =
          `CARENEST EMERGENCY ASSESSMENT SUMMARY

    Child: ${childName}
    Age: ${ageStr}
    Assessment time: ${dateStr}
    Risk level: ${severity}

    Existing medical conditions: ${conditions}

    CareNest recommendation: ${recommendation}

    Selected hospital: ${selectedHospitalName||"Not selected"}
    ${selectedHospitalDuration?`Estimated driving time: Approximately ${selectedHospitalDuration} minutes\n`:""}
    Please evaluate the child urgently.`;
      }

      summaryText = summaryText.replace(/^ {4}/gm, "");

      if (summaryEl) {
        const summaryHtml = escapeSummaryHtml(summaryText).replace(
          /^([ \t]*)(CARENEST EMERGENCY ASSESSMENT SUMMARY|Child:|Age:|Assessment time:|Risk level:|Existing medical conditions:|CareNest recommendation:|Selected hospital:|Estimated driving time:)/gm,
          "$1<strong>$2</strong>"
        );
        summaryEl.innerHTML = summaryHtml;
      }
      return summaryText;
    }

    function escapeSummaryHtml(text) {
      return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
    }


    function copyEmergencySummary() {
      const summaryText = buildEmergencySummary();
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(summaryText).then(() => {
          if (typeof displaySystemMessage === 'function') {
            displaySystemMessage("Emergency summary copied to clipboard!", "success");
          }
        }).catch(() => fallbackCopy(summaryText));
      } else {
        fallbackCopy(summaryText);
      }
    }

    function fallbackCopy(text) {
      const textarea = document.createElement("textarea");
      textarea.value = text;
      textarea.style.position = "fixed";
      textarea.style.opacity = "0";
      document.body.appendChild(textarea);
      textarea.select();
      document.execCommand("copy");
      textarea.remove();
      if (typeof displaySystemMessage === "function") {
        displaySystemMessage("Emergency summary copied!", "success");
      }
    }

    function shareEmergencySummaryWhatsApp() {
      if (!activeHospitalAssessment) {
        return;
      }
      const summaryText = buildEmergencySummary();
      const waUrl = `https://wa.me/?text=${encodeURIComponent(summaryText)}`;
      window.open(waUrl, '_blank');
    }


    function getChildAgeText(dob) {
      if (!dob) return "Unknown";
      const birth = new Date(dob);
      if (Number.isNaN(birth.getTime())) return "Unknown";

      const today = new Date();
      let years = today.getFullYear() - birth.getFullYear();
      let months = today.getMonth() - birth.getMonth();

      if (today.getDate() < birth.getDate()) months--;
      if (months < 0) {
        years--;
        months += 12;
      }

      if (years < 1) return `${Math.max(0,months)} months`;
      return `${years} year${years===1?"":"s"}`;
    }

    function safelyParseJson(value) {
      if (!value) return null;
      if (typeof value === "object") return value;

      try {
        return JSON.parse(value);
      } catch (error) {
        return null;
      }
    }

    function extractAssessmentWarningSigns(assessment) {
      const questions = safelyParseJson(
        assessment.questions_json ||
        assessment.questions
      );

      const answers = safelyParseJson(
        assessment.answers_json ||
        assessment.answers ||
        assessment.symptoms_json
      );

      if (!answers) return "- Symptoms were assessed, but detailed answers are unavailable.";

      const lines = [];

      if (Array.isArray(answers)) {
        answers.forEach((answer, index) => {
          const value = typeof answer === "object" ?
            answer.answer || answer.value || answer.response :
            answer;

          if (value === undefined || value === null || String(value).trim() === "") return;

          const question = Array.isArray(questions) ?
            questions[index]?.question || questions[index]?.text || `Question ${index+1}` :
            `Question ${index+1}`;

          lines.push(`- ${question}: ${value}`);
        });
      } else {
        Object.entries(answers).forEach(([key, value]) => {
          if (value === undefined || value === null || String(value).trim() === "") return;

          const readableKey = key
            .replace(/_/g, " ")
            .replace(/\b\w/g, char => char.toUpperCase());

          lines.push(`- ${readableKey}: ${typeof value==="object"?JSON.stringify(value):value}`);
        });
      }

      return lines.length ?
        lines.join("\n") :
        "- No detailed warning signs were saved.";
    }



    //function to share the emergency summary using the Web Share API
    async function shareEmergencySummary() {
      if (!activeHospitalAssessment) {
        return;
      }
      const summary = buildEmergencySummary();

      if (navigator.share) {
        try {
          await navigator.share({
            title: "CareNest Emergency Summary",
            text: summary
          });
        } catch (error) {
          if (error.name !== "AbortError") {
            console.error("Sharing failed:", error);
          }
        }
        return;
      }

      copyEmergencySummary();
      displaySystemMessage?.(
        "Sharing is unavailable on this browser. The summary was copied instead.",
        "info"
      );
    }

    function loadMoreHospitals() {
      const next = allNearbyHospitals.slice(
        displayedHospitals,
        displayedHospitals + 2
      );

      renderNearbyHospitals(next, true);

      displayedHospitals += next.length;

      document.getElementById("loadMoreHospitalsBtn")
        .classList.toggle(
          "d-none",
          displayedHospitals >= allNearbyHospitals.length
        );
    }
  </script>
</body>

</html>
