
<?php
function activate($uri) {
    if ($_SERVER['REQUEST_URI'] == $uri) {
        echo ' class="active" ';
    }
}

?>

<nav>
    <ul class="nav nav-tabs">
      <li role="tab" <?php activate('/wikireport/index.php'); ?> ><a href="index.php" aria-controls="Home">Wiki Report</a></li>
      <li role="tab" <?php activate('/wikireport/examples.php'); ?> ><a href="examples.php" aria-controls="Examples">Examples</a></li>
      <li role="tab" <?php activate('/wikireport/about.php'); ?> ><a href="about.php" aria-controls="About">About</a></li>
    </ul>
</nav>