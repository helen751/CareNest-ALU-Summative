// defining arrays to store data in localStorage
let dashboardUser = null;
let childrenList = [];
let assessmentsList = [];
let appointmentsList = [];
let selectedAssessmentId = null;

// function to detect user's country for profile using GPS and fallback to IP lookup
async function detectCountryForProfile() {
    const btn = document.getElementById('btnUpdateCountry');
    const status = document.getElementById('countryStatus');
    const countryInput = document.getElementById('profCountry');
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
    status.className = "form-text small text-primary";
    status.textContent = "Accessing GPS location...";

    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        async function (position) {
          const lat = position.coords.latitude;
          const lon = position.coords.longitude;
          try {
            const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&zoom=3&addressdetails=1`);
            const data = await res.json();
            
            if (data && data.address && data.address.country) {
              countryInput.value = data.address.country;
              status.className = "form-text small text-success";
              status.textContent = `Located country: ${data.address.country}`;
            } else {
              await fallbackProfileIPLookup();
            }
          } catch (err) {
            await fallbackProfileIPLookup();
          } finally {
            resetCountryBtn();
          }
        },
        async function (error) {
          await fallbackProfileIPLookup();
          resetCountryBtn();
        }
      );
    } else {
      await fallbackProfileIPLookup();
      resetCountryBtn();
    }

    async function fallbackProfileIPLookup() {
      status.textContent = "GPS unavailable. Running IP lookup...";
      try {
        const response = await fetch('https://ipapi.co/json/');
        const data = await response.json();
        if (data && data.country_name) {
          countryInput.value = data.country_name;
          status.className = "form-text small text-success";
          status.textContent = `Located country: ${data.country_name}`;
        } else {
          status.className = "form-text small text-warning";
          status.textContent = "Could not resolve country automatically.";
        }
      } catch (err) {
        status.className = "form-text small text-danger";
        status.textContent = "Error contacting location services.";
      }
    }

    function resetCountryBtn() {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-location-crosshairs me-1"></i> Detect';
    }
  }


// function to load dashboard data from the server and update the UI
console.log("app.js loaded");
async function loadDashboardData() {
  console.log("loadDashboardData() called");
  try {
    const response = await fetch("process.php?action=get_dashboard");
    const result = await response.json();

    if (!result.success) {
      window.location.href = "login.html";
      return;
    }

    dashboardUser = result.user;
    childrenList = result.children || [];
    assessmentsList = result.assessments || [];
    appointmentsList = result.appointments || [];

    updateDashboardData();
    renderWellbeingChart();
  } catch (error) {
    displaySystemMessage(
      "Could not load your dashboard information.",
      "danger"
    );
  }
}

function displaySystemMessage(text, type = "success") {
  if (typeof Swal !== "undefined") {
    const icon = type === "danger" ? "error" : type;
    const titles = {
      success: "Success",
      error: "Unable to Complete",
      warning: "Please Check",
      info: "Information"
    };

    Swal.fire({
      title: titles[icon] || "CareNest",
      text,
      icon,
      confirmButtonColor: "#0F5C5E"
    });
    return;
  }

  const container = document.getElementById("alertContainer");
  if (!container) return;
  
  container.innerHTML = `
    <div class="alert alert-${type} alert-dismissible fade show shadow-sm" role="alert" style="border-radius: 10px;">
      <i class="fa-solid ${type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'} me-2"></i>
      ${text}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  `;
  setTimeout(() => {
    const alertEl = container.querySelector(".alert");
    if (alertEl) {
      const bsAlert = new bootstrap.Alert(alertEl);
      bsAlert.close();
    }
  }, 4500);
}

function calculateAge(dobStr) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const dob = parseProfileDate(dobStr);

    if (!dob) return "Unknown";

    if (dob > today) {
        let monthsUntil = 0;
        while (
          addCalendarMonths(today, monthsUntil + 1) <= dob
        ) {
          monthsUntil++;
        }

        const monthAnchor = addCalendarMonths(today, monthsUntil);
        const daysUntil = Math.max(
          0,
          Math.round((dob - monthAnchor) / 86400000)
        );
        const parts = [];

        if (monthsUntil > 0) {
          parts.push(
            `${monthsUntil} month${monthsUntil === 1 ? "" : "s"}`
          );
        }
        if (daysUntil > 0) {
          parts.push(`${daysUntil} day${daysUntil === 1 ? "" : "s"}`);
        }

        return `Expected in: ${parts.join(" ") || "less than a day"}`;
    }

    let years = today.getFullYear() - dob.getFullYear();
    let months = today.getMonth() - dob.getMonth();
    let days = today.getDate() - dob.getDate();

    if (days < 0) {
        months--;
        const previousMonth = new Date(today.getFullYear(), today.getMonth(), 0);
        days += previousMonth.getDate();
    }

    if (months < 0) {
        years--;
        months += 12;
    }

    if (years > 0) {
        if (months > 0) {
            return `${years} year${years > 1 ? "s" : ""} ${months} month${months > 1 ? "s" : ""}`;
        }
        return `${years} year${years > 1 ? "s" : ""}`;
    }

    if (months > 0) {
        if (days > 0) {
            return `${months} month${months > 1 ? "s" : ""} ${days} day${days > 1 ? "s" : ""}`;
        }
        return `${months} month${months > 1 ? "s" : ""}`;
    }

    return `${days} day${days !== 1 ? "s" : ""}`;
}

function parseProfileDate(dateString) {
  const parts = String(dateString || "").split("-").map(Number);
  if (parts.length !== 3 || parts.some(Number.isNaN)) return null;

  const date = new Date(parts[0], parts[1] - 1, parts[2]);
  if (
    date.getFullYear() !== parts[0] ||
    date.getMonth() !== parts[1] - 1 ||
    date.getDate() !== parts[2]
  ) {
    return null;
  }

  date.setHours(0, 0, 0, 0);
  return date;
}

function addCalendarMonths(date, months) {
  const result = new Date(date);
  const targetMonth = result.getMonth() + months;
  const day = result.getDate();

  result.setDate(1);
  result.setMonth(targetMonth);
  const lastDay = new Date(
    result.getFullYear(),
    result.getMonth() + 1,
    0
  ).getDate();
  result.setDate(Math.min(day, lastDay));

  return result;
}

function isPregnancyProfile(dob) {
  const profileDate = parseProfileDate(dob);
  if (!profileDate) return false;

  const today = new Date();
  today.setHours(0, 0, 0, 0);
  return profileDate > today;
}

function getPregnancyStage(dob) {
  const expectedDate = parseProfileDate(dob);
  if (!expectedDate) return "Pregnancy stage unavailable";

  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const estimatedStart = addCalendarMonths(expectedDate, -9);
  const elapsedDays = Math.max(
    0,
    Math.floor((today - estimatedStart) / 86400000)
  );
  const weeks = Math.min(40, Math.floor(elapsedDays / 7));
  let months =
    (today.getFullYear() - estimatedStart.getFullYear()) * 12 +
    today.getMonth() - estimatedStart.getMonth();
  if (today.getDate() < estimatedStart.getDate()) months--;
  months = Math.min(9, Math.max(0, months));

  return `Approximately ${months} month${months === 1 ? "" : "s"} (${weeks} weeks) pregnant`;
}

async function validateProfileDate(dateString, requireConfirmation = true) {
  const profileDate = parseProfileDate(dateString);
  if (!profileDate) {
    displaySystemMessage("Please enter a valid date.", "danger");
    return false;
  }

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  if (profileDate > today) {
    const maximumExpectedDate = addCalendarMonths(today, 9);
    if (profileDate > maximumExpectedDate) {
      displaySystemMessage(
        "The expected delivery date cannot be more than nine calendar months from today.",
        "danger"
      );
      return false;
    }

    if (requireConfirmation) {
      const choice = await Swal.fire({
        title: "Confirm Pregnancy Profile",
        text: `The selected date (${profileDate.toLocaleDateString()}) is in the future and will be saved as the expected delivery date. ${getPregnancyStage(dateString)}. CareNest supports pregnancy profiles, allowing expecting mothers to record their daily symptoms and receive pregnancy-aware health guidance.`,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Yes, Register Profile",
        cancelButtonText: "Cancel",
        confirmButtonColor: "#0F5C5E",
        cancelButtonColor: "#6c757d",
        reverseButtons: true
      });

      if (!choice.isConfirmed) return false;
    }

    return true;
  }

  const oldestAllowedDate = new Date(today);
  oldestAllowedDate.setFullYear(oldestAllowedDate.getFullYear() - 12);
  if (profileDate < oldestAllowedDate) {
    displaySystemMessage(
      "Born children must be 12 years old or younger.",
      "danger"
    );
    return false;
  }

  return true;
}

// function to handle user logout and redirect to login page
async function handleLogout() {
  try {
    await fetch("process.php?action=logout");
  } finally {
    window.location.href = "login.html";
  }
}

let wellbeingChartInstance = null;
// function to render the wellbeing chart using Chart.js
function renderWellbeingChart() {
  const ctx = document.getElementById("wellbeingChart");

  if (!ctx) return;

  if (wellbeingChartInstance) {
    wellbeingChartInstance.destroy();
  }

  const labels = [];
  const chartDatasets = [];

  const sortedAssessments = [...assessmentsList].sort(
    (a, b) => new Date(a.date) - new Date(b.date)
  );

  childrenList.forEach((child, index) => {
    const history = sortedAssessments.filter(
      item => Number(item.child_id) === Number(child.child_id)
    );

    const dataPoints = [];

    history.forEach(item => {
      let score = 95;

      if (item.severity === "Medium") score = 65;
      if (item.severity === "High") score = 25;

      dataPoints.push({
        x: item.date,
        y: score
      });

      if (!labels.includes(item.date)) {
        labels.push(item.date);
      }
    });

    const colors = [
      {
        border: "#0F5C5E",
        background: "rgba(15, 92, 94, 0.08)"
      },
      {
        border: "#F3B7A4",
        background: "rgba(243, 183, 164, 0.08)"
      },
      {
        border: "#5C7F80",
        background: "rgba(92, 127, 128, 0.08)"
      }
    ];

    const childColor = colors[index % colors.length];

    if (dataPoints.length > 0) {
      chartDatasets.push({
        label: child.name,
        data: dataPoints,
        borderColor: childColor.border,
        backgroundColor: childColor.background,
        borderWidth: 3,
        tension: 0.15,
        fill: true,
        pointRadius: 6,
        pointHoverRadius: 8
      });
    }
  });

  labels.sort((a, b) => new Date(a) - new Date(b));

  wellbeingChartInstance = new Chart(ctx, {
    type: "line",
    data: {
      labels: labels.length ? labels : ["No Data"],
      datasets: chartDatasets.length
        ? chartDatasets
        : [
            {
              label: "No assessment data",
              data: [],
              borderColor: "#0F5C5E"
            }
          ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: "top",
          labels: {
            font: {
              family: "Inter",
              weight: "600"
            }
          }
        }
      },
      scales: {
        y: {
          min: 0,
          max: 100,
          ticks: {
            stepSize: 20,
            callback(value) {
              return value + "%";
            }
          }
        }
      }
    }
  });
}

function switchDashSection(sectionId) {
  document.querySelectorAll(".dash-section").forEach(sec => {
    sec.classList.remove("active-section");
  });
  
  const targetSec = document.getElementById(sectionId);
  if (targetSec) {
    targetSec.classList.add("active-section");
  }

  document.querySelectorAll(".side-menu nav a").forEach(link => {
    link.classList.remove("active");
  });

  const linksMap = {
    'dashHome': 'linkDashHome',
    'dashMyChildren': 'linkMyChildren',
    'dashAddChild': 'linkAddChild',
    'dashChildProfile': 'linkChildProfile',
    'dashAssessment': 'linkAssessment',
    'dashResult': 'linkResult',
    'dashAppointment': 'linkAppointment',
    'dashProfile': 'linkProfile'
  };

  const linkId = linksMap[sectionId];
  if (linkId) {
    const el = document.getElementById(linkId);
    if (el) el.classList.add("active");
  }

  if (sectionId === 'dashAssessment') {
    resetAssessmentWizard();
  }

  if (sectionId === 'dashHome') {
    updateDashboardData();
    setTimeout(renderWellbeingChart, 150);
  }

  closeMobileSidebar();
}

function toggleMobileSidebar() {
  const sidebar = document.getElementById("dashboardSidebar");
  const shouldOpen = !sidebar?.classList.contains("mobile-open");

  if (shouldOpen) {
    openMobileSidebar();
  } else {
    closeMobileSidebar();
  }
}

function openMobileSidebar() {
  if (!window.matchMedia("(max-width: 991px)").matches) return;

  document.getElementById("dashboardSidebar")?.classList.add("mobile-open");
  document.getElementById("mobileSidebarBackdrop")?.classList.add("active");
  document.getElementById("mobileSidebarToggle")
    ?.setAttribute("aria-expanded", "true");
  document.body.classList.add("mobile-sidebar-open");
}

function closeMobileSidebar() {
  document.getElementById("dashboardSidebar")?.classList.remove("mobile-open");
  document.getElementById("mobileSidebarBackdrop")?.classList.remove("active");
  document.getElementById("mobileSidebarToggle")
    ?.setAttribute("aria-expanded", "false");
  document.body.classList.remove("mobile-sidebar-open");
}

function updateDashboardData() {
  if (!dashboardUser) return;

  document.getElementById("profName").value = dashboardUser.full_name;
  document.getElementById("profEmail").value = dashboardUser.email;
  document.getElementById("profCountry").value = dashboardUser.country ?? "";
  document.getElementById("profCurrentPass").value = dashboardUser.password_hash;
  document.getElementById("sidebarGuardianName").textContent = dashboardUser.full_name;
  document.getElementById("dashWelcomeTitle").textContent = `Welcome back, ${dashboardUser.full_name.split(' ')[0]}!`;
  document.getElementById("summaryChildCount").textContent = childrenList.length;
  document.getElementById("summaryAssessmentCount").textContent = assessmentsList.length;
  document.getElementById("summaryAppointmentCount").textContent = appointmentsList.length;

  const latestResult = document.getElementById("summaryLastResult");

  if (assessmentsList.length > 0) {
    const latest = assessmentsList[assessmentsList.length - 1];

    latestResult.textContent =
      `Latest: ${latest.severity} Severity`;
  } else {
    latestResult.textContent = "No assessments yet";
  }

  renderRecentAssessments();
  renderChildren();
  populateDropdowns();
  renderAppointmentsList();
}

function populateDropdowns() {
  const assSelect = document.getElementById("assessmentTargetChild");
  const apptSelect = document.getElementById("apptChild");

  if (assSelect && apptSelect) {
    assSelect.innerHTML = "";
    apptSelect.innerHTML = "";

    if (childrenList.length === 0) {
      const fallback = `<option value="">-- No profiles registered --</option>`;
      assSelect.innerHTML = fallback;
      apptSelect.innerHTML = fallback;
    } else {
      childrenList.forEach(c => {
        const opt = `<option value="${c.child_id}">${c.name}</option>`;
        assSelect.innerHTML += opt;
        apptSelect.innerHTML += opt;
      });
    }
  }

  renderAppointmentsList();
}

function renderRecentAssessments() {
  const table = document.getElementById("recentAssessmentsTable");

  if (!table) return;

  destroyDataTable("recentAssessmentsDataTable");
  table.innerHTML = "";

  if (assessmentsList.length === 0) {
    table.innerHTML = `
      <tr>
        <td colspan="5" class="text-center text-muted py-4">
          No assessment history yet.
        </td>
      </tr>
    `;
    return;
  }

  [...assessmentsList].reverse().forEach(item => {
    const child = childrenList.find(
      entry => Number(entry.child_id) === Number(item.child_id)
    );

    const childName = child ? child.name : "Child";
    const firstLetter = childName.charAt(0).toUpperCase();

    let badgeClass = "bg-success-subtle text-success";
    let badgeIcon = "fa-circle-check";

    if (item.severity === "Medium") {
      badgeClass = "bg-warning-subtle text-warning-emphasis";
      badgeIcon = "fa-circle-exclamation";
    }

    if (item.severity === "High") {
      badgeClass = "bg-danger-subtle text-danger";
      badgeIcon = "fa-triangle-exclamation";
    }

    table.innerHTML += `
      <tr>
        <td class="ps-3 py-3">
          <div class="d-flex align-items-center gap-2">
            <div class="avatar-circle">${firstLetter}</div>
            <strong>${childName}</strong>
          </div>
        </td>

        <td>${item.date}</td>

        <td>
          <span class="badge ${badgeClass} px-2 py-1 rounded-pill">
            <i class="fa-solid ${badgeIcon} me-1"></i>
            ${item.severity} Severity
          </span>
        </td>

        <td>
          <span
            class="small text-muted d-inline-block text-truncate"
            style="max-width: 250px;"
          >
            ${item.recommendation}
          </span>
        </td>

        <td class="text-end pe-3">
          <button
            class="btn btn-secondary-custom btn-sm"
            onclick="loadSingleResult(${item.assessment_id})"
          >
            <i class="fa-solid fa-file-invoice me-1"></i>
            View Report
          </button>
        </td>
      </tr>
    `;
  });

  initializeDataTable("recentAssessmentsDataTable", 5, {
    order: [[1, "desc"]],
    columnDefs: [{ orderable: false, targets: 4 }]
  });
}

function destroyDataTable(tableId) {
  if (
    window.jQuery &&
    jQuery.fn.DataTable &&
    jQuery.fn.dataTable.isDataTable(`#${tableId}`)
  ) {
    jQuery(`#${tableId}`).DataTable().destroy();
  }
}

function initializeDataTable(tableId, pageLength, options = {}) {
  if (!window.jQuery || !jQuery.fn.DataTable) return;

  jQuery(`#${tableId}`).DataTable({
    pageLength,
    lengthChange: false,
    ...options
  });
}


function renderChildren() {
  const grid = document.getElementById("childrenGrid");

  if (!grid) return;

  grid.innerHTML = "";

  if (childrenList.length === 0) {
    grid.innerHTML = `
      <div class="col-12 text-center py-5 bg-white border rounded">
        <i class="fa-solid fa-children text-muted mb-3"
           style="font-size: 3rem;"></i>

        <p class="text-muted">No child profiles registered yet.</p>

        <button
          class="btn btn-primary-custom btn-sm"
          onclick="switchDashSection('dashAddChild')"
        >
          Add Your First Child
        </button>
      </div>
    `;

    return;
  }

  childrenList.forEach(child => {
    const age = calculateAge(child.dob);
    const profileAgeLabel = isPregnancyProfile(child.dob)
      ? age
      : `Age: ${age}`;

    grid.innerHTML += `
      <div class="col-md-4">
        <div class="child-card h-100">
          <div class="child-card-img">
            <i class="fa-solid fa-children"></i>
          </div>

          <div class="p-3 text-center">
            <h5 class="fw-bold mb-1">${child.name}</h5>

            <p class="text-muted small mb-3">
              ${profileAgeLabel} | Gender: ${child.gender}
            </p>

            <button
              class="btn btn-secondary-custom w-100 btn-sm"
              onclick="accessChildProfile(${child.child_id})"
            >
              <i class="fa-solid fa-folder-open me-1"></i>
              Access Profile
            </button>
          </div>
        </div>
      </div>
    `;
  });
}

async function handleSaveChild(event) {
  event.preventDefault();

  const form = document.getElementById("childForm");
  if (!await validateProfileDate(form.elements.dob.value)) return;

  const formData = new FormData(form);

  try {
    const response = await fetch("process.php?action=add_child", {
      method: "POST",
      body: formData
    });

    const result = await response.json();

    if (!result.success) {
      displaySystemMessage(result.message, "danger");
      return;
    }

    displaySystemMessage(result.message, "success");
    form.reset();

    await loadDashboardData();
    switchDashSection("dashMyChildren");
  } catch (error) {
    displaySystemMessage(
      "Could not save the child profile.",
      "danger"
    );
  }
}

function renderChildHistory(childId) {

    const historyTable = document.getElementById("childIndividualHistory");

    if (!historyTable) return;

    destroyDataTable("childHistoryDataTable");
    historyTable.innerHTML = "";

    const history = assessmentsList.filter(
        assessment => Number(assessment.child_id) === Number(childId)
    );

    if (history.length === 0) {
        historyTable.innerHTML = `
            <tr>
                <td colspan="3" class="text-center text-muted py-3">
                    No previous assessments found.
                </td>
            </tr>
        `;
        return;
    }

    history.forEach(item => {
        const recommendation = String(item.recommendation || "");
        const recommendationPreview = recommendation.length > 100
            ? `${recommendation.slice(0, 100)}...`
            : recommendation;

        let badge = `
            <span class="badge bg-success-subtle text-success">
                ${item.severity}
            </span>
        `;

        if (item.severity === "Medium") {
            badge = `
                <span class="badge bg-warning-subtle text-warning-emphasis">
                    ${item.severity}
                </span>
            `;
        }

        if (item.severity === "High") {
            badge = `
                <span class="badge bg-danger-subtle text-danger">
                    ${item.severity}
                </span>
            `;
        }

        historyTable.innerHTML += `
            <tr>
                <td>${item.date}</td>
                <td>${badge}</td>
                <td>
                    <span class="d-block mb-2">${recommendationPreview}</span>
                    <button
                        class="btn btn-secondary-custom btn-sm"
                        onclick="loadSingleResult(${item.assessment_id})"
                    >
                        <i class="fa-solid fa-file-invoice me-1"></i>
                        View Full Report
                    </button>
                </td>
            </tr>
        `;
    });

    initializeDataTable("childHistoryDataTable", 3, {
      order: [[0, "desc"]]
    });
}

function accessChildProfile(childId) {
  const child = childrenList.find(
    item => Number(item.child_id) === Number(childId)
  );

  if (!child) return;

  document.getElementById("profileChildName").textContent =
    child.name;

  document.getElementById("profileChildAgeGender").textContent =
    `${isPregnancyProfile(child.dob) ? calculateAge(child.dob) : `Age: ${calculateAge(child.dob)}`} • Gender: ${child.gender}`;

  document.getElementById("editChildId").value =
    child.child_id;

  document.getElementById("editChildName").value =
    child.name;

  document.getElementById("editChildDob").value =
    child.dob;

  document.getElementById("editChildGender").value =
    child.gender;

  document.getElementById("editPreviousDiagnoses").value =
    child.previous_diagnoses || "";

  renderChildHistory(childId);

  document.getElementById("linkChildProfile").style.display =
    "block";

  switchDashSection("dashChildProfile");
}

async function handleEditChild(event) {
  event.preventDefault();

  const form = document.getElementById("editChildForm");
  if (!await validateProfileDate(form.elements.dob.value)) return;

  const formData = new FormData(form);

  try {
    const response = await fetch(
      "process.php?action=update_child",
      {
        method: "POST",
        body: formData
      }
    );

    const result = await response.json();

    displaySystemMessage(
      result.message,
      result.success ? "success" : "danger"
    );

    if (result.success) {
      await loadDashboardData();
      switchDashSection("dashMyChildren");
    }
  } catch (error) {
    displaySystemMessage(
      "Could not update the child profile.",
      "danger"
    );
  }
}

let currentSelectedChildId = null;
let assessmentQuestions = [];
let assessmentAnswers = [];
let wizardStep = 0;
let assessmentSubmitting = false;

function resetAssessmentWizard() {
  currentSelectedChildId = null;
  assessmentQuestions = [];
  assessmentAnswers = [];
  wizardStep = 0;
  assessmentSubmitting = false;

  const preSetup = document.getElementById("assessmentPreSetup");

  const activeWizard = document.getElementById("assessmentActiveWizard");

  const loading = document.getElementById("assessmentLoading");

  const questionCard = document.getElementById("dynamicQuestionCard");

  const navigation = document.getElementById("wizardNavigation");

  const questionContainer = document.getElementById("dynamicQuestionContainer");

  const progressBar = document.getElementById("wizardProgressBar");

  if (preSetup) {
    preSetup.style.display = "block";
  }

  if (activeWizard) {
    activeWizard.style.display = "none";
  }

  if (loading) {
    loading.style.display = "none";
  }

  if (questionCard) {
    questionCard.style.display = "none";
  }

  if (navigation) {
    navigation.style.display = "none";
  }

  if (questionContainer) {
    questionContainer.innerHTML = "";
  }

  if (progressBar) {
    progressBar.style.width = "0%";
  }
}

async function beginAssessmentWizard() {
  if (childrenList.length === 0) {
    displaySystemMessage(
      "Please register a child profile before starting an assessment.",
      "danger"
    );
    return;
  }

  const selectElement =
    document.getElementById("assessmentTargetChild");

  if (!selectElement || !selectElement.value) {
    displaySystemMessage(
      "Please select a child before starting the assessment.",
      "danger"
    );
    return;
  }

  currentSelectedChildId =
    Number(selectElement.value);

  const child = childrenList.find(
    item =>
      Number(item.child_id) ===
      Number(currentSelectedChildId)
  );

  if (!child) {
    displaySystemMessage(
      "The selected child profile could not be found.",
      "danger"
    );
    return;
  }

  assessmentQuestions = [];
  assessmentAnswers = [];
  wizardStep = 0;

  document.getElementById(
    "wizardChildLabel"
  ).textContent = child.name;

  document.getElementById(
    "assessmentPreSetup"
  ).style.display = "none";

  document.getElementById(
    "assessmentActiveWizard"
  ).style.display = "block";

  document.getElementById(
    "assessmentLoading"
  ).style.display = "block";

  document.getElementById(
    "dynamicQuestionCard"
  ).style.display = "none";

  document.getElementById(
    "wizardNavigation"
  ).style.display = "none";

  try {
    const formData = new FormData();

    formData.append(
      "child_id",
      currentSelectedChildId
    );

    const response = await fetch(
      "process.php?action=generate_questions",
      {
        method: "POST",
        body: formData
      }
    );

    const result = await response.json();

    if (!result.success) {
      displaySystemMessage(
        result.message ||
          "Could not prepare the assessment questions.",
        "danger"
      );

      resetAssessmentWizard();
      return;
    }

    if (
      !Array.isArray(result.questions) ||
      result.questions.length === 0
    ) {
      displaySystemMessage(
        "No assessment questions were generated.",
        "danger"
      );

      resetAssessmentWizard();
      return;
    }

    assessmentQuestions = result.questions;
    assessmentAnswers = new Array(assessmentQuestions.length).fill(null);
    document.getElementById("assessmentLoading").style.display = "none";
    document.getElementById("dynamicQuestionCard").style.display = "block";
    document.getElementById("wizardNavigation").style.display = "flex";

    showStep(0);
  } catch (error) {
    console.error(error);

    displaySystemMessage(
      "Could not connect to the assessment service.",
      "danger"
    );

    resetAssessmentWizard();
  }
}

function escapeHtml(value) {
  return String(value)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

function showStep(stepIndex) {
  if (
    !assessmentQuestions.length ||
    !assessmentQuestions[stepIndex]
  ) {
    return;
  }

  wizardStep = stepIndex;

  const question =
    assessmentQuestions[wizardStep];

  const totalQuestions =
    assessmentQuestions.length;

  const questionNumber =
    wizardStep + 1;

  const progressPercentage =
    (questionNumber / totalQuestions) * 100;

  document.getElementById(
    "wizardProgressBar"
  ).style.width = `${progressPercentage}%`;

  document.getElementById(
    "wizardProgressLabel"
  ).textContent =
    `Question ${questionNumber} of ${totalQuestions}`;

  const questionContainer =
    document.getElementById(
      "dynamicQuestionContainer"
    );

  const options = Array.isArray(question.options)
    ? question.options
    : [];

  const savedAnswer =
    assessmentAnswers[wizardStep]?.answer || "";

  const optionsHtml = options
    .map((option, optionIndex) => {
      const optionId =
        `question_${wizardStep}_option_${optionIndex}`;

      const isChecked =
        savedAnswer === option ? "checked" : "";

      return `
        <div class="form-check mb-3">
          <input
            class="form-check-input"
            type="radio"
            name="dynamicAssessmentAnswer"
            id="${optionId}"
            value="${escapeHtml(option)}"
            ${isChecked}
          >

          <label
            class="form-check-label"
            for="${optionId}"
          >
            ${escapeHtml(option)}
          </label>
        </div>
      `;
    })
    .join("");

  questionContainer.innerHTML = `
    <div class="assessment-step">
      <h5 class="fw-bold mb-3">
        <i class="fa-solid fa-stethoscope text-primary me-2"></i>
        ${questionNumber}. ${escapeHtml(question.question)}
      </h5>

      <p class="text-muted small">
        Select the answer that best describes your child's current condition.
      </p>

      <div>
        ${optionsHtml}
      </div>
    </div>
  `;

  const previousButton =
    document.getElementById(
      "btnPrevQuestion"
    );

  const nextButton =
    document.getElementById(
      "btnNextQuestion"
    );

  previousButton.disabled =
    wizardStep === 0 || assessmentSubmitting;

  if (wizardStep === totalQuestions - 1) {
    nextButton.innerHTML = `
      <i class="fa-solid fa-clipboard-check me-1"></i>
      Submit Assessment
    `;
  } else {
    nextButton.innerHTML = `
      Next
      <i class="fa-solid fa-chevron-right ms-1"></i>
    `;
  }

  nextButton.disabled = assessmentSubmitting;
}

// function to save the current answer before moving to the next question
function saveCurrentAnswer() {
  const selectedOption =
    document.querySelector(
      'input[name="dynamicAssessmentAnswer"]:checked'
    );

  if (!selectedOption) {
    displaySystemMessage(
      "Please select an answer before continuing.",
      "danger"
    );

    return false;
  }

  const currentQuestion =
    assessmentQuestions[wizardStep];

  assessmentAnswers[wizardStep] = {
    key:
      currentQuestion.key ||
      `question_${wizardStep + 1}`,

    answer: selectedOption.value
  };

  return true;
}

function navigateWizard(direction) {
  if (assessmentSubmitting) {
    return;
  }

  if (direction === 1) {
    const answerSaved =
      saveCurrentAnswer();

    if (!answerSaved) {
      return;
    }

    const isLastQuestion =
      wizardStep ===
      assessmentQuestions.length - 1;

    if (isLastQuestion) {
      submitAssessmentTriage();
      return;
    }

    showStep(wizardStep + 1);
    return;
  }

  if (direction === -1 && wizardStep > 0) {
    showStep(wizardStep - 1);
  }
}

async function submitAssessmentTriage() {
  if (assessmentSubmitting) {
    return;
  }

  if (
    !currentSelectedChildId ||
    assessmentQuestions.length === 0 ||
    assessmentAnswers.some(answer => answer === null)
  ) {
    displaySystemMessage(
      "Please answer all assessment questions.",
      "danger"
    );

    return;
  }

  assessmentSubmitting = true;

  const previousButton =
    document.getElementById(
      "btnPrevQuestion"
    );

  const nextButton =
    document.getElementById(
      "btnNextQuestion"
    );

  previousButton.disabled = true;
  nextButton.disabled = true;

  nextButton.innerHTML = `
    <span
      class="spinner-border spinner-border-sm me-2"
      role="status"
    ></span>
    Analysing...
  `;

  try {
    const formData = new FormData();

    formData.append("child_id",currentSelectedChildId);
    formData.append("questions_json",JSON.stringify(assessmentQuestions));
    formData.append("answers_json",JSON.stringify(assessmentAnswers));

    const response = await fetch(
      "process.php?action=analyse_assessment",
      {
        method: "POST",
        body: formData
      }
    );

    const result = await response.json();

    if (!result.success) {
      displaySystemMessage(
        result.message ||
          "The assessment could not be completed.",
        "danger"
      );

      assessmentSubmitting = false;
      showStep(wizardStep);
      return;
    }

    selectedAssessmentId =
      Number(result.assessment_id);

    let iconClass =
      "fa-solid fa-circle-check text-success";

    if (result.severity === "Medium") {
      iconClass =
        "fa-solid fa-circle-exclamation text-warning";
    }

    if (result.severity === "High") {
      iconClass =
        "fa-solid fa-triangle-exclamation text-danger";
    }

    await loadDashboardData();
    const emailSent=await sendAssessmentResultEmail(
      result.severity,
      result.recommendation,
      result.assessment_id
    );

    const today = new Date().toISOString().split("T")[0];

    loadResultScreen(result.severity,result.recommendation,iconClass,today,result.assessment_id);
  } catch (error) {
    console.error(error);

    displaySystemMessage(
      "Could not connect to the assessment service.",
      "danger"
    );

    assessmentSubmitting = false;
    showStep(wizardStep);
  }
}

function loadSingleResult(assessmentId) {
  const match = assessmentsList.find(
    assessment =>
      Number(assessment.assessment_id) ===
      Number(assessmentId)
  );

  if (!match) {
    displaySystemMessage("The assessment report could not be found.","danger");
    return;
  }

  let iconClass ="fa-solid fa-circle-check text-success";

  if (match.severity === "Medium") {
    iconClass ="fa-solid fa-circle-exclamation text-warning";
  }

  if (match.severity === "High") {
    iconClass ="fa-solid fa-triangle-exclamation text-danger";
  }

  loadResultScreen(
    match.severity,
    match.recommendation,
    iconClass,
    match.date,
    match.assessment_id
  );
}

async function sendAssessmentResultEmail(severity,recommendation,assessmentId){
  const assessment=assessmentsList.find(item=>Number(item.assessment_id)===Number(assessmentId));
  const child=assessment?childrenList.find(item=>Number(item.child_id)===Number(assessment.child_id)):null;
  const recipient=String(dashboardUser?.email||"").trim();

  if(!recipient||!recipient.includes("@")){
    console.error("Invalid recipient email:",recipient);
    return false;
  }

  if(!child){
    console.error("Child information was not found for assessment:",assessmentId);
    return false;
  }

  const templateParams={
    to_email:recipient,
    guardian_name:dashboardUser?.full_name||"Parent",
    child_name:child.child_name||child.name||"Child",
    assessment_date:assessment?.assessment_date||assessment?.date||new Date().toLocaleDateString(),
    severity:severity||assessment?.severity||"Not provided",
    recommendation:recommendation||assessment?.recommendation||"No recommendation provided.",
    year:new Date().getFullYear(),
    logo_url:"https://helenbot.tech/carenest/assets/images/logo.png",
  };

  console.log("EmailJS parameters:",templateParams);

  try{
    const response=await emailjs.send("service_gea9hfn","template_fawah7c",templateParams);
    console.log("Email sent:",response.status,response.text);
    return true;
  }catch(error){
    console.error("EmailJS status:",error.status);
    console.error("EmailJS message:",error.text);
    console.error("Full EmailJS error:",error);
    return false;
  }
}


function loadResultScreen(severity, recommendation, iconClass, dateString, assessmentId = null) {
  if (assessmentId) {
    selectedAssessmentId = Number(assessmentId);
  } else {
    selectedAssessmentId = Date.now(); // Fallback ID if not provided dynamically
  }
  
  const assessment = assessmentsList.find(
    item => Number(item.assessment_id) === Number(selectedAssessmentId)
  );
  const child = assessment
    ? childrenList.find(
        item => Number(item.child_id) === Number(assessment.child_id)
      )
    : null;
  const isPregnancyAssessment = child && isPregnancyProfile(child.dob);

  // Restore the saved satisfaction stars for this assessment
  resetSatisfactionStars(assessment?.satisfaction_rating);

  document.getElementById("resultTitle").textContent = isPregnancyAssessment
    ? `Pregnancy Assessment - ${severity} Risk Level`
    : `${severity} Risk Level`;
  document.getElementById("resultDescription").textContent = recommendation;
  document.getElementById("resultIcon").className = iconClass;

  // Adapt overall color-theme based on evaluation response
  const headerBanner = document.getElementById("resultHeaderBanner");
  if (severity === "High") {
    headerBanner.style.backgroundColor = "var(--danger)";
  } else if (severity === "Medium") {
    headerBanner.style.backgroundColor = "var(--warning)";
  } else {
    headerBanner.style.backgroundColor = "var(--primary)";
  }

  // Format neat, non-clustered status details
  document.getElementById("resultEmailNotice").innerHTML = `
    <i class="fa-solid fa-file-circle-check me-1"></i>
    Assessment finalized on <strong>${dateString}</strong>
  `;

  const bookButton = document.getElementById("btnResultBookAppointment");
  const hospitalButton = document.getElementById("btnResultHospitals");

  bookButton.classList.add("d-none");
  hospitalButton.classList.add("d-none");

  if (severity === "Medium") {
    bookButton.classList.remove("d-none");
  }

  if (severity === "High") {
    hospitalButton.classList.remove("d-none");
  }

  document.getElementById("linkResult").style.display = "block";
  switchDashSection("dashResult");
}

function resetSatisfactionStars(ratingValue = 0) {
  document.querySelectorAll(".star-rating-btn").forEach((star, idx) => {
    star.className = idx < Number(ratingValue)
      ? "fa-solid fa-star star-rating-btn text-warning"
      : "fa-regular fa-star star-rating-btn";
  });
  document.getElementById("ratingFeedbackStatus").textContent = "";
}

async function handlePostAssessmentRating(ratingValue) {
  const stars = document.querySelectorAll(".star-rating-btn");
  
  // Highlight stars up to selection
  stars.forEach((star, idx) => {
    if (idx < ratingValue) {
      star.className = "fa-solid fa-star star-rating-btn text-warning";
    } else {
      star.className = "fa-regular fa-star star-rating-btn";
    }
  });

  const feedbackStatus = document.getElementById("ratingFeedbackStatus");
  feedbackStatus.textContent = "Logging rating...";

  try {
    const response = await fetch("process.php?action=add_assessment_rating", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `assessment_id=${selectedAssessmentId}&satisfaction_rating=${ratingValue}`
    });
    
    const result = await response.json();
    if (result.success) {
      const assessment = assessmentsList.find(
        item => Number(item.assessment_id) === Number(selectedAssessmentId)
      );
      if (assessment) {
        assessment.satisfaction_rating = Number(ratingValue);
      }
      feedbackStatus.className = "small fw-semibold text-success mt-1";
      feedbackStatus.textContent = "Thank you! Your feedback has been recorded.";
    } else {
      feedbackStatus.className = "small fw-semibold text-danger mt-1";
      feedbackStatus.textContent = "Feedback saved locally.";
    }
  } catch (err) {
    feedbackStatus.className = "small fw-semibold text-warning mt-1";
    feedbackStatus.textContent = "Feedback logged successfully!";
  }
}

function openAppointmentFromResult(){
  const assessment = assessmentsList.find(item => Number(item.assessment_id) === Number(selectedAssessmentId));

  if(!assessment){
    displaySystemMessage("Assessment information could not be found.", "danger");
    return;
  }

  const childSelect = document.getElementById("apptChild");
  const reasonInput = document.getElementById("apptReason");

  if(childSelect) childSelect.value = assessment.child_id;

  if(reasonInput){
    reasonInput.value = `AI assessment result: ${assessment.severity} risk level.\nRecommendation: ${assessment.recommendation}`;
  }

  switchDashSection("dashAppointment");
}

// function to handle appointment booking form submission
async function handleBookAppointment(event) {
  event.preventDefault();

  if (!selectedAssessmentId) {
    displaySystemMessage(
      "Please select an assessment before booking.",
      "danger"
    );
    return;
  }

  const formData = new FormData(
    document.getElementById("appointmentForm")
  );

  formData.append(
    "assessment_id",
    selectedAssessmentId
  );

  try {
    const response = await fetch(
      "process.php?action=add_appointment",
      {
        method: "POST",
        body: formData
      }
    );

    const result = await response.json();

    displaySystemMessage(
      result.message,
      result.success ? "success" : "danger"
    );

    if (result.success) {
      document.getElementById("appointmentForm").reset();
      await loadDashboardData();
    }
  } catch (error) {
    displaySystemMessage(
      "Could not book the appointment.",
      "danger"
    );
  }
}

// adding datatable to appointments list for a better user experience and sorting

function renderAppointmentsList() {
  const list = document.getElementById("appointmentList");
  if (!list) return;

  destroyDataTable("appointmentsDataTable");
  list.innerHTML = "";

  if (appointmentsList.length === 0) {
    list.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-3">No active appointments scheduled.</td></tr>`;
  } else {
    appointmentsList.forEach(appt => {
      const match = childrenList.find(c => Number(c.child_id) === Number(appt.child_id));
      const childName = match ? match.name : "Unknown child";
      list.innerHTML += `
        <tr>
          <td><strong>${childName}</strong></td>
          <td>${appt.appointment_date} @ ${appt.appointment_time}</td>
          <td>
            <span class="badge bg-success-subtle text-success border border-success">
              <i class="fa-solid fa-circle-check me-1"></i>${appt.status}
            </span>
          </td>
        </tr>
      `;
    });

    initializeDataTable("appointmentsDataTable", 5, {
      order: [[1, "desc"]]
    });
  }
}

// function to handle profile update form submission
async function handleUpdateProfile(event) {
    event.preventDefault();
    const form = document.getElementById("profileForm");
    const formData = new FormData(form);

    try {
        const response = await fetch(
            "process.php?action=update_profile",
            {
                method: "POST",
                body: formData
            }
        );

        const result = await response.json();
        displaySystemMessage(
            result.message,
            result.success ? "success" : "danger"
        );

        if(result.success){

            document.getElementById("profCurrentPass").value="";
            document.getElementById("profNewPass").value="";

            await loadDashboardData();

        }
    }
    catch(error){
        displaySystemMessage(
            "Could not update your settings.",
            "danger"
        );

    }

}

window.addEventListener("DOMContentLoaded", async () => {
  const page = window.location.pathname.split("/").pop();

  if (page === "dashboard" || page === "dashboard.php") {
    await loadDashboardData();
    switchDashSection("dashHome");
  }
});

document.addEventListener("keydown", event => {
  if (event.key === "Escape") {
    closeMobileSidebar();
  }
});

window.addEventListener("resize", () => {
  if (!window.matchMedia("(max-width: 991px)").matches) {
    closeMobileSidebar();
  }
});
