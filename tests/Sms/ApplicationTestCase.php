<?php

declare(strict_types=1);

namespace Tests\Sms;

use EzPhp\Testing\ApplicationTestCase as EzPhpApplicationTestCase;
use RuntimeException;

/**
 * Base class for sms module tests that need a bootstrapped Application.
 *
 * Creates a temporary application root containing a config/sms.php file
 * that reads from env vars at require-time. Because Config and all service
 * bindings are resolved lazily, env vars set in a test method before the
 * first make() call are picked up correctly.
 *
 * @package Tests\Sms
 */
abstract class ApplicationTestCase extends EzPhpApplicationTestCase
{
    /**
     * @return string
     */
    protected function getBasePath(): string
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ez-sms-test-' . uniqid('', true);
        $configDir = $path . DIRECTORY_SEPARATOR . 'config';

        mkdir($configDir, 0o777, true);

        $content = <<<'PHP'
            <?php

            declare(strict_types=1);

            return [
                'driver' => getenv('SMS_DRIVER') ?: 'null',
                'from' => getenv('SMS_FROM') ?: '',
                'log_path' => getenv('SMS_LOG_PATH') ?: '',
                'twilio' => [
                    'sid' => getenv('SMS_TWILIO_SID') ?: '',
                    'token' => getenv('SMS_TWILIO_TOKEN') ?: '',
                ],
                'vonage' => [
                    'key' => getenv('SMS_VONAGE_KEY') ?: '',
                    'secret' => getenv('SMS_VONAGE_SECRET') ?: '',
                ],
            ];
            PHP;

        $result = file_put_contents($configDir . DIRECTORY_SEPARATOR . 'sms.php', $content);

        if ($result === false) {
            throw new RuntimeException('Failed to write sms.php for test at ' . $configDir);
        }

        return $path;
    }
}
