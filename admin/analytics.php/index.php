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
    gap: 15px;
    margin-bottom: 15px;
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
  }

  .card h3 {
    font-size: 16px;
    font-weight: bold;
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

  .chart-header button:hover,
  .chart-header select:hover {
    background-color: #0056b3;
    color: white;
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
  <h3>Total Production and Sales</h3>

  <div class="summary-container">
    <div class="card">
      <h6>Total Production (Jars)</h6>
      <h3 id="total_production">0</h3>
    </div>
    <div class="card">
      <h6>Total Sales (₱)</h6>
      <h3 id="total_sales">0.00</h3>
    </div>
  </div>

  <div class="chart-card">
    <div class="chart-header">
      <h6>Monthly Analytics</h6>
      <div>
        <select id="yearSelector"></select>
        <button onclick="showDataset('sales')">Sales</button>
        <button onclick="showDataset('production')">Production</button>
      </div>
    </div>
    <canvas id="analyticsChart"></canvas>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    let chart;
    let selectedYear;

    // Populate year selector
    function populateYearSelector() {
      const sel = document.getElementById('yearSelector');
      sel.innerHTML = ''; // clear
      // Fetch years dynamically from the database using AJAX
      fetch('../classes/get_available_years.php')
        .then(response => response.json())
        .then(years => {
          years.forEach(year => {
            const opt = document.createElement('option');
            opt.value = year;
            opt.textContent = year;
            sel.appendChild(opt);
          });
          // Default to the last year in the array
          selectedYear = years[years.length - 1];
          sel.value = selectedYear;
          // Trigger initial fetchStats
          fetchStats();
        })
        .catch(error => {
          console.error('Error fetching years:', error);
          // If there's an error, you might want to display a default set of years
          const currentYear = new Date().getFullYear();
          for (let i = 2020; i <= currentYear; i++) {
            const opt = document.createElement('option');
            opt.value = i;
            opt.textContent = i;
            sel.appendChild(opt);
          }
          selectedYear = currentYear;
          sel.value = selectedYear;
          fetchStats(); //fetch stats after the year dropdown is populated
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
      const hasData = salesData.some(val => val > 0) || productionData.some(val => val > 0);

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
              ticks: {
                font: { size: 12 }
              }
            },
            y: {
              beginAtZero: true,
              suggestedMin: 0,
              ticks: {
                font: { size: 12 },
                callback: function(value) {
                  if (hasData) {
                    if (value % 1 === 0) {
                      return value;
                    } else {
                      return '';
                    }
                  }
                  return value;
                }
              },
              min: 0,
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
                  return label.includes("Sales") ?
                    `₱${value.toLocaleString()}` :
                    `${value} jars`;
                }
              }
            },
            legend: {
              labels: {
                font: {
                  size: 13
                }
              }
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
      const hasData = salesData.some(val => val > 0) || productionData.some(val => val > 0);
      const yAxisOptions = {
        beginAtZero: true,
        suggestedMin: 0,
        ticks: {
          font: {
            size: 12
          },
          callback: function(value) {
            if (hasData) {
              if (value % 1 === 0) {
                return value;
              } else {
                return '';
              }
            }
            return value;
          }
        },
        min: 0,
      };


      chart.options.scales.y = yAxisOptions;
      chart.config.options.scales.y = yAxisOptions;
      chart.data.datasets[0].data = salesData;
      chart.data.datasets[1].data = productionData;
      chart.update();
    }

    // Fetch years dynamically and populate the dropdown on page load
    populateYearSelector();
    setInterval(fetchStats, 10000);
  </script>
</body>

