<?php
/**
 * fac_gen.php — Staff (Students) listing via gRPC microservice
 * NOTE:
 * - Requires composer autoload + generated PHP stubs in /var/www/html/php_client
 * - Talks to gRPC server in container: student-grpc:50051
 * - No mysqli is used here — purely gRPC.
 */
declare(strict_types=1);

// Output and encoding
header('Content-Type: text/html; charset=utf-8');

// 1) Composer autoload
require __DIR__ . '/../vendor/autoload.php';

// 2) Load generated PHP classes (from protoc) — adjust if your paths differ
//    Expected structure:
//      /var/www/html/php_client/GPBMetadata/*.php
//      /var/www/html/php_client/Student/*.php
$genBase = realpath(__DIR__ . '/../php_client');
if (!$genBase) {
  http_response_code(500);
  echo "<h3 style='color:#b00'>Generated PHP stubs not found in php_client/. Did you run protoc?</h3>";
  exit;
}
foreach (glob($genBase . '/GPBMetadata/*.php') as $f) require_once $f;
foreach (glob($genBase . '/Student/*.php') as $f)     require_once $f;

// 3) Use generated namespaces
use Grpc\ChannelCredentials;
use Google\Protobuf\GPBEmpty;
use Student\StudentServiceClient;

// 4) Call gRPC: ListStudents
$students = [];
$errorMsg = null;
try {
  $client = new StudentServiceClient('student-grpc:50051', [
    'credentials' => ChannelCredentials::createInsecure(),
  ]);
  list($resp, $status) = $client->ListStudents(new GPBEmpty())->wait();

  if ($status->code !== \Grpc\STATUS_OK) {
    $errorMsg = "gRPC error ({$status->code}): " . htmlspecialchars($status->details ?? 'Unknown');
  } else {
    $students = $resp->getStudents();
  }
} catch (Throwable $e) {
  $errorMsg = "Exception calling gRPC: " . htmlspecialchars($e->getMessage());
}

// Helper for HTML escaping
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>:: SAHJANAND VIDHYALAY, RAJKOT :: Staff</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Minimal, clean styling (keeps your classic layout but looks crisp) -->
  <style>
    :root {
      --bg: #E2F0FE; --card:#fff; --primary:#165DFF; --muted:#6b7280; --border:#e5e7eb;
      --success:#0ea5e9; --danger:#ef4444;
    }
    html,body { margin:0; font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; background: var(--bg); color:#111; }
    a { color: var(--primary); text-decoration: none; }
    .wrap { max-width: 1100px; margin: 0 auto; }
    header { background: #0b5bd3; color:#fff; padding: 16px; display:flex; align-items:center; justify-content:space-between; }
    header h1 { font-size: 20px; margin:0; letter-spacing: .3px; }
    nav a { color:#eaf1ff; margin-left: 12px; font-weight: 500; }
    .grid { display:grid; grid-template-columns: 220px 1fr; gap: 16px; padding:16px; }
    .card { background: var(--card); border:1px solid var(--border); border-radius: 12px; overflow: hidden; }
    .side { padding: 16px; }
    .side h3 { margin:0 0 12px; color:#0b5bd3; font-size: 15px; letter-spacing:.2px; }
    .menu a { display:block; padding:10px 12px; border-radius: 8px; color:#0f172a; margin-bottom:6px; }
    .menu a:hover { background:#eef5ff; }
    .content { padding: 16px; }
    .titlebar { display:flex; align-items:center; justify-content:space-between; margin-bottom: 12px; }
    .titlebar h2 { margin:0; font-size: 18px; }
    .pill { font-size: 12px; padding: 2px 8px; border-radius: 999px; background:#eef2ff; color:#3730a3; border:1px solid #c7d2fe; }
    .alert { padding:12px 14px; border:1px solid var(--border); background:#fff7f7; color:#991b1b; border-left:4px solid var(--danger); border-radius: 8px; }
    table { width:100%; border-collapse: collapse; border:1px solid var(--border); overflow: hidden; border-radius: 10px; background:#fff; }
    thead th { text-align:left; font-size:13px; letter-spacing:.3px; color:#374151; background:#f3f4f6; padding:10px 12px; border-bottom:1px solid var(--border); }
    tbody td { padding:12px; border-bottom:1px solid var(--border); font-size:14px; }
    tbody tr:hover { background:#fafafa; }
    tfoot td { padding:10px 12px; color: var(--muted); font-size:12px; }
    footer { text-align:center; padding: 12px; color:#334155; font-size: 13px; }
    .muted { color: var(--muted); }
  </style>
</head>
<body>
  <header>
    <div class="wrap" style="display:flex; align-items:center; justify-content:space-between;">
      <h1>SAHJANAND VIDHYALAY, RAJKOT</h1>
      <nav>
        <a href="/client_user/index.php">Home</a>
        <a href="/client_user/about.php">About</a>
        <a href="/client_user/contact.php">Contact</a>
      </nav>
    </div>
  </header>

  <main class="wrap grid">
    <!-- Left sidebar -->
    <aside class="card side">
      <h3>Menu</h3>
      <div class="menu">
        <a href="/client_user/index.php">Home</a>
        <a href="/client_user/about.php">About us</a>
        <a href="/client_user/standard.php">Standard</a>
        <a href="/client_user/STAFF.php">Staff</a>
        <a href="/client_user/admission.php">Admission</a>
        <a href="/client_user/activity.php">Student Activity</a>
        <a href="/client_user/contact.php">Contact us</a>
        <a href="/client_user/feedback.php">Feedback</a>
      </div>
    </aside>

    <!-- Main content -->
    <section class="card content">
      <div class="titlebar">
        <h2>General Staff</h2>
        <span class="pill"><?= $h(count($students)) ?> records</span>
      </div>

      <?php if ($errorMsg): ?>
        <div class="alert"><?= $errorMsg ?></div>
      <?php else: ?>
        <table aria-label="Staff table">
          <thead>
            <tr>
              <th style="width:80px">ID</th>
              <th>Name</th>
              <th style="width:220px">Department</th>
            </tr>
          </thead>
          <tbody>
          <?php if (count($students) === 0): ?>
            <tr><td colspan="3" class="muted">No records found.</td></tr>
          <?php else: ?>
            <?php foreach ($students as $s): ?>
              <tr>
                <td><?= $h($s->getId()) ?></td>
                <td><?= $h($s->getName()) ?></td>
                <td><?= $h($s->getDepartment()) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
          <tfoot>
            <tr><td colspan="3">Data served via gRPC microservice (Python) & MySQL.</td></tr>
          </tfoot>
        </table>
      <?php endif; ?>
    </section>
  </main>

  <footer>
    © SAHJANAND VIDHYALAY, RAJKOT
  </footer>
</body>
</html>