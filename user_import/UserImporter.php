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
   * The database user.
   *
   * @var string
   */
  private $dbUser;

  /**
   * The database password.
   *
   * @var string
   */
  private $dbPassword;

  /**
   * The database host.
   *
   * @var string
   */
  private $dbHost;

  /**
   * Reference to the database itself.
   *
   * @var mysqli
   */
  private $db;

  /**
   * UserImporter constructor. Private for singleton pattern.
   */
  private function __construct() {
  }

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

    // Loop through args. Start at arg[1] so we skip the actual script name.
    for ($i = 1; $i < $argc; $i++) {
      $arg = $argv[$i];

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
            $this->dbUser = $argv[$i];
          }
          else {
            throw new Exception("Database user not specified.");
          }
          break;

        case '-p':
          if ($i + 1 < $argc) {
            $i++;
            $this->dbPassword = $argv[$i];
          }
          else {
            throw new Exception("Database password not specified.");
          }
          break;

        case '-h':
          if ($i + 1 < $argc) {
            $i++;
            $this->dbHost = $argv[$i];
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
   * Print the help text.
   */
  public function printHelpText() {
    echo "Command line directives\n";
    echo "\n";

    echo "--file [csv_file_name]\n";
    echo "Specify the path to the CSV file to be parsed. Required unless --help or --create_table is specified.\n";
    echo "\n";

    echo "--create_table\n";
    echo "Create the database table only, without importing the file. Optional.\n";
    echo "\n";

    echo "--dry_run\n";
    echo "Dry run. Read the CSV file and print the results, but don't insert data into the database. Optional.\n";
    echo "\n";

    echo "-u [username]\n";
    echo "The database user name. Required unless --help, --create_table, or --dry_run is specified.\n";
    echo "\n";

    echo "-p [password]\n";
    echo "The database password. Required unless --help, --create_table, or --dry_run is specified.\n";
    echo "\n";

    echo "-h [host]\n";
    echo "The database host. Required unless --help, --create_table, or --dry_run is specified.\n";
    echo "\n";

    echo "--help\n";
    echo "Display this help text (and do nothing else). Optional.\n";
    echo "\n";
  }

  /**
   * Connect to the database.
   *
   * @return \mysqli
   *   The MySQLi database object.
   *
   * @throws \Exception
   */
  protected function connectDatabase(): mysqli {
    // Check we have all the database parameters.
    if (empty($this->dbUser)) {
      throw new Exception("Database user not specified.");
    }
    if (empty($this->dbPassword)) {
      throw new Exception("Database password not specified.");
    }
    if (empty($this->dbHost)) {
      throw new Exception("Database host not specified.");
    }

    // Connect to the database table.
    $this->db = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, 'catalyst');
    if ($this->db->connect_error) {
      throw new Exception($this->db->connect_error);
    }

    echo "Successful connection to database.\n";
  }

  /**
   * Create or recreate the users table.
   *
   * @throws \Exception
   */
  protected function createUsersTable() {
    // Drop table if it already exists.
    echo "Creating users table.\n";
    $result = $this->db->query("drop table if exists users");
    if (!$result) {
      throw new Exception("Error dropping users table: " . $this->db->error);
    }

    // Create or recreate table.
    $result = $this->db->query("
      create table users
        email varchar(100) not null unique primary,
        first_name varchar(100) not null,
        last_name varchar(100) not null
    ");
    if (!$result) {
      throw new Exception("Error creating users table: " . $this->db->error);
    }
  }

  /**
   * Create the users table, read the users, and import into the database table
   * (depending on command line parameters).
   *
   * @throws \Exception
   */
  public function run() {
    if ($this->showHelp) {
      $this->printHelpText();
      return;
    }

    // Do we want to insert users into the database?
    $insert_users = $this->createTableOnly || !$this->dryRun;

    if ($insert_users) {
      $this->db = $this->connectDatabase();
      $this->createUsersTable();
    }

  }

}
