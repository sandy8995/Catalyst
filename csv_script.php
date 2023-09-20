<?php

// Class for handling Database.

class DatabaseHandler
{
    /**
     * @var string.
     */
    private $conn;

    /**
     * Construct method
     * 
     * @param $host
     *   Host name.
     * @param  $username
     *   Username.
     * @param $password
     *   Password.
     * @param $dbname
     *   Database name.
     */
    public function __construct($host, $username, $password, $dbname)
    {
        $this->conn = new mysqli($host, $username, $password);

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }

        // Create the database if it doesn't exist
        $this->conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
        $this->conn->select_db($dbname);
    }

    /**
     * Create user table.
     */
    public function createUsersTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            surname VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL
        )";

        if ($this->conn->query($sql) === TRUE) {
            echo "Table 'users' created successfully\n";
        } else {
            echo "Error creating table: " . $this->conn->error . "\n";
            exit(1);
        }
    }

    /**
     * Insert user table.
     * 
     * @param string $name
     *   Name.
     * @param string $surname
     *   Surname.
     * @param string $email
     *   Email.
     */
    public function insertUser($name, $surname, $email)
    {
        $name = ucwords(strtolower($name));
        $surname = ucwords(strtolower($surname));
        $email = strtolower($email);

        if (!$this->validateEmail($email)) {
            echo "Invalid email format: $email\n";
            return false;
        }

        $sql = "INSERT INTO users (name, surname, email) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sss", $name, $surname, $email);

        if ($stmt->execute()) {
            echo "Record inserted successfully\n";
            return true;
        } else {
            echo "Error inserting record: " . $stmt->error . "\n";
            return false;
        }
    }

    /**
     * Validate email.
     * 
     * @param string $email
     *   Email.
     */
    private function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Close connection.
     * 
     */
    public function closeConnection()
    {
        $this->conn->close();
    }
}

// Class for csv process.

class CsvProcessor
{
   
    /**
     * @var string
     */
    private $db;

    /**
     * @var boolean
     */
    private $dryRun = false;

    /**
     * Construct method
     * 
     * @param $db
     *   DB name.
     * @param $dryRun
     *   Dry run.
     */
    public function __construct($db, $dryRun)
    {
        $this->db = $db;
        $this->dryRun = $dryRun;
    }

    
    /**
     * Process csv.
     * 
     * @param $csvFile
     *   CSV file.
     */
    public function processCsv($csvFile)
    {
        $this->db->createUsersTable();

        $file = fopen($csvFile, 'r');
        if (!$file) {
            echo "Error opening CSV file\n";
            exit(1);
        }

        while (($data = fgetcsv($file)) !== false) {
            if (count($data) !== 3) {
                echo "Invalid CSV format\n";
                continue;
            }

            $name = $data[0];
            $surname = $data[1];
            $email = $data[2];

            if (!$this->dryRun) {
                $this->db->insertUser($name, $surname, $email);
            } else {
                echo "Dry run: Record not inserted into the database\n";
            }
        }

        fclose($file);
    }
}

// Function to display help information.
function displayHelp()
{
    echo "Usage: php csv_script.php [options]\n";
    echo "Options:\n";
    echo "  --file [csv file name]   this is the name of the CSV to be parsed\n";
    echo "  --create_table           this will cause the MySQL users table to be built (and no further
    • action will be taken)\n";
    echo "  --dry_run                this will be used with the --file directive in case we want to run the script but not
    insert into the DB. All other functions will be executed, but the database won't be altered\n";
    echo "  -u [MySQL username]      MySQL username\n";
    echo "  -p [MySQL password]      MySQL password\n";
    echo "  -h [MySQL host]          MySQL host\n";
    echo "  --help                   which will output the above list of directives with details.\n";
}

// Parse command line options.
$options = getopt("u:p:h:", ["file:", "create_table", "dry_run", "help"]);

if (isset($options['help'])) {
    displayHelp();
    exit(0);
}

if (!isset($options['file']) || empty($options['file'])) {
    echo "Error: Missing CSV file option (--file)\n";
    exit(1);
}

$csvFile = $options['file'];
$createTable = isset($options['create_table']);
$dryRun = isset($options['dry_run']);
$username = isset($options['u']) ? $options['u'] : "username";
$password = isset($options['p']) ? $options['p'] : "password";
$host = isset($options['h']) ? $options['h'] : "localhost";
$dbname = "task";

$db = new DatabaseHandler($host, $username, $password, $dbname);
$csvProcessor = new CsvProcessor($db, $dryRun);

if ($createTable) {
    $db->createUsersTable();
    $db->closeConnection();
    exit(0);
}

$csvProcessor->processCsv($csvFile);
$db->closeConnection();
?>
