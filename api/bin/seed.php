<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Seeders\DayHabitSeeder;
use App\Infrastructure\Persistence\Seeders\DaySeeder;
use App\Infrastructure\Persistence\Seeders\HabitSeeder;
use DI\ContainerBuilder;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

// Load .env file
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// Instantiate PHP-DI ContainerBuilder
$containerBuilder = new ContainerBuilder();

// Add settings to the container builder
$settings = require __DIR__ . '/../config/settings.php';
$containerBuilder->addDefinitions($settings);

// Add dependencies to the container builder
$dependencies = require __DIR__ . '/../config/container.php';
$containerBuilder->addDefinitions($dependencies);

// Build PHP-DI Container instance
$container = $containerBuilder->build();

// Get the PDO instance
$pdo = $container->get(PDO::class);

echo "Starting seeding process...\n";

// 1. Run the DaySeeder (Ensures dates exist)
$daySeeder = new DaySeeder($pdo);
$daySeeder->run();

// 2. Run the HabitSeeder (Creates specific habits for user 1)
$habitSeeder = new HabitSeeder(
    $pdo,
    $container->get(App\Domain\Repository\UserRepositoryInterface::class),
    $container->get(App\Domain\Repository\HabitRepositoryInterface::class)
);
$habitSeeder->run();

// 3. Run the DayHabitSeeder (Creates completion markings)
$dayHabitSeeder = new DayHabitSeeder($pdo);
$dayHabitSeeder->run();

echo "All seeders executed successfully!\n";
