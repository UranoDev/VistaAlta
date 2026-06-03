<?php

namespace Tests;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;
use RuntimeException;

abstract class DuskTestCase extends BaseTestCase
{
    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            $edgeDriver = dirname(__DIR__).'/vendor/laravel/dusk/bin/msedgedriver.exe';

            if (! file_exists($edgeDriver)) {
                throw new RuntimeException("Microsoft Edge WebDriver was not found at [{$edgeDriver}].");
            }

            static::useChromedriver($edgeDriver);
            static::startChromeDriver(['--port=9515']);
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $capabilities = DesiredCapabilities::microsoftEdge();

        $capabilities->setCapability('ms:edgeOptions', [
            'args' => [
                '--headless=new',
                '--disable-gpu',
                '--window-size=1920,1080',
            ],
            'binary' => 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
        ]);

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? 'http://localhost:9515',
            $capabilities
        );
    }
}
