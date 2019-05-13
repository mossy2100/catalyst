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
   * @throws \Exception
   */
  public function connectDatabase() {
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
   * Create the users table.
   *
   * @throws \Exception
   */
  public function createUsersTable() {
    // Drop table if it already exists.
    echo "Creating users table.\n";
    $result = $this->db->query("DROP TABLE IF EXISTS `users`");
    if (!$result) {
      throw new Exception("Error dropping users table: " . $this->db->error);
    }

    // Create table.
    $result = $this->db->query("
      CREATE TABLE `users` (
        `email` varchar(100) NOT NULL DEFAULT '',
        `name` varchar(100) NOT NULL DEFAULT '',
        `surname` varchar(100) NOT NULL DEFAULT '',
        PRIMARY KEY (`email`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    if (!$result) {
      throw new Exception("Error creating users table: " . $this->db->error);
    }
  }

  /**
   * Import the users and, if not a dry run, insert them into the database.
   *
   * @throws \Exception
   */
  public function importUsers() {
    // Read the users.
    $fh = fopen($this->path, 'r');
    if ($fh === FALSE) {
      throw new Exception("Error opening the data file.");
    }

    // Read the first line, which has the headings, and ignore it.
    $headings = fgetcsv($fh, 0, ',', '');
    if ($headings === FALSE) {
      throw new Exception("Error reading from the data file.");
    }

    // Read the user records.
    while (!feof($fh)) {
      $rec = fgetcsv($fh, 0, ',', '');
      if ($rec === FALSE) {
        throw new Exception("Error reading from the data file.");
      }
      var_dump($rec);
      if (count($rec) !== 3) {
        throw new Exception("Invalid user record (number of fields = " . count($rec) . ").\n");
      }

      // Massage the data.
      $email = $this->db->escape_string(strtolower(trim($rec[2])));
      $name = $this->db->escape_string(ucfirst(trim($rec[0])));
      $surname = $this->db->escape_string(ucfirst(trim($rec[1])));

      // Check we at least have an email address, since this is the primary key.
      if (empty($email)) {
        throw new Exception("Email address is required.\n");
      }

      // Display the user record.
      echo "$name $surname <$email>\n";

      // If this isn't a dry run, insert the user into the database.
      if (!$this->dryRun) {
        $this->db->query("
          INSERT `users` (email, name, surname)
          VALUES ('$email, '$name', '$surname')
        ");
      }
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
    $db_required = $this->createTableOnly || !$this->dryRun;

    if ($db_required) {
      $this->connectDatabase();
      $this->createUsersTable();
    }

    // If we're only creating the table, we're done.
    if ($this->createTableOnly) {
      return;
    }

    // Import the users.
    $this->importUsers();

    // Close the database.
    if ($db_required) {
      $this->db->close();
    }
  }

}
