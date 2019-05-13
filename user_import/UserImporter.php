<?php

/**
 * @file
 * Singleton class to import users from the command line.
 *
 * @author Shaun Moss <shaun.moss@gaiaresources.com.au>
 */

class UserImporter {

  /**
   * The single user importer object.
   *
   * @var \UserImporter
   */
  private static $userImporter;

  /**
   * Path to the CSV file.
   *
   * @var string
   */
  private $path;

  /**
   * If TRUE, create the table without importing users.
   * If FALSE (default), create the table and import users.
   *
   * @var bool
   */
  private $createTableOnly = FALSE;

  /**
   * If TRUE, read the users from the CSV file, but do not import them into the database.
   * If FALSE (default), read the users from the CSV file, and import them into the database.
   *
   * @var bool
   */
  private $dryRun = FALSE;

  /**
   * If TRUE, show the help text and that's all
   * If FALSE (default), don't show the help text.
   *
   * @var bool
   */
  private $showHelp = FALSE;

  /**
   * Array with database access details.
   *
   * @var array
   */
  private $db = [];

  /**
   * UserImporter constructor. Private for singleton pattern.
   */
  private function __construct() {}

  /**
   * Get the instance of UserImporter.
   *
   * @return \UserImporter
   */
  public static function getInstance() {
    if (!self::$userImporter) {
      self::$userImporter = new self;
    }
    return self::$userImporter;
  }

  /**
   * Read and check the command line arguments.
   *
   * @throws \Exception
   */
  public function processArguments() {
    global $argv;
    global $argc;

    for ($i = 0; $i < $argc; $i++) {
      $arg = $argv[$i];
      echo $arg . "\n";

      switch ($arg) {
        case '--file':
          if ($i + 1 < $argc) {
            $i++;
            $this->path = $argv[$i];
            if (!is_file($this->path)) {
              throw new Exception("Invalid path specified.");
            }
          }
          else {
            throw new Exception("Path to data file not specified.");
          }
          break;

        case '--create_table':
          $this->createTableOnly = TRUE;
          break;

        case '--dry_run':
          $this->dryRun = TRUE;
          break;

        case '-u':
          if ($i + 1 < $argc) {
            $i++;
            $this->db['user'] = $argv[$i];
          }
          else {
            throw new Exception("Database user not specified.");
          }
          break;

        case '-p':
          if ($i + 1 < $argc) {
            $i++;
            $this->db['password'] = $argv[$i];
          }
          else {
            throw new Exception("Database password not specified.");
          }
          break;

        case '-h':
          if ($i + 1 < $argc) {
            $i++;
            $this->db['host'] = $argv[$i];
          }
          else {
            throw new Exception("Database host not specified.");
          }
          break;

        case '--help':
          $this->showHelp = TRUE;
          break;

        default:
          throw new Exception("Invalid command line argument.");
          break;
      }
    }
  }

  /**
   * Create the users table, read the users, and import into the database table
   * (depending on command line parameters).
   */
  public function run() {
    if ($this->showHelp) {
//      $this->printHelpText();
      return;
    }

  }

}
