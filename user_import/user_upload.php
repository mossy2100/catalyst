<?php

/**
 * @file
 * Import users from a CSV file into a MySQL database.
 *
 * @author Shaun Moss <shaun@astromultimedia.com>
 */

require __DIR__ . "/UserImporter.php";

try {
  $importer = UserImporter::getInstance();
  $importer->processArguments();
  $importer->run();
}
catch (Exception $e) {
  // Show error.
  echo $e->getMessage() . "\n";
  exit(1);
}

// Everything worked.
exit(0);
