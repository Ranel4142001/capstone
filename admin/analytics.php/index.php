<?php
require_once('../config.php');
require_once('inc/header.php');
?>
<style>
  body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background-color: #f4f4f4;
    overflow-x: hidden;
  }

  .summary-container {
    display: flex;
    gap: 660px;
    margin-bottom: 5px;
    flex-wrap: wrap;
  }

  .card {
    background-color: #fff;
    border-radius: 8px;
    padding: 8px 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    width: 140px;
  }

  .card h6 {
    font-size: 12px;
    color: #666;
    margin: 0;
    font-weight: bold; /* Make the card heading bold */
  }

  .card h3 {
    font-size: 16px;
    font-weight: bold; /* Make the card value bold */
    margin-top: 4px;
    color: #333;
  }

  .chart-card {
    background-color: #fff;
    border-radius: 8px;
    padding: 12px 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    width: 100%;
    max-width: 100%;
    overflow-x: auto;
  }

  .chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
  }

  .chart-header h6 {
    font-size: 14px;
    margin: 0;
  }

  .chart-header select,
  .chart-header button {
    margin-left: 8px;
    padding: 5px 8px;
    border: none;
    border-radius: 4px;
    background-color: #007BFF;
    color: white;
    cursor: pointer;
    font-size: 12px;
  }

  .chart-header select {
    appearance: none;
    background-color: #fff;
    color: #333;
    border: 1px solid #ccc;
    cursor: pointer;
  }

  .chart-header button.active {
    background-color: #0056b3;
  }

  #analyticsChart {
    width: 100% !important;
    height: 50vh !important;
  }

  @media (max-width: 768px) {
    #analyticsChart {
      height: 60vh !important;
    }
  }
</style>
</head>

<body>
  <h3>Total Sales and Productions</h3>

  <div class="summary-container">
    <div class="card">
      <h6>Total Sales (₱)</h6>
      <h3 id="total_sales">0.00</h3>
    </div>
    <div class="card">
      <h6>Total Production (Jars)</h6>
      <h3 id="total_production">0</h3>
    </div>
  </div>


  <div class="chart-card">
    <div class="chart-header">
      <h6>Sales and Productions</h6>
      <div>
        <select id="yearSelector"></select>
        <button id="salesButton" class="active">Sales</button>
        <button id="productionButton" class="">Production</button>
      </div>
    </div>
    <canvas id="analyticsChart"></canvas>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    let chart;
    let selectedYear;
    const salesButton = document.getElementById('salesButton');
    const productionButton = document.getElementById('productionButton');

    function populateYearSelector() {
      const sel = document.getElementById('yearSelector');
      sel.innerHTML = ''; // Clear existing options

      // Use get_dashboard_data.php to also get available years
      fetch('../classes/get_dashboard_data.php')
        .then(response => response.json())
        .then(data => {
          const years = data.available_years || [];

          if (!years.length) {
            const currentYear = new Date().getFullYear();
            for (let i = 2020; i <= currentYear; i++) {
              years.push(i);
            }
          }

          years.forEach(year => {
            const opt = document.createElement('option');
            opt.value = year;
            opt.textContent = year;
            sel.appendChild(opt);
          });

          selectedYear = data.selectedYear || years[years.length - 1];
          sel.value = selectedYear;

          // Fetch stats for selected year
          fetchStats();
        })
        .catch(error => {
          console.error('Error fetching available years:', error);

          const currentYear = new Date().getFullYear();
          for (let i = 2020; i <= currentYear; i++) {
            const opt = document.createElement('option');
            opt.value = i;
            opt.textContent = i;
            sel.appendChild(opt);
          }

          selectedYear = currentYear;
          sel.value = selectedYear;
          fetchStats();
        });

      sel.addEventListener('change', () => {
        selectedYear = sel.value;
        fetchStats();
      });
    }

    function fetchStats() {
      const url = `../classes/get_dashboard_data.php?year=${selectedYear || ''}`;
      fetch(url)
        .then(response => response.json())
        .then(data => {
          document.getElementById('total_production').textContent = data.total_production;
          document.getElementById('total_sales').textContent = parseFloat(data.total_sales).toFixed(2);

          if (!chart) {
            createChart(data.months, data.monthly_sales, data.monthly_production);
          } else {
            updateChart(data.monthly_sales, data.monthly_production);
          }
        })
        .catch(error => {
          console.error('Failed to fetch stats:', error);
        });
    }

    function createChart(labels, salesData, productionData) {
      const ctx = document.getElementById('analyticsChart').getContext('2d');

      // Determine if data is empty (all 0s)
      const allZero = salesData.every(val => val === 0) && productionData.every(val => val === 0);

      chart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
            label: 'Sales (₱)',
            data: salesData,
            borderColor: 'blue',
            backgroundColor: 'rgba(0,0,255,0.1)',
            hidden: false,
            pointRadius: 4,
            pointHoverRadius: 6,
            borderWidth: 2
          }, {
            label: 'Production (Jars)',
            data: productionData,
            borderColor: 'green',
            backgroundColor: 'rgba(0,255,0,0.1)',
            hidden: true,
            pointRadius: 4,
            pointHoverRadius: 6,
            borderWidth: 2
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          layout: { padding: 10 },
          scales: {
            x: {
              ticks: { font: { size: 12 } }
            },
            y: {
              beginAtZero: true,
              min: 0,
              suggestedMin: 0,
              suggestedMax: allZero ? 1 : undefined, // ✅ Force [0,1] if no data
              ticks: {
                font: { size: 12 },
                stepSize: 1,
                callback: function(value) {
                  return Number.isInteger(value) ? value : '';
                }
              }
            }
          },
          plugins: {
            tooltip: {
              mode: 'index',
              intersect: false,
              callbacks: {
                label: function(context) {
                  const label = context.dataset.label;
                  const value = context.parsed.y;
                  return label.includes("Sales") ? `₱${value.toLocaleString()}` : `${value} jars`;
                }
              }
            },
            legend: {
              labels: { font: { size: 13 } }
            }
          },
          interaction: {
            mode: 'index',
            intersect: false
          }
        }
      });
    }

    function updateChart(salesData, productionData) {
      const allZero = salesData.every(val => val === 0) && productionData.every(val => val === 0);

      // Reset Y-axis settings
      chart.options.scales.y.min = 0;
      chart.options.scales.y.suggestedMin = 0;
      chart.options.scales.y.suggestedMax = allZero ? 1 : undefined;
      chart.options.scales.y.ticks = {
        stepSize: 1,
        callback: function(value) {
          return Number.isInteger(value) ? value : '';
        }
      };

      // Update data
      chart.data.datasets[0].data = salesData;
      chart.data.datasets[1].data = productionData;

      // Trigger chart update
      chart.update();
    }

    function toggleDataset(datasetType) {
      chart.data.datasets.forEach((dataset) => {
        if (dataset.label.toLowerCase().includes(datasetType)) {
          dataset.hidden = !dataset.hidden;
        }
      });
      chart.update();

      // Update button styles
      if (datasetType === 'sales') {
        salesButton.classList.toggle('active');
      } else if (datasetType === 'production') {
        productionButton.classList.toggle('active');
      }
    }

    // Event listeners for the buttons
    salesButton.addEventListener('click', () => toggleDataset('sales'));
    productionButton.addEventListener('click', () => toggleDataset('production'));

    // Fetch years dynamically and populate the dropdown on page load
    populateYearSelector();
    setInterval(fetchStats, 10000);
  </script>
</body>