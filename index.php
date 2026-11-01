<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/sentiment.php';

/* ── Load & filter dataset ──────────────────────────────── */
$dataFile = __DIR__ . '/data/sample_feedback.csv';
$feedback = load_feedback($dataFile);
$categories = array_values(array_unique(array_column($feedback, 'category')));
sort($categories);

$filterSentiment = $_GET['sentiment'] ?? 'All';
$filterCategory  = $_GET['category']  ?? 'All';
$search          = trim($_GET['q']    ?? '');

$filtered = array_values(array_filter($feedback, function ($row) use ($filterSentiment, $filterCategory, $search) {
    if ($filterSentiment !== 'All' && $row['sentiment'] !== $filterSentiment) return false;
    if ($filterCategory  !== 'All' && $row['category']  !== $filterCategory)  return false;
    if ($search !== '' && stripos($row['feedback'], $search) === false && stripos($row['id'], $search) === false) return false;
    return true;
}));

/* ── Stats ──────────────────────────────────────────────── */
$counts = ['Positive' => 0, 'Negative' => 0, 'Neutral' => 0];
foreach ($filtered as $row) $counts[$row['sentiment']] = ($counts[$row['sentiment']] ?? 0) + 1;
$total        = count($filtered);
$positiveRate = $total ? round(($counts['Positive'] / $total) * 100) : 0;

/* ── Trend & category maps ──────────────────────────────── */
$trendMap    = [];
$categoryMap = [];
foreach ($filtered as $row) {
    $month = date('M Y', strtotime($row['date']));
    $trendMap[$month] = $trendMap[$month] ?? ['Positive' => 0, 'Negative' => 0, 'Neutral' => 0];
    $trendMap[$month][$row['sentiment']]++;
    $categoryMap[$row['category']] = $categoryMap[$row['category']] ?? ['Positive' => 0, 'Negative' => 0, 'Neutral' => 0];
    $categoryMap[$row['category']][$row['sentiment']]++;
}
uksort($trendMap, fn ($a, $b) => strtotime($a) <=> strtotime($b));
ksort($categoryMap);

/* ── Single-comment analysis (analyzer panel) ───────────── */
$prediction     = null;
$predictionText = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['analyze_comment'])) {
    $predictionText = trim($_POST['comment'] ?? '');
    if ($predictionText !== '') $prediction = analyze_sentiment($predictionText);
}

/* ── Batch analysis (AI Agent panel) ────────────────────── */
$batchResult = null;
$batchRaw    = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_analyze'])) {
    $batchRaw = trim($_POST['batch_comments'] ?? '');
    if ($batchRaw !== '') {
        $lines = array_filter(array_map('trim', explode("\n", $batchRaw)));
        $batchResult = analyze_batch(array_values($lines));
    }
}

/* ── CSV export ─────────────────────────────────────────── */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sentiment_analysis_export.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Date', 'Category', 'Feedback', 'Sentiment']);
    foreach ($filtered as $r) fputcsv($out, [$r['id'], $r['date'], $r['category'], $r['feedback'], $r['sentiment']]);
    fclose($out);
    exit;
}

