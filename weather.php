<?php 
include('includes/db.php');
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
$apiKey = "b30924bfd9f243e2bc5141043253107";

// ✅ Updated city list
$locations = [
    'Lucknow', 'Lakhimpur', 'Barabanki', 'Sitapur', 'Unnao', 
    'Ayodhya', 'Aliganj', 'Raebareli', 'Kanpur', 'Bahraich'
];

// Get selected city
$location = $_GET['location'] ?? 'Lucknow';

// Weather API call (7 days if plan allows)
$weatherUrl = "http://api.weatherapi.com/v1/forecast.json?key=$apiKey&q=" . urlencode($location) . "&days=7&aqi=no&alerts=no";
$response = @file_get_contents($weatherUrl);
$data = $response ? json_decode($response, true) : null;

// Basic safety: if API failed, provide defaults to avoid errors
if (!$data || !isset($data['current'])) {
    // Provide safe defaults to avoid warnings/errors in HTML below
    $currentTemp = "N/A";
    $humidity = "N/A";
    $precip = "N/A";
    $condition = "Data unavailable";
    $hourlyData = [];
    $labels = [];
    $rainChances = [];
    $forecastData = [];
    $averageRainChance = 0;
} else {
    // Current Weather
    $currentTemp = $data['current']['temp_c'];
    $humidity = $data['current']['humidity'];
    $precip = $data['current']['precip_mm'];
    $condition = $data['current']['condition']['text'];

    // Hourly Forecast (Next 12 hours)
    $hourlyData = [];
    $labels = [];
    $rainChances = [];
    $now = time();
    foreach (($data['forecast']['forecastday'][0]['hour'] ?? []) as $hour) {
        $hourTime = strtotime($hour['time']);
        if ($hourTime >= $now && count($hourlyData) < 12) {
            $hourlyData[] = [
                'time' => date('g A', $hourTime),
                'temp_c' => $hour['temp_c'],
                'rain_chance' => $hour['chance_of_rain'] ?? 0
            ];
            $labels[] = date('g A', $hourTime);
            $rainChances[] = $hour['chance_of_rain'] ?? 0;
        }
    }

    // 7-Day Forecast
    $forecastData = [];
    $totalRainChance = 0;
    foreach ($data['forecast']['forecastday'] as $day) {
        $rainChance = $day['day']['daily_chance_of_rain'] ?? 0;
        $forecastData[] = [
            'date' => date('D, M j', strtotime($day['date'])),
            'min_temp' => $day['day']['mintemp_c'] ?? 'N/A',
            'max_temp' => $day['day']['maxtemp_c'] ?? 'N/A',
            'condition' => $day['day']['condition']['text'] ?? 'N/A',
            'rain_chance' => $rainChance
        ];
        $totalRainChance += $rainChance;
    }
    $averageRainChance = count($forecastData) > 0 ? ($totalRainChance / count($forecastData)) : 0;
}

