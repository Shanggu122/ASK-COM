<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Admin Analytics</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="{{ asset('css/admin-navbar.css') }}">
  <link rel="stylesheet" href="{{ asset('css/admin-analytics.css') }}">
  <link rel="stylesheet" href="{{ asset('css/logout-confirm.css') }}">
  <script src="{{ asset('js/logout-confirm.js') }}" defer></script>
</head>
<body>
  @include('components.navbar-admin')
  <div class="main-content">
    <div class="analytics-container">
      <div class="fixed-section">
        <div class="header">
          <h1>Consultation Analytics Dashboard</h1>
        </div>

        <!-- Date filters removed per latest requirement -->
      </div>

      <div class="department-tabs">
        <button class="department-tab active" data-dept="comsci">
          <i class='bx bx-code-alt'></i> ComSci Department
        </button>
      </div>
    </div>

    <div class="scrollable-content">
    <!-- ComSci Section -->
    <div class="department-section active" id="comsci-section">
      <div class="stats-grid">
        <div class="stat-card">
          <h3>Total ComSci Consultations</h3>
          <div class="stat-value" id="comsci-total">0</div>
        </div>
      </div>
      <div class="grid">
        <div class="card chart-card">
          <h2>Top ComSci Consultation Topics</h2>
          <div class="legend-inline" id="comsciTopicLegend"></div>
          <div class="chart-container">
            <canvas id="comsciTopicsChart"></canvas>
          </div>
        </div>
        <div class="card chart-card">
          <h2>ComSci Consultation Activity</h2>
          <div class="chart-container">
            <canvas id="comsciActivityChart"></canvas>
          </div>
        </div>
        <div class="card chart-card">
          <h2>ComSci Peak Days</h2>
          <div class="chart-container">
            <canvas id="comsciPeakDaysChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Load Chart.js -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
  <!-- Initialize charts -->
  <script>
    // Debug flag
    const DEBUG = true;

    // Constants
    const REFRESH_MS = 15000; // 15s polling interval


    // Chart state management
    const charts = {
      comsci: { topics: null, activity: null, peak: null },
      lastHash: null
    };

    // Color schemes
    const palettes = {
      comsci: ['#76A1DF', '#7C6394', '#5D3184', '#3D0270', '#3C1642']
    };

    // Helper function to log debug messages
    function log(message, data = null) {
      if (DEBUG) {
        if (data) {
          console.log(message, data);
        } else {
          console.log(message);
        }
      }
    }

    // Canvas setup helper
    function setCanvasSize(id) {
      const canvas = document.getElementById(id);
      if (!canvas) {
        log(`Canvas ${id} not found`);
        return;
      }
      
      const container = canvas.parentElement;
      const width = container.clientWidth;
      const height = container.clientHeight;
      const dpr = window.devicePixelRatio || 1;

      canvas.style.width = width + 'px';
      canvas.style.height = height + 'px';
      canvas.width = Math.floor(width * dpr);
      canvas.height = Math.floor(height * dpr);
      
      log(`Canvas ${id} sized to ${width}x${height} (dpr: ${dpr})`);
    }

    // Chart builders
    function buildTopicsChart(dept, t) {
      if (!t?.topics?.length) {
        log(`No topic data for ${dept}`);
        return;
      }

      const canvas = document.getElementById(`${dept}TopicsChart`);
      if (!canvas) {
        log(`Topics chart canvas not found for ${dept}`);
        return;
      }

      if (charts[dept].topics) {
        charts[dept].topics.destroy();
      }

      const ctx = canvas.getContext('2d');
      if (!ctx) {
        log(`Could not get 2D context for ${dept} topics chart`);
        return;
      }

      const legend = document.getElementById(`${dept}TopicLegend`);
      if (legend) {
        legend.innerHTML = t.topics.map((topic, i) => 
          `<span><span class="swatch" style="background:${palettes[dept][i%5]}"></span>${topic}</span>`
        ).join('');
      }

            charts[dept].topics = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: t.professors || [],
          datasets: t.topics.map((topic, i) => ({
            label: topic,
            data: t.data[topic] || [],
            backgroundColor: palettes[dept][i%5],
            borderColor: palettes[dept][i%5],
            borderWidth: 1,
            barPercentage: 0.7,
            categoryPercentage: 0.85,
            maxBarThickness: 50
          }))
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            x: {
              stacked: true,
              grid: { display: false }
            },
            y: {
              stacked: true,
              beginAtZero: true,
              ticks: {
                stepSize: 1,
                precision: 0
              }
            }
          },
          plugins: {
            legend: { display: false }
          }
        }
      });
    }

    function buildActivityChart(dept, a) {
      if (!a?.months?.length) {
        log(`No activity data for ${dept}`);
        return;
      }

      const canvas = document.getElementById(`${dept}ActivityChart`);
      if (!canvas) {
        log(`Activity chart canvas not found for ${dept}`);
        return;
      }

      if (charts[dept].activity) {
        charts[dept].activity.destroy();
      }

      charts[dept].activity = new Chart(canvas, {
        type: 'line',
        data: {
          labels: a.months,
          datasets: a.series.map((s, i) => ({
            label: s.name,
            data: s.data,
            borderColor: palettes[dept][i%5],
            backgroundColor: palettes[dept][i%5],
            tension: 0.4,
            fill: false
          }))
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              ticks: { 
                stepSize: 1,
                precision: 0 
              }
            }
          }
        }
      });
    }

    function buildPeakDaysChart(dept, days, weekend) {
      if (!days) {
        log(`No peak days data for ${dept}`);
        return;
      }

      const canvas = document.getElementById(`${dept}PeakDaysChart`);
      if (!canvas) {
        log(`Peak days chart canvas not found for ${dept}`);
        return;
      }

      if (charts[dept].peak) {
        charts[dept].peak.destroy();
      }

      const ctx = canvas.getContext('2d');
      if (!ctx) {
        log(`Could not get 2D context for ${dept} peak days chart`);
        return;
      }

      const labels = Object.keys(days);
      const data = Object.values(days);

      charts[dept].peak = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: labels,
          datasets: [{
            data: data,
            backgroundColor: labels.map((_, i) => palettes[dept][i%5])
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { 
              position: 'right',
              labels: {
                boxWidth: 12,
                padding: 15,
                font: {
                  size: 11
                }
              }
            }
          },
          layout: {
            padding: {
              right: 20
            }
          }
        }
      });
    }

    // Main analytics loader
    async function loadAnalytics(force = false) {
      log('Loading analytics...');

      try {
  const params = new URLSearchParams();
  params.append('_', Date.now().toString());

  const response = await fetch('/api/admin/analytics?' + params.toString());
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        log('Received data:', data);

        const hash = JSON.stringify(data);
        if (!force && hash === charts.lastHash) {
          log('No data changes');
          return;
        }
        charts.lastHash = hash;

        // Update stats
        ['itis', 'comsci'].forEach(dept => {
          if (data[dept]) {
            document.getElementById(`${dept}-total`).textContent = data[dept].totalConsultations || 0;

            // Ensure proper canvas sizes
            setCanvasSize(`${dept}TopicsChart`);
            setCanvasSize(`${dept}ActivityChart`);
            setCanvasSize(`${dept}PeakDaysChart`);

            // Build charts
            buildTopicsChart(dept, data[dept].topics);
            buildActivityChart(dept, data[dept].activity);
            buildPeakDaysChart(dept, data[dept].peak_days, data[dept].weekend_days);
          }
        });
      } catch (error) {
        log('Error loading analytics:', error);
      }
    }

    // Tab switching
    document.querySelectorAll('.department-tab').forEach(tab => {
      tab.addEventListener('click', () => {
        const dept = tab.dataset.dept;
        document.querySelectorAll('.department-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.department-section').forEach(s => s.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById(`${dept}-section`).classList.add('active');
      });
    });

    // Handle window resizing
    function handleResize() {
      ['itis', 'comsci'].forEach(dept => {
        setCanvasSize(`${dept}TopicsChart`);
        setCanvasSize(`${dept}ActivityChart`);
        setCanvasSize(`${dept}PeakDaysChart`);
        
        if (charts[dept].topics) charts[dept].topics.resize();
        if (charts[dept].activity) charts[dept].activity.resize();
        if (charts[dept].peak) charts[dept].peak.resize();
      });
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', () => {
      log('Initializing analytics...');
      // Add resize handler with debounce
      let resizeTimeout;
      window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(handleResize, 250);
      });

      // Initial load
      loadAnalytics(true);
      
      // Set up auto-refresh
      setInterval(() => loadAnalytics(false), REFRESH_MS);
      document.addEventListener('visibilitychange', () => {
        if (!document.hidden) loadAnalytics(false);
      });
    });
  </script>
</body>
</html>