/* ── Helpers ────────────────────────────────────────────── */
function pct(int $value, int $total): int { return $total ? (int) round($value / $total * 100) : 0; }
function h($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FeedbackIQ — Sentiment Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="app-shell">

  <!-- ── Sidebar ─────────────────────────────────────────── -->
  <aside class="sidebar">
    <div class="brand">
      <div class="brand-mark">F</div>
      <div><strong>FeedbackIQ</strong><small>INSIGHT PLATFORM</small></div>
    </div>
    <div class="nav-label">WORKSPACE</div>
    <a class="nav-item active" href="index.php"><span class="nav-icon">▦</span> Overview</a>
    <a class="nav-item" href="#feedback"><span class="nav-icon">▤</span> Feedback data</a>
    <a class="nav-item" href="#analyzer"><span class="nav-icon">✳</span> Sentiment analyzer</a>
    <a class="nav-item" href="#ai-agent"><span class="nav-icon">◈</span> AI Assistant</a>
    <div class="sidebar-bottom">
      <div class="side-status">
        <span class="status-dot"></span>
        <div><b>System operational</b><small>Local analytics mode</small></div>
      </div>
      <div class="profile">
        <div class="avatar">AD</div>
        <div><b>Analyst workspace</b><small>Demo project</small></div>
      </div>
    </div>
  </aside>

  <!-- ── Main ────────────────────────────────────────────── -->
  <main class="main-content">

    <header class="topbar">
      <div>
        <div class="eyebrow">DATA ANALYTICS / OVERVIEW</div>
        <h1>Customer feedback</h1>
        <p class="subheading">Understand what your customers are saying.</p>
      </div>
      <div class="top-actions">
        <span class="date-chip">◷ &nbsp; <?= date('d M Y') ?></span>
        <a class="export-btn" href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>">↓ &nbsp; Export CSV</a>
      </div>
    </header>

    <section class="welcome-strip">
      <div><span class="sparkle">✦</span><div><b>Your feedback at a glance</b><p>Track customer opinions, spot patterns, and turn feedback into actionable insights.</p></div></div>
      <span class="live-pill"><i></i> LIVE OVERVIEW</span>
    </section>

    <!-- ── Filters ──────────────────────────────────────── -->
    <form class="filters" method="get">
      <div class="filter-title">FILTER DATA</div>
      <label>Sentiment
        <select name="sentiment">
          <option>All</option>
          <?php foreach (['Positive', 'Negative', 'Neutral'] as $s): ?>
            <option value="<?= h($s) ?>" <?= $filterSentiment === $s ? 'selected' : '' ?>><?= h($s) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Category
        <select name="category">
          <option>All</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= h($c) ?>" <?= $filterCategory === $c ? 'selected' : '' ?>><?= h($c) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="search-filter">Search feedback
        <input type="search" name="q" value="<?= h($search) ?>" placeholder="Search keyword or ID...">
      </label>
      <button class="apply-btn" type="submit">Apply filters</button>
      <a class="reset-link" href="index.php">Reset</a>
    </form>

    <!-- ── Stats cards ──────────────────────────────────── -->
    <section class="stats-grid">
      <article class="stat-card">
        <div class="stat-top"><span>Total feedback</span><span class="stat-icon purple">▤</span></div>
        <div class="stat-value"><?= number_format($total) ?></div>
        <div class="stat-foot">Records in current view</div>
      </article>
      <article class="stat-card">
        <div class="stat-top"><span>Positive feedback</span><span class="stat-icon green">↗</span></div>
        <div class="stat-value"><?= number_format($counts['Positive']) ?></div>
        <div class="stat-foot"><span class="positive-text"><?= pct($counts['Positive'], $total) ?>%</span> of filtered feedback</div>
      </article>
      <article class="stat-card">
        <div class="stat-top"><span>Negative feedback</span><span class="stat-icon red">↘</span></div>
        <div class="stat-value"><?= number_format($counts['Negative']) ?></div>
        <div class="stat-foot"><span class="negative-text"><?= pct($counts['Negative'], $total) ?>%</span> of filtered feedback</div>
      </article>
      <article class="stat-card">
        <div class="stat-top"><span>Positive rate</span><span class="stat-icon blue">％</span></div>
        <div class="stat-value"><?= $positiveRate ?>%</div>
        <div class="stat-foot">Share of positive sentiment</div>
      </article>
    </section>

    <!-- ── Charts row 1 ─────────────────────────────────── -->
    <section class="charts-grid">
      <article class="panel sentiment-panel">
        <div class="panel-heading">
          <div><h2>Sentiment distribution</h2><p>Breakdown of customer opinions</p></div>
          <span class="more">•••</span>
        </div>
        <div class="donut-wrap">
          <div class="donut-canvas">
            <canvas id="sentimentChart"></canvas>
            <div class="donut-center"><strong><?= $total ?></strong><span>FEEDBACK</span></div>
          </div>
          <div class="legend-list">
            <?php foreach (['Positive' => 'green', 'Negative' => 'red', 'Neutral' => 'yellow'] as $name => $color): ?>
              <div class="legend-row">
                <span class="legend-dot <?= $color ?>"></span>
                <span><?= $name ?></span>
                <b><?= pct($counts[$name], $total) ?>%</b>
                <small><?= $counts[$name] ?></small>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </article>
      <article class="panel trend-panel">
        <div class="panel-heading">
          <div><h2>Sentiment over time</h2><p>Monthly feedback trend</p></div>
          <span class="chart-note">MONTHLY</span>
        </div>
        <div class="chart-area"><canvas id="trendChart"></canvas></div>
      </article>
    </section>

    <!-- ── Charts row 2 ─────────────────────────────────── -->
    <section class="charts-grid lower-grid">
      <article class="panel">
        <div class="panel-heading"><div><h2>Sentiment by category</h2><p>How each category is perceived</p></div></div>
        <div class="chart-area category-chart"><canvas id="categoryChart"></canvas></div>
      </article>
      <article class="panel insights-panel">
        <div class="panel-heading"><div><h2>Quick insights</h2><p>Generated from current filters</p></div><span class="insight-icon">✦</span></div>
        <?php
          $topPositive = '—'; $topNegative = '—'; $maxP = -1; $maxN = -1;
          foreach ($categoryMap as $cat => $v) {
              if ($v['Positive'] > $maxP) { $maxP = $v['Positive']; $topPositive = $cat; }
              if ($v['Negative'] > $maxN) { $maxN = $v['Negative']; $topNegative = $cat; }
          }
        ?>
        <div class="insight-item">
          <span class="insight-bullet green-bg">↗</span>
          <div><b>Positive feedback leader</b><p><?= h($topPositive) ?> has the highest number of positive comments in this view.</p></div>
        </div>
        <div class="insight-item">
          <span class="insight-bullet red-bg">!</span>
          <div><b>Needs attention</b><p><?= h($topNegative) ?> has the highest number of negative comments in this view.</p></div>
        </div>
        <div class="insight-item">
          <span class="insight-bullet purple-bg">◎</span>
          <div><b>Overall sentiment</b><p><?= $positiveRate ?>% of the visible feedback is positive.</p></div>
        </div>
      </article>
    </section>

    <!-- ── Feedback table ───────────────────────────────── -->
    <section class="panel table-panel" id="feedback">
      <div class="panel-heading">
        <div><h2>Recent feedback</h2><p>Customer comments and detected sentiment</p></div>
        <span class="record-count"><?= count($filtered) ?> RECORDS</span>
      </div>
      <div class="table-scroll">
        <table>
          <thead><tr><th>FEEDBACK ID</th><th>DATE</th><th>CATEGORY</th><th>CUSTOMER FEEDBACK</th><th>SENTIMENT</th></tr></thead>
          <tbody>
          <?php foreach (array_slice(array_reverse($filtered), 0, 12) as $r): ?>
            <tr>
              <td class="id-cell"><?= h($r['id']) ?></td>
              <td><?= h(date('d M Y', strtotime($r['date']))) ?></td>
              <td><span class="category-tag"><?= h($r['category']) ?></span></td>
              <td class="feedback-cell"><?= h($r['feedback']) ?></td>
              <td><span class="sentiment-badge <?= strtolower($r['sentiment']) ?>"><i></i><?= h($r['sentiment']) ?></span></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$filtered): ?>
            <tr><td colspan="5" class="empty-state">No feedback matches these filters.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="table-footer">
        Showing <?= min(12, count($filtered)) ?> of <?= count($filtered) ?> filtered records &nbsp;
        <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>">Download all results ↗</a>
      </div>
    </section>

    <!-- ── Sentiment analyzer (single comment) ──────────── -->
    <section class="panel analyzer-panel" id="analyzer">
      <div class="panel-heading">
        <div><h2>Try the sentiment analyzer</h2><p>Enter a customer comment to estimate its sentiment.</p></div>
        <span class="analyzer-label">LEXICON-BASED NLP</span>
      </div>
      <form method="post" class="analyzer-form">
        <textarea name="comment" rows="3" placeholder="Example: Pelayanannya cepat dan sangat ramah, saya puas..." required><?= h($predictionText) ?></textarea>
        <div class="analyzer-actions">
          <span>Supports Indonesian comments</span>
          <button class="analyze-btn" type="submit" name="analyze_comment">✦ Analyze sentiment</button>
        </div>
      </form>
      <?php if ($prediction): ?>
        <div class="prediction-result">
          <span class="sentiment-badge <?= strtolower($prediction['sentiment']) ?>"><i></i><?= h($prediction['sentiment']) ?></span>
          <div>
            <b>Analysis result</b>
            <p><?= h($prediction['explanation']) ?></p>
            <?php if ($prediction['complaints']): ?>
              <p class="tag-row"><span class="tag-label red-tag">Complaints:</span> <?= h(implode(', ', $prediction['complaints'])) ?></p>
            <?php endif; ?>
            <?php if ($prediction['appreciations']): ?>
              <p class="tag-row"><span class="tag-label green-tag">Appreciations:</span> <?= h(implode(', ', $prediction['appreciations'])) ?></p>
            <?php endif; ?>
            <?php if ($prediction['recommendation']): ?>
              <p class="tag-row"><span class="tag-label blue-tag">Recommendation:</span> <?= h($prediction['recommendation']) ?></p>
            <?php endif; ?>
          </div>
          <strong class="score-badge"><?= h((string) $prediction['score']) ?></strong>
        </div>
      <?php endif; ?>
    </section>

    <!-- ── AI Agent panel ───────────────────────────────── -->
    <section class="panel ai-agent-panel" id="ai-agent">
      <div class="panel-heading">
        <div>
          <h2>Customer Feedback AI Assistant</h2>
          <p>Analyze multiple feedback entries at once — get sentiment, complaints, appreciations &amp; recommendations.</p>
        </div>
        <span class="agent-label">AI AGENT</span>
      </div>

      <div class="agent-intro">
        <div class="agent-capabilities">
          <span class="cap-chip">📊 Classify sentiment</span>
          <span class="cap-chip">🔍 Explain classification</span>
          <span class="cap-chip">⚠ Identify complaints</span>
          <span class="cap-chip">✓ Identify appreciations</span>
          <span class="cap-chip">💡 Recommend improvements</span>
          <span class="cap-chip">📋 Batch analysis</span>
          <span class="cap-chip">📝 Insight summary</span>
        </div>
        <p class="agent-note"><strong>Engine:</strong> Lexicon-based NLP (rule engine). This assistant does <em>not</em> use an external LLM or paid API. Analysis is performed locally using a curated Indonesian lexicon.</p>
      </div>

      <form method="post" class="batch-form">
        <label class="batch-label">Enter one feedback per line (supports bulk analysis):</label>
        <textarea name="batch_comments" rows="6" placeholder="Pelayanannya lambat dan mengecewakan.&#10;Produk bagus, saya sangat puas!&#10;Harga terlalu mahal untuk kualitas ini.&#10;Aplikasi mudah digunakan dan tampilannya menarik." required><?= h($batchRaw) ?></textarea>
        <div class="analyzer-actions">
          <span><?= $batchResult ? count($batchResult['results']) . ' feedback analyzed' : 'Paste or type feedback below' ?></span>
          <button class="analyze-btn" type="submit" name="batch_analyze">◈ Run AI Analysis</button>
        </div>
      </form>

      <?php if ($batchResult): ?>
        <!-- Summary strip -->
        <div class="batch-summary">
          <div class="bs-stat"><span>Total analyzed</span><strong><?= $batchResult['total'] ?></strong></div>
          <div class="bs-stat green-stat"><span>Positive</span><strong><?= $batchResult['summary']['Positive'] ?></strong></div>
          <div class="bs-stat red-stat"><span>Negative</span><strong><?= $batchResult['summary']['Negative'] ?></strong></div>
          <div class="bs-stat yellow-stat"><span>Neutral</span><strong><?= $batchResult['summary']['Neutral'] ?></strong></div>
          <div class="bs-stat blue-stat"><span>Positive rate</span><strong><?= $batchResult['positive_rate'] ?>%</strong></div>
          <div class="bs-stat purple-stat"><span>Dominant sentiment</span><strong><?= h($batchResult['dominant']) ?></strong></div>
        </div>

        <!-- Insight summary -->
        <div class="insight-summary-box">
          <h3>📋 Insight Summary</h3>
          <div class="is-row">
            <div>
              <b>Top complaints detected:</b>
              <?php if ($batchResult['top_complaints']): ?>
                <ul class="kw-list"><?php foreach ($batchResult['top_complaints'] as $kw): ?><li><?= h($kw) ?></li><?php endforeach; ?></ul>
              <?php else: ?><p class="muted">No complaints detected.</p><?php endif; ?>
            </div>
            <div>
              <b>Top appreciations detected:</b>
              <?php if ($batchResult['top_appreciations']): ?>
                <ul class="kw-list green-kw"><?php foreach ($batchResult['top_appreciations'] as $kw): ?><li><?= h($kw) ?></li><?php endforeach; ?></ul>
              <?php else: ?><p class="muted">No appreciations detected.</p><?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Per-feedback breakdown -->
        <div class="batch-results">
          <h3>Detailed analysis</h3>
          <?php foreach ($batchResult['results'] as $idx => $res): ?>
            <div class="batch-item <?= strtolower($res['sentiment']) ?>-item">
              <div class="bi-header">
                <span class="bi-num">#<?= $idx + 1 ?></span>
                <span class="sentiment-badge <?= strtolower($res['sentiment']) ?>"><i></i><?= h($res['sentiment']) ?></span>
                <span class="bi-score">Score: <?= $res['score'] ?></span>
              </div>
              <p class="bi-text">"<?= h($res['text']) ?>"</p>
              <p class="bi-explain"><?= h($res['explanation']) ?></p>
              <?php if ($res['complaints']): ?>
                <p class="tag-row"><span class="tag-label red-tag">Complaints:</span> <?= h(implode(', ', $res['complaints'])) ?></p>
              <?php endif; ?>
              <?php if ($res['appreciations']): ?>
                <p class="tag-row"><span class="tag-label green-tag">Appreciations:</span> <?= h(implode(', ', $res['appreciations'])) ?></p>
              <?php endif; ?>
              <?php if ($res['recommendation']): ?>
                <p class="tag-row"><span class="tag-label blue-tag">Recommendation:</span> <?= h($res['recommendation']) ?></p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <footer>FeedbackIQ <span>•</span> Customer Feedback Sentiment Analyzer <span>•</span> Demo dataset for academic project</footer>
  </main>
</div>

<script>
const sentimentData  = <?= json_encode(array_values($counts)) ?>;
const trendLabels    = <?= json_encode(array_keys($trendMap)) ?>;
const trendData      = <?= json_encode(array_values($trendMap)) ?>;
const categoryLabels = <?= json_encode(array_keys($categoryMap)) ?>;
const categoryData   = <?= json_encode(array_values($categoryMap)) ?>;
</script>
<script src="assets/js/dashboard.js"></script>
</body>
</html>