// ✅ City-specific dynamic suggestions mapping
$citySuggestions = [
    'Lucknow' => [
        "🌧️ Lucknow has dense urban areas – prioritize rooftop rainwater collection.",
        "🏫 Encourage schools and offices in Lucknow to set up rain harvesting pits.",
        "💧 Use harvested water in Lucknow for non-drinking purposes like gardening."
    ],
    'Raebareli' => [
        "🌾 Agriculture is key in Raebareli – store rainwater for crop irrigation.",
        "🪣 Promote farm-level water storage tanks in Raebareli.",
        "🚜 Train farmers in Raebareli to use rainwater for livestock."
    ],
    'Sitapur' => [
        "🌿 Sitapur can use rainwater for small-scale irrigation.",
        "🏠 Encourage households in Sitapur to install rooftop collection.",
        "🛠️ Community tanks should be built in Sitapur villages."
    ],
    'Kanpur' => [
        "🏭 Kanpur industries can recycle rainwater to reduce groundwater use.",
        "🏙️ High-rise buildings in Kanpur should integrate rainwater harvesting systems.",
        "🌍 Awareness drives about water saving in Kanpur should be conducted."
    ],
    'Barabanki' => [
        "🌾 Barabanki farmers should build field ponds for rainwater.",
        "🌱 Promote rain-fed crops in Barabanki.",
        "🧱 Encourage water storage structures in Barabanki villages."
    ],
    'Lakhimpur' => [
        "🌲 Lakhimpur has forested areas – store runoff water for later use.",
        "🚜 Promote contour bunding and farm ponds in Lakhimpur.",
        "💧 Use rainwater harvesting in Lakhimpur schools and rural households."
    ],
    'Unnao' => [
        "🏭 Industries in Unnao should recycle harvested rainwater.",
        "🌿 Farmers in Unnao should set up storage ponds.",
        "🏠 Rainwater harvesting should be adopted in government offices."
    ],
    'Ayodhya' => [
        "⛪ In Ayodhya, temples and large buildings should install harvesting tanks.",
        "🏡 Promote rooftop collection in residential colonies.",
        "🚰 Use harvested rainwater to recharge groundwater in Ayodhya."
    ],
    'Aliganj' => [
        "🏘️ Households in Aliganj should adopt rooftop harvesting.",
        "🌱 Encourage community-level water tanks in Aliganj.",
        "💧 Harvested rainwater can reduce summer water scarcity in Aliganj."
    ],
    'Bahraich' => [
        "🌾 Bahraich farmers should store rainwater for irrigation.",
        "🪣 Promote low-cost storage tanks in Bahraich villages.",
        "🌍 Encourage awareness campaigns in Bahraich about water conservation."
    ]
];

// Rain-based tips (based on average rain chance)
if ($averageRainChance > 80) {
    $rainTips = [
        "🌧️ Heavy rains expected in $location. Install rooftop rainwater collection systems.",
        "🧼 Ensure all storage tanks in $location are clean and covered.",
        "🌿 Use harvested rainwater in $location for irrigation and household cleaning."
    ];
} elseif ($averageRainChance > 50) {
    $rainTips = [
        "🪣 Moderate chance of rain in $location. Set up rain barrels to collect runoff.",
        "🧹 Clean gutters in houses/buildings to ensure smooth water flow.",
        "🪵 Prepare storage like drums/tanks for rainwater in $location."
    ];
} else {
    $rainTips = [
        "🌤️ Low rain chances in $location. Conserve existing stored water.",
        "💧 Use drip irrigation for gardens in $location to save water.",
        "🧰 Check and repair rain harvesting systems for future rains in $location."
    ];
}

// Final suggestions: city-specific tips first, then rain-based tips
$suggestions = array_merge(
    $citySuggestions[$location] ?? [],
    $rainTips
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Weather - <?php echo htmlspecialchars($location); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="css/style.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
      background: #b3d9ff;
      color: #222;
    }
    .container {
  max-width: 1000px;
  margin: auto;
  padding: 20px;
  background-color: white; /* Keep the container white for contrast */
  border-radius: 10px;
    }
    h1, h2, h3 {
      color: #0077b6;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 30px;
    }
    table, th, td {
      border: 1px solid #ccc;
    }
    th, td {
      text-align: center;
      padding: 10px;
    }
    th {
      background: #caf0f8;
    }
    tr:nth-child(even) {
      background: #edf6f9;
    }
    .suggestions {
      background: #d9f9d9;
      padding: 20px;
      border-left: 6px solid green;
      border-radius: 5px;
    }
    .suggestions ul {
      padding-left: 20px;
    }
    canvas {
      margin: 30px auto;
      display: block;
      max-width: 100%;
    }
    .btn-download {
      background: #0077b6;
      color: white;
      padding: 10px 20px;
      border: none;
      cursor: pointer;
      font-size: 16px;
      margin-bottom: 20px;
      border-radius: 5px;
    }
    .btn-download:hover {
      background: #023e8a;
    }
    @media (max-width: 768px) {
      table, tr, th, td {
        font-size: 14px;
      }
      h1 {
        font-size: 24px;
      }
    }
  </style>
