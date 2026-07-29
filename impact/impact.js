const impactNumberFormatter = new Intl.NumberFormat();
let impactSyncInProgress = false;
let impactSyncQueued = false;
let impactHasShownConnectionError = false;

function impactFormatNumber(value) {
  return impactNumberFormatter.format(Number(value) || 0);
}

function impactSetText(id, value) {
  const element = document.getElementById(id);
  if (element) element.textContent = value;
}

function impactEscapeHtml(value) {
  const element = document.createElement("div");
  element.textContent = String(value ?? "");
  return element.innerHTML;
}

function impactCountryIcon(country) {
  const icons = {
    Rwanda: "🇷🇼",
    Kenya: "🇰🇪",
    Nigeria: "🇳🇬",
    Ghana: "🇬🇭",
    Uganda: "🇺🇬",
    Tanzania: "🇹🇿",
    Burundi: "🇧🇮"
  };

  return icons[country] || "🌍";
}

function impactUpdateCountryOptions(countries) {
  const select = document.getElementById("countryFilter");
  const existingValues = new Set(
    Array.from(select.options, option => option.value)
  );

  countries.forEach(item => {
    if (existingValues.has(item.country)) return;

    const option = document.createElement("option");
    option.value = item.country;
    option.textContent = `${impactCountryIcon(item.country)} ${item.country}`;
    select.appendChild(option);
  });
}

function impactRenderCountries(countries) {
  const tableBody = document.getElementById("countryTableBody");

  if (!countries.length) {
    tableBody.innerHTML = `
      <tr>
        <td colspan="3" class="py-4 text-center text-gray-400">
          No guardian registrations match these filters.
        </td>
      </tr>
    `;
    return;
  }

  tableBody.innerHTML = countries.map(item => `
    <tr class="hover:bg-gray-50/80 transition-colors">
      <td class="py-3 flex items-center gap-2.5">
        <span class="text-base">${impactCountryIcon(item.country)}</span>
        <span class="font-semibold text-gray-800">${impactEscapeHtml(item.country)}</span>
      </td>
      <td class="py-3 text-center text-gray-700 font-semibold">
        ${impactFormatNumber(item.guardians)}
      </td>
      <td class="py-3 text-right font-bold text-brand">
        ${Number(item.share).toFixed(1)}%
      </td>
    </tr>
  `).join("");
}

function impactUpdateCharts(data) {
  if (ageChartInstance) {
    ageChartInstance.data.datasets[0].data = data.age_distribution.counts;
    ageChartInstance.update("none");
  } else {
    initAgeChart(data.age_distribution.counts);
  }

  if (severityChartInstance) {
    severityChartInstance.data.datasets[0].data = data.severity.percentages;
    severityChartInstance.update("none");
  } else {
    initSeverityChart(data.severity.percentages);
  }

  if (satisfactionChartInstance) {
    satisfactionChartInstance.data.datasets[0].data =
      [...data.ratings.counts].reverse();
    satisfactionChartInstance.update("none");
  } else {
    initSatisfactionChart(data.ratings.counts);
  }
}

function impactRenderGuardianTrend(growth) {
  const trend = document.getElementById("guardianTrend");
  const value = Number(growth) || 0;
  const directionClass = value > 0
    ? "text-emerald-600"
    : value < 0
      ? "text-red-600"
      : "text-gray-500";
  const icon = value > 0
    ? "fa-arrow-trend-up"
    : value < 0
      ? "fa-arrow-trend-down"
      : "fa-minus";

  trend.className =
    `text-xs font-bold ${directionClass} flex items-center gap-0.5`;
  trend.innerHTML =
    `<i class="fa-solid ${icon}"></i> ${value > 0 ? "+" : ""}${value.toFixed(1)}%`;
}

