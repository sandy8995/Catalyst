<?php

// Function to validate an email address
function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Function to capitalize the first letter of a string
function capitalize(string $str): string {
    return ucwords(strtolower($str));
}

// Function to create a MySQL database connection
function createDatabaseConnection(string $host, string $username, string $password, string $dbname): mysqli {
    $conn = new mysqli($host, $username, $password);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Create the database if it doesn't exist
    $conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
    $conn->select_db($dbname);

    return $conn;
}

// Function to create the "users" table
function createUsersTable(mysqli $conn) {
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        surname VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL
    )";

    if ($conn->query($sql) === TRUE) {
        echo "Table 'users' created successfully\n";
    } else {
        echo "Error creating table: " . $conn->error . "\n";
        exit(1);
    }
}

// Main function to process the CSV file and insert data into the database
function processCSV(string $csvFile, mysqli $conn, bool $dryRun) {
    createUsersTable($conn);

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

        $name = capitalize($data[0]);
        $surname = capitalize($data[1]);
        $email = strtolower($data[2]);

        if (!validateEmail($email)) {
            echo "Invalid email format: {$data[2]}\n";
            continue;
        }

        if (!$dryRun) {
            $sql = "INSERT INTO users (name, surname, email) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $name, $surname, $email);

            if ($stmt->execute()) {
                echo "Record inserted successfully\n";
            } else {
                echo "Error inserting record: " . $stmt->error . "\n";
            }
        } else {
            echo "Dry run: Record not inserted into the database\n";
        }
    }

    fclose($file);
}

// Function to display help information
function displayHelp() {
    echo "Usage: php script.php [options]\n";
    echo "Options:\n";
    echo "  --file [csv file name]   Specify the CSV file to be parsed\n";
    echo "  --create_table           Create the MySQL 'users' table and exit\n";
    echo "  --dry_run                Run the script without inserting into the database\n";
    echo "  -u [MySQL username]      Specify the MySQL username\n";
    echo "  -p [MySQL password]      Specify the MySQL password\n";
    echo "  -h [MySQL host]          Specify the MySQL host\n";
    echo "  --help                   Display this help message\n";
}

// Parse command line options
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
$username = isset($options['u']) ? $options['u'] : "your_username";
$password = isset($options['p']) ? $options['p'] : "your_password";
$host = isset($options['h']) ? $options['h'] : "localhost";
$dbname = "task";

$conn = createDatabaseConnection($host, $username, $password, $dbname);

if ($createTable) {
    createUsersTable($conn);
    exit(0);
}

processCSV($csvFile, $conn, $dryRun);
$conn->close();
?>