</head>
<body>
  <div class="container" id="pdf-content">
    <h1>Rainwater Harvesting Planner</h1>

    <!-- ✅ Updated Dropdown -->
    <h2 style="text-align:center; color:#0077b6;">Select Location</h2>
    <form method="GET" action="" style="text-align:center; margin-bottom:20px;">
      <label for="location">Choose your area:</label>
      <select name="location" id="location" onchange="this.form.submit()">
        <?php foreach ($locations as $city): ?>
          <option value="<?php echo $city; ?>" <?php if($location==$city) echo "selected"; ?>>
            <?php echo $city; ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>

    <button class="btn-download" onclick="downloadPDF()">📄 Download PDF</button>

    <h2>Current Weather in <?php echo htmlspecialchars($location); ?></h2>
    <p><strong>Temperature:</strong> <?php echo htmlspecialchars($currentTemp); ?><?php echo is_numeric($currentTemp) ? "°C" : ""; ?></p>
    <p><strong>Humidity:</strong> <?php echo htmlspecialchars($humidity); ?><?php echo is_numeric($humidity) ? "%" : ""; ?></p>
    <p><strong>Precipitation:</strong> <?php echo htmlspecialchars($precip); ?> <?php echo is_numeric($precip) ? "mm" : ""; ?></p>
    <p><strong>Condition:</strong> <?php echo htmlspecialchars($condition); ?></p>

    <h3>Hourly Forecast (Next 12 Hours)</h3>
    <table>
      <tr><th>Time</th><th>Temp (°C)</th><th>Rain (%)</th></tr>
      <?php if (!empty($hourlyData)): ?>
        <?php foreach ($hourlyData as $hour): ?>
          <tr>
            <td><?php echo htmlspecialchars($hour['time']); ?></td>
            <td><?php echo htmlspecialchars($hour['temp_c']); ?>°C</td>
            <td><?php echo htmlspecialchars($hour['rain_chance']); ?>%</td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="3">Hourly data not available.</td></tr>
      <?php endif; ?>
    </table>

    <canvas id="rainChart" height="100"></canvas>

    <h3>7-Day Forecast</h3>
    <table>
      <tr><th>Date</th><th>Min Temp</th><th>Max Temp</th><th>Condition</th><th>Rain (%)</th></tr>
      <?php if (!empty($forecastData)): ?>
        <?php foreach ($forecastData as $day): ?>
          <tr>
            <td><?php echo htmlspecialchars($day['date']); ?></td>
            <td><?php echo htmlspecialchars($day['min_temp']); ?>°C</td>
            <td><?php echo htmlspecialchars($day['max_temp']); ?>°C</td>
            <td><?php echo htmlspecialchars($day['condition']); ?></td>
            <td><?php echo htmlspecialchars($day['rain_chance']); ?>%</td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="5">Forecast data not available.</td></tr>
      <?php endif; ?>
    </table>

    <div class="suggestions">
      <h3>Suggestions for Rainwater Harvesting</h3>
      <ul>
        <?php if (!empty($suggestions)): ?>
          <?php foreach ($suggestions as $tip): ?>
            <li><?php echo $tip; ?></li>
          <?php endforeach; ?>
        <?php else: ?>
          <li>💧 Collect rainwater from rooftops.</li>
          <li>🧼 Keep tanks clean and covered.</li>
          <li>🌿 Use harvested rainwater for gardens and cleaning.</li>
        <?php endif; ?>
      </ul>
    </div>

    <a href="index.php" style="display:block;margin-top:20px;text-align:center;">⬅ Back to Home</a>
  </div>

  <script>
    // Chart.js line chart
    const ctx = document.getElementById('rainChart') && document.getElementById('rainChart').getContext ? document.getElementById('rainChart').getContext('2d') : null;
    if (ctx) {
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: <?php echo json_encode($labels); ?>,
          datasets: [{
            label: 'Rain Probability (%)',
            data: <?php echo json_encode($rainChances); ?>,
            borderColor: '#0077b6',
            backgroundColor: 'rgba(0, 119, 182, 0.2)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#0077b6',
            pointRadius: 5,
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { display: true }
          },
          scales: {
            y: {
              beginAtZero: true,
              max: 100
            }
          }
        }
      });
    }

    // PDF download function
    function downloadPDF() {
      const element = document.getElementById('pdf-content');
      html2pdf().from(element).set({
        margin: 0.5,
        filename: 'Rainwater_Weather_Report.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
      }).save();
    }
  </script>
</body>
</html>
