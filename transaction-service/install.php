<?php

/**
 * Go Transaction Service Cross-Platform Installer
 * Compatible with Windows, Linux, and macOS
 * Author: Auto-generated for Pintro Go Transaction Service
 */

class GoServiceInstaller
{
    private $isWindows;
    private $hasColor;

    public function __construct()
    {
        $this->isWindows = PHP_OS_FAMILY === 'Windows';
        $this->hasColor = !$this->isWindows || getenv('ANSICON') !== false;
    }

    /**
     * Print colored output
     */
    private function printColored($message, $color = 'default')
    {
        $colors = [
            'red' => "\033[0;31m",
            'green' => "\033[0;32m",
            'yellow' => "\033[1;33m",
            'blue' => "\033[0;34m",
            'reset' => "\033[0m"
        ];

        if ($this->hasColor && isset($colors[$color])) {
            echo $colors[$color] . $message . $colors['reset'] . PHP_EOL;
        } else {
            echo $message . PHP_EOL;
        }
    }

    private function printStatus($message)
    {
        $this->printColored("✅ $message", 'green');
    }

    private function printError($message)
    {
        $this->printColored("❌ $message", 'red');
    }

    private function printWarning($message)
    {
        $this->printColored("⚠️  $message", 'yellow');
    }

    private function printInfo($message)
    {
        $this->printColored("ℹ️  $message", 'blue');
    }

    /**
     * Execute command and return success status
     */
    private function execCommand($command, $description = '')
    {
        if ($description) {
            $this->printInfo($description);
        }

        echo "Running: $command" . PHP_EOL;
        
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode === 0) {
            if ($description) {
                $this->printStatus("$description completed successfully");
            }
            return true;
        } else {
            if ($description) {
                $this->printError("$description failed");
            }
            echo "Error output:" . PHP_EOL;
            foreach ($output as $line) {
                echo "  $line" . PHP_EOL;
            }
            return false;
        }
    }

    /**
     * Check if command exists
     */
    private function commandExists($command)
    {
        $whereCmd = $this->isWindows ? 'where' : 'which';
        $output = [];
        $returnCode = 0;
        exec("$whereCmd $command", $output, $returnCode);
        return $returnCode === 0;
    }

    /**
     * Get user input
     */
    private function getUserInput($prompt, $default = 'n')
    {
        echo "$prompt ";
        $handle = fopen("php://stdin", "r");
        $input = trim(fgets($handle));
        fclose($handle);
        
        return empty($input) ? $default : strtolower($input);
    }

    /**
     * Load environment variables from .env file
     */
    private function loadEnvVariables()
    {
        if (file_exists('.env')) {
            $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0) continue;
                if (strpos($line, '=') === false) continue;
                
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                if (!empty($key)) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                }
            }
        }
    }

    /**
     * Main installation process
     */
    public function install()
    {
        echo str_repeat('=', 50) . PHP_EOL;
        $this->printInfo("🚀 Starting Go Transaction Service Installation...");
        echo str_repeat('=', 50) . PHP_EOL;
        echo PHP_EOL;

        // Check prerequisites
        if (!$this->checkPrerequisites()) {
            return false;
        }

        // Step 1: Download Go dependencies
        if (!$this->downloadDependencies()) {
            return false;
        }

        // Step 2: Tidy modules
        $this->tidyModules();

        // Step 3: Environment setup
        $this->setupEnvironment();

        // Step 4: Install migrate tool
        $this->installMigrateTool();

        // Step 5: Database setup
        $this->setupDatabase();

        // Step 6: Run migrations
        $this->runMigrations();

        // Step 7: Build application
        if (!$this->buildApplication()) {
            return false;
        }

        // Step 8: Run tests
        $this->runTests();

        // Installation complete
        echo PHP_EOL;
        echo str_repeat('=', 50) . PHP_EOL;
        $this->printStatus("🎉 Installation completed successfully!");
        echo str_repeat('=', 50) . PHP_EOL;
        echo PHP_EOL;

        // Step 9: Start service
        $this->startService();

        return true;
    }

    private function checkPrerequisites()
    {
        $this->printInfo("Checking prerequisites...");

        // Check Go
        if (!$this->commandExists('go')) {
            $this->printError("Go is not installed or not in PATH");
            echo "Visit: https://golang.org/dl/" . PHP_EOL;
            return false;
        }

        $output = [];
        exec('go version', $output);
        $goVersion = isset($output[0]) ? $output[0] : 'Unknown';
        $this->printInfo("Go Version: $goVersion");

        // Check Docker (optional)
        if ($this->commandExists('docker')) {
            $this->printInfo("Docker found");
        } else {
            $this->printWarning("Docker not found - database setup will be limited");
        }

        $this->printStatus("Prerequisites check passed");
        echo PHP_EOL;
        return true;
    }

    private function downloadDependencies()
    {
        return $this->execCommand('go mod download', 'Downloading Go dependencies');
    }

    private function tidyModules()
    {
        if ($this->execCommand('go mod tidy', 'Tidying Go modules')) {
            // Success handled by execCommand
        } else {
            $this->printWarning("Failed to tidy Go modules, but continuing...");
        }
        echo PHP_EOL;
    }

    private function setupEnvironment()
    {
        if (!file_exists('.env')) {
            if (file_exists('.env.example')) {
                $this->printInfo("Creating .env file from .env.example...");
                if (copy('.env.example', '.env')) {
                    $this->printStatus(".env file created");
                    $this->printWarning("Please update .env file with your configuration before running the service");
                } else {
                    $this->printError("Failed to create .env file");
                }
            } else {
                $this->printWarning(".env.example not found. Please create .env file manually.");
            }
        } else {
            $this->printInfo(".env file already exists");
        }
        echo PHP_EOL;
    }

    private function installMigrateTool()
    {
        $this->printInfo("Installing database migration tool...");
        
        if ($this->commandExists('migrate')) {
            $this->printInfo("Migration tool already installed");
        } else {
            $installCmd = 'go install -tags "postgres" github.com/golang-migrate/migrate/v4/cmd/migrate@latest';
            if ($this->execCommand($installCmd, 'Installing migration tool')) {
                // Success handled by execCommand
            } else {
                $this->printWarning("Failed to install migration tool. You can install it manually:");
                echo "go install -tags 'postgres' github.com/golang-migrate/migrate/v4/cmd/migrate@latest" . PHP_EOL;
            }
        }
        echo PHP_EOL;
    }

    private function setupDatabase()
    {
        $this->printInfo("Setting up database...");
        
        if ($this->commandExists('docker') && ($this->commandExists('docker-compose') || $this->commandExists('docker'))) {
            $choice = $this->getUserInput("Do you want to start PostgreSQL using Docker Compose? (Y/n):", 'y');
            
            if ($choice === 'y' || $choice === 'yes') {
                $dockerComposeCmd = $this->commandExists('docker-compose') ? 'docker-compose' : 'docker compose';
                if ($this->execCommand("$dockerComposeCmd up -d postgres", 'Starting PostgreSQL with Docker Compose')) {
                    $this->printInfo("Waiting for PostgreSQL to be ready...");
                    sleep(5); // Wait for PostgreSQL to start
                    $this->printStatus("PostgreSQL should be ready");
                }
            } else {
                $this->printInfo("Skipping Docker database setup");
            }
        } else {
            $this->printWarning("Docker not available. Please ensure PostgreSQL is running before starting the service.");
        }
        echo PHP_EOL;
    }

    private function runMigrations()
    {
        $choice = $this->getUserInput("Do you want to run database migrations? (Y/n):", 'y');
        
        if ($choice === 'y' || $choice === 'yes') {
            $this->printInfo("Running database migrations...");
            
            // Load environment variables
            $this->loadEnvVariables();
            
            $dbUsername = getenv('DB_USERNAME') ?: 'postgres';
            $dbPassword = getenv('DB_PASSWORD') ?: 'password';
            $dbHost = getenv('DB_HOST') ?: 'localhost';
            $dbPort = getenv('DB_PORT') ?: '5432';
            $dbDatabase = getenv('DB_DATABASE') ?: 'transaction_db';
            $dbSslMode = getenv('DB_SSL_MODE') ?: 'disable';
            
            $dbUrl = "postgres://$dbUsername:$dbPassword@$dbHost:$dbPort/$dbDatabase?sslmode=$dbSslMode";
            
            if ($this->commandExists('migrate')) {
                $migrateCmd = "migrate -path migrations -database \"$dbUrl\" up";
                if ($this->execCommand($migrateCmd, 'Running database migrations')) {
                    // Success handled by execCommand
                } else {
                    $this->printWarning("Please check your database configuration in .env file");
                }
            } else {
                $this->printError("Migration tool not found. Please install it manually and run:");
                echo "migrate -path migrations -database \"$dbUrl\" up" . PHP_EOL;
            }
        } else {
            $this->printInfo("Skipping database migrations");
        }
        echo PHP_EOL;
    }

    private function buildApplication()
    {
        $this->printInfo("Building the application...");
        
        if (file_exists('Makefile') && $this->commandExists('make')) {
            return $this->execCommand('make build', 'Building with Make');
        } else {
            $buildCmd = $this->isWindows ? 
                'go build -o bin/go-transaction-service.exe ./cmd/server' :
                'go build -o bin/go-transaction-service ./cmd/server';
            return $this->execCommand($buildCmd, 'Building with go build');
        }
    }

    private function runTests()
    {
        $choice = $this->getUserInput("Do you want to run tests? (y/N):", 'n');
        
        if ($choice === 'y' || $choice === 'yes') {
            $this->printInfo("Running tests...");
            
            if (file_exists('Makefile') && $this->commandExists('make')) {
                $this->execCommand('make test', 'Running tests with Make');
            } else {
                $this->execCommand('go test ./tests/...', 'Running tests with go test');
            }
        } else {
            $this->printInfo("Skipping tests");
        }
        echo PHP_EOL;
    }

    private function startService()
    {
        $choice = $this->getUserInput("Do you want to start the Go service now? (Y/n):", 'y');
        
        if ($choice === 'n' || $choice === 'no') {
            $this->printInfo("You can start the service later with: make run");
            $this->printInfo("Or run directly: go run cmd/server/main.go");
            $this->printInfo("Default URL: http://localhost:8080");
            $this->printInfo("Health check: http://localhost:8080/health");
            return;
        }

        $this->printStatus("Starting Go Transaction Service...");
        $this->printInfo("Service will be available at: http://localhost:8080");
        $this->printInfo("Health check: http://localhost:8080/health");
        $this->printInfo("API Documentation: http://localhost:8080/api/v1");
        $this->printInfo("Press Ctrl+C to stop the service");
        echo PHP_EOL;

        // Open browser (optional)
        $browserChoice = $this->getUserInput("Do you want to open the health check in browser? (Y/n):", 'y');
        
        if ($browserChoice !== 'n' && $browserChoice !== 'no') {
            $this->openBrowser('http://localhost:8080/health');
        }

        // Start service
        echo "Starting service..." . PHP_EOL;
        
        if (file_exists('Makefile') && $this->commandExists('make')) {
            passthru('make run');
        } else {
            passthru('go run cmd/server/main.go');
        }
    }

    private function openBrowser($url)
    {
        if ($this->isWindows) {
            exec("start $url");
        } elseif (PHP_OS === 'Darwin') {
            exec("open $url");
        } else {
            exec("xdg-open $url");
        }
    }
}

// Run the installer
$installer = new GoServiceInstaller();
$installer->install();

echo PHP_EOL;
echo "✅ Thank you for using the Go Transaction Service installer! 🚀" . PHP_EOL;
echo PHP_EOL;
echo "ℹ️  Quick commands:" . PHP_EOL;
echo "  make run          - Run the service" . PHP_EOL;
echo "  make test         - Run tests" . PHP_EOL;
echo "  make build        - Build the application" . PHP_EOL;
echo "  make docker-build - Build Docker image" . PHP_EOL;
echo "  make help         - Show all available commands" . PHP_EOL;