function impactRenderAnalytics(data) {
  const kpis = data.kpis;

  impactSetText("kpiGuardians", impactFormatNumber(kpis.guardians));
  impactSetText("kpiChildren", impactFormatNumber(kpis.children));
  impactSetText("kpiBornChildren", impactFormatNumber(kpis.born_children));
  impactSetText(
    "kpiExpectedProfiles",
    impactFormatNumber(kpis.expected_profiles)
  );
  impactSetText("kpiAssessments", impactFormatNumber(kpis.assessments));
  impactSetText("kpiSatisfaction", Number(kpis.average_rating).toFixed(2));
  impactSetText(
    "childrenPerGuardian",
    `Avg ${Number(kpis.children_per_guardian).toFixed(2)} profiles per guardian`
  );
  impactSetText(
    "positiveFeedback",
    `(${Number(kpis.positive_percentage).toFixed(1)}% positive)`
  );
  impactSetText(
    "ratingAverageBadge",
    `${Number(kpis.average_rating).toFixed(2)} / 5 Avg Rating`
  );
  impactSetText(
    "ratingResponseTotal",
    `${impactFormatNumber(kpis.rating_total)} responses`
  );
  impactSetText(
    "ratingPositiveTotal",
    `${Number(kpis.positive_percentage).toFixed(1)}% Positive Feedback (4 & 5 Stars)`
  );

  impactSetText(
    "underSixShare",
    `${Number(data.age_distribution.under_six_percentage).toFixed(1)}%`
  );
  impactSetText(
    "olderChildrenShare",
    `${Number(data.age_distribution.six_to_twelve_percentage).toFixed(1)}%`
  );

  ["lowSeverityShare", "mediumSeverityShare", "highSeverityShare"]
    .forEach((id, index) => {
      impactSetText(
        id,
        `${Number(data.severity.percentages[index]).toFixed(1)}%`
      );
    });

  impactRenderGuardianTrend(kpis.guardian_growth);
  impactUpdateCountryOptions(data.countries);
  impactRenderCountries(data.countries);
  impactSetText(
    "countryCoverage",
    `Coverage spanning ${impactFormatNumber(data.countries.length)} registered ${data.countries.length === 1 ? "territory" : "territories"}`
  );
  impactUpdateCharts(data);

  const syncedAt = new Date(data.synced_at);
  impactSetText(
    "lastRefreshTime",
    Number.isNaN(syncedAt.getTime())
      ? "Just now"
      : syncedAt.toLocaleString([], {
          dateStyle: "medium",
          timeStyle: "medium"
        })
  );
}

/**
 * Fetch every dashboard metric in one request. If a filter changes during an
 * active sync, one fresh request is queued so stale data never wins.
 */
async function refreshImpactAnalytics(showConfirmation = false) {
  if (impactSyncInProgress) {
    impactSyncQueued = true;
    return;
  }

  impactSyncInProgress = true;

  const country = document.getElementById("countryFilter").value;
  const range = document.getElementById("timeFilter").value;
  const query = new URLSearchParams({
    action: "analytics",
    country,
    range
  });

  try {
    const response = await fetch(`functions.php?${query}`, {
      headers: { Accept: "application/json" },
      cache: "no-store"
    });
    const result = await response.json();

    if (!response.ok || !result.success) {
      throw new Error(result.message || "Impact analytics could not be loaded.");
    }

    impactRenderAnalytics(result.data);
    impactHasShownConnectionError = false;

    if (showConfirmation) {
      showToast("Live impact analytics synchronized.");
    }
  } catch (error) {
    console.error(error);
    if (!impactHasShownConnectionError) {
      showToast("Live impact data is temporarily unavailable.");
      impactHasShownConnectionError = true;
    }
  } finally {
    impactSyncInProgress = false;

    if (impactSyncQueued) {
      impactSyncQueued = false;
      refreshImpactAnalytics();
    }
  }
}

document.addEventListener("DOMContentLoaded", () => {
  refreshImpactAnalytics();
  window.setInterval(refreshImpactAnalytics, 5000);
});
