
<?php
function activate($uri) {
    if ($_SERVER['REQUEST_URI'] == $uri) {
        echo ' class="active" ';
    }
}
$Profiler = new \eqt\wikireport\Profiler();
$Profiler->start();
$Profiler->stopwatch();

?>

<nav>
    <ul class="nav nav-tabs">
      <li role="tab" <?php activate('/wikireport/index.php'); ?> ><a href="index.php" aria-controls="Home">Wiki Report</a></li>
      <li role="tab" <?php activate('/wikireport/examples.php'); ?> ><a href="examples.php" aria-controls="Examples">Examples</a></li>
      <li role="tab" <?php activate('/wikireport/stats.php'); ?> ><a href="stats.php" aria-controls="Statistics">Stats</a></li>
      <li role="tab" <?php activate('/wikireport/version.php'); ?> ><a href="version.php" aria-controls="Version Distribution">Version</a></li>
      <li role="tab" <?php activate('/wikireport/about.php'); ?> ><a href="about.php" aria-controls="About">About</a></li>
    </ul>
</nav>