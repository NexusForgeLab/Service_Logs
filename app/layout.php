<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

function render_header(string $title, ?array $user=null): void {
  $user = $user ?? current_user();
  
  // Check for admin notifications (Badge Logic)
  $notifBadge = '';
  if ($user && is_admin()) {
      $pdo = db();
      try {
          // Wrap in try-catch in case table doesn't exist yet
          $count = $pdo->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
          if ($count > 0) {
              $notifBadge = " <span style='background:var(--pop-red); color:white; border-radius:50%; padding:2px 6px; font-size:10px; vertical-align:top;'>$count</span>";
          }
      } catch (Exception $e) {} 
  }

  echo "<!doctype html><html><head>
  <meta charset='utf-8'/>
  <meta name='viewport' content='width=device-width,initial-scale=1'/>
  <title>".h($title)."</title>
  
  <link rel='manifest' href='/manifest.json'>
  <link rel='icon' type='image/png' href='/assets/logo.png'>
  <link rel='apple-touch-icon' href='/assets/icon-192.png'>
  <meta name='theme-color' content='#2c3e50'>

  <link rel='preconnect' href='https://fonts.googleapis.com'>
  <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
  <link href='https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap' rel='stylesheet'>
  
  <link rel='stylesheet' href='/assets/style.css'/>
  
  <script>
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('/sw.js')
        .then(() => console.log('SW Registered'))
        .catch(err => console.log('SW Fail', err));
    }
  </script>
  </head><body><div class='wrap'>";
  
  echo "<div class='topbar'>
          <div class='brand'><a href='/'>Service Log</a></div>";
  
  if ($user) {
    echo "<div class='nav'>
            <a class='btn' href='/'>Home</a>
            <a class='btn' href='/search.php'>Search</a>";
            
    // Admin Only Link
    if (is_admin()) {
        echo "<a class='btn' href='/admin_machines.php'>Machines$notifBadge</a>";
    }

    echo "<a class='btn' href='/users.php'>Users</a>
          <a class='btn' href='/logout.php'>Logout</a>
          </div>";
  } else {
    echo "<div class='nav'>
            <a class='btn' href='/login.php'>Login</a>
          </div>";
  }
  echo "</div>";
}

function render_footer(): void {
  echo "</div><script src='/assets/app.js'></script></body></html>";
}