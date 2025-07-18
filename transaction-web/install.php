<?php

/**
 * Laravel Project Cross-Platform Installer
 * Compatible with Windows, Linux, and macOS
 * Author: Auto-generated for Pintro Transaction Web
 */

class LaravelInstaller
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
     * Main installation process
     */
    public function install()
    {
        echo str_repeat('=', 50) . PHP_EOL;
        $this->printInfo("🚀 Starting Laravel Project Installation...");
        echo str_repeat('=', 50) . PHP_EOL;
        echo PHP_EOL;

        // Check prerequisites
        if (!$this->checkPrerequisites()) {
            return false;
        }

        // Step 1: Composer install
        if (!$this->installComposerDependencies()) {
            return false;
        }

        // Step 2: Node dependencies
        if (!$this->installNodeDependencies()) {
            return false;
        }

        // Step 3: Environment setup
        $this->setupEnvironment();

        // Step 4: Generate app key
        $this->generateAppKey();

        // Step 5: Storage link
        $this->createStorageLink();

        // Step 6: Database migrations
        $this->runMigrations();

        // Step 7: Database seeders
        $this->runSeeders();

        // Step 8: Build assets
        $this->buildAssets();

        // Step 9: Clear caches
        $this->clearCaches();

        // Installation complete
        echo PHP_EOL;
        echo str_repeat('=', 50) . PHP_EOL;
        $this->printStatus("🎉 Installation completed successfully!");
        echo str_repeat('=', 50) . PHP_EOL;
        echo PHP_EOL;

        // Step 10: Start server
        $this->startServer();

        return true;
    }

    private function checkPrerequisites()
    {
        $this->printInfo("Checking prerequisites...");

        // Check PHP
        if (!$this->commandExists('php')) {
            $this->printError("PHP is not installed or not in PATH");
            return false;
        }

        $phpVersion = PHP_VERSION;
        $this->printInfo("PHP Version: $phpVersion");

        // Check Composer
        if (!$this->commandExists('composer')) {
            $this->printError("Composer is not installed or not in PATH");
            echo "Visit: https://getcomposer.org/download/" . PHP_EOL;
            return false;
        }

        $this->printStatus("Prerequisites check passed");
        echo PHP_EOL;
        return true;
    }

    private function installComposerDependencies()
    {
        return $this->execCommand(
            'composer install --no-interaction --prefer-dist --optimize-autoloader',
            'Installing Composer dependencies'
        );
    }

    private function installNodeDependencies()
    {
        // Check for pnpm first, then npm
        if ($this->commandExists('pnpm') && file_exists('pnpm-lock.yaml')) {
            $this->printInfo("Using pnpm for Node.js dependencies...");
            return $this->execCommand('pnpm install', 'Installing pnpm dependencies');
        } elseif ($this->commandExists('npm')) {
            $this->printInfo("Using npm for Node.js dependencies...");
            return $this->execCommand('npm install', 'Installing npm dependencies');
        } else {
            $this->printError("Neither npm nor pnpm is installed. Please install Node.js first.");
            echo "Visit: https://nodejs.org/" . PHP_EOL;
            return false;
        }
    }

    private function setupEnvironment()
    {
        if (!file_exists('.env')) {
            if (file_exists('.env.example')) {
                $this->printInfo("Creating .env file from .env.example...");
                if (copy('.env.example', '.env')) {
                    $this->printStatus(".env file created");
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

    private function generateAppKey()
    {
        $this->execCommand('php artisan key:generate --ansi', 'Generating application key');
        echo PHP_EOL;
    }

    private function createStorageLink()
    {
        if ($this->execCommand('php artisan storage:link', 'Creating storage symbolic link')) {
            // Success handled by execCommand
        } else {
            $this->printWarning("Storage link creation failed (might already exist)");
        }
        echo PHP_EOL;
    }

    private function runMigrations()
    {
        $choice = $this->getUserInput("Do you want to run database migrations? (y/N):", 'n');
        
        if ($choice === 'y' || $choice === 'yes') {
            $this->execCommand('php artisan migrate --force', 'Running database migrations');
        } else {
            $this->printInfo("Skipping database migrations");
        }
        echo PHP_EOL;
    }

    private function runSeeders()
    {
        $choice = $this->getUserInput("Do you want to run database seeders? (y/N):", 'n');
        
        if ($choice === 'y' || $choice === 'yes') {
            $this->execCommand('php artisan db:seed --force', 'Running database seeders');
        } else {
            $this->printInfo("Skipping database seeders");
        }
        echo PHP_EOL;
    }

    private function buildAssets()
    {
        $choice = $this->getUserInput("Do you want to build frontend assets? (y/N):", 'n');
        
        if ($choice === 'y' || $choice === 'yes') {
            if ($this->commandExists('pnpm') && file_exists('pnpm-lock.yaml')) {
                $this->execCommand('pnpm run build', 'Building assets with pnpm');
            } elseif ($this->commandExists('npm')) {
                $this->execCommand('npm run build', 'Building assets with npm');
            }
        } else {
            $this->printInfo("Skipping asset building");
        }
        echo PHP_EOL;
    }

    private function clearCaches()
    {
        $this->printInfo("Clearing application caches...");
        
        $commands = [
            'php artisan cache:clear',
            'php artisan config:clear',
            'php artisan route:clear',
            'php artisan view:clear'
        ];

        foreach ($commands as $command) {
            exec($command);
        }

        $this->printStatus("Caches cleared");
        echo PHP_EOL;
    }

    private function startServer()
    {
        $choice = $this->getUserInput("Do you want to start the development server now? (Y/n):", 'y');
        
        if ($choice === 'n' || $choice === 'no') {
            $this->printInfo("You can start the server later with: php artisan serve");
            $this->printInfo("Default URL: http://localhost:8000");
            return;
        }

        $this->printStatus("Starting Laravel development server...");
        $this->printInfo("Server will be available at: http://localhost:8000");
        $this->printInfo("Press Ctrl+C to stop the server");
        echo PHP_EOL;

        // Open browser (optional)
        $browserChoice = $this->getUserInput("Do you want to open the app in browser? (Y/n):", 'y');
        
        if ($browserChoice !== 'n' && $browserChoice !== 'no') {
            $this->openBrowser('http://localhost:8000');
        }

        // Start server
        echo "Starting server..." . PHP_EOL;
        passthru('php artisan serve');
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
$installer = new LaravelInstaller();
$installer->install();

echo PHP_EOL;
echo "✅ Thank you for using the Laravel installer! 🚀" . PHP_EOL;
