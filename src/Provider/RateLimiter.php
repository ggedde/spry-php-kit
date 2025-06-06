<?php declare(strict_types=1);
/**
 * This file is to handle the RateLimiter
 */

namespace SpryPhp\Provider;

use SpryPhp\Provider\Functions;
use SpryPhp\Type\TypeRateLimitBy;
use SpryPhp\Type\TypeStorage;
use SpryPhp\Model\RateLimitModel;
use SpryPhp\Type\TypeRateLimitStatus;
use Exception;

/**
 * Class for RateLimiter
 */
class RateLimiter
{
    /**
     * Key used for Banning
     */
    private const string BANNED_KEY = '__BANNED__'; // phpcs:ignore

    /**
     * Key used for Banning
     *
     * @var string[] $excludeIps
     */
    private static array $excludeIps = ['127.0.0.1', '::1']; // phpcs:ignore

    /**
     * Attempt to allow.
     *
     * @param string                                                              $key             Unique Key of Resource.
     * @param TypeRateLimitBy                                                     $by              Limit by IP or User.
     * @param int                                                                 $blockLimit      Limit of attempts within the $blockTime before Requestor is blocked from the resource.
     * @param int                                                                 $banLimit        Limit passed the blockLimit which to consider the Requester completely banned from the application.
     * @param int                                                                 $blockTime       Time allowed for requests before they get blocked based on $blockLimit.
     * @param int                                                                 $banTime         Time to ban the Requestor from the application. This applies acrossed all RateLimit attemtps.
     * @param string|callable(object{attempts:int,remaining:int,reset:int}): void $blockedResponse Use a Callable to use your own Callback or String to echo. Callbacks get the Limit details returned as a single object.
     * @param string|callable(object{attempts:int,remaining:int,reset:int}): void $bannedResponse  Use a Callable to use your own Callback or String to echo. Callbacks get the Limit details returned as a single object.
     *
     * @throws Exception
     *
     * @return object{attempts:int,remaining:int,reset:int}|null
     */
    public static function attempt(string $key, TypeRateLimitBy $by, int $blockLimit = 5, int $banLimit = 5, int $blockTime = 20, int $banTime = 3600, string|callable $blockedResponse = 'You have exceeded the rate limit and have been blocked. Try again later!', string|callable $bannedResponse = 'You have exceeded the rate limit too many times and have been banned. Try again later!'): ?object
    {
        $ip = Functions::getIp();
        $user = Session::getUser();

        $bannedDetails = (object) [
            'attempts'  => 0,
            'remaining' => 0,
            'reset'     => time() + $banTime,
        ];

        if (in_array($ip, self::$excludeIps, true)) {
            return $bannedDetails;
        }

        $idKey = '';
        if ($by->value === 'ip') {
            $idKey = Functions::sanitizeString(str_replace('.', '_', $ip));
        }

        if ($by->value === 'user') {
            if (!empty($user->id) && is_scalar($user->id)) {
                $idKey = Functions::sanitizeString(strval($user->id));
            } else {
                $userString = json_encode($user);
                if (!$userString) {
                    throw new Exception('SpryPHP Error: RateLimit could not create user Key.');
                }
                $idKey = md5($userString);
            }
        }

        if (empty($idKey)) {
            throw new Exception('SpryPHP Error: RateLimit could not detect Key.');
        }

        $bannedReset = self::getBannedReset($idKey);
        if ($bannedReset > 0) {
            $bannedDetails->reset = $bannedReset;
            if (is_string($bannedResponse)) {
                echo $bannedResponse;
            }

            if (is_callable($bannedResponse)) {
                $bannedResponse($bannedDetails);
            }

            return null;
        }

        $fullKey = Functions::sanitizeString($key).':'.$idKey;

        $driver = Functions::constantString('APP_RATELIMIT_DRIVER');

        $totalBlockTime = time() + $blockTime;
        $totalBanTime = time() + $banTime;

        /**
         * File Driver
         */
        if ($driver === 'file') {
            $path = Functions::constantString('APP_RATELIMIT_FILE_PATH');

            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }

            if (!is_dir($path)) {
                throw new Exception(sprintf('SpryPHP Error: RateLimit File Path does not exist and could not be created (%s).', $path));
            }

            $file = $path.'/'.$fullKey;
            $fp = fopen($file, 'c+');
            if (!$fp) {
                throw new Exception(sprintf('SpryPHP Error: RateLimit File does not exist and could not be created (%s).', $file));
            }

            $data = fgets($fp);
            $rateLimitDetails = new RateLimitModel($data ? $data : null);
            $rateLimitDetails->name = $fullKey;
            $rateLimitDetails->attempts++;

            $details = (object) [
                'attempts'  => $rateLimitDetails->attempts,
                'remaining' => $blockLimit - $rateLimitDetails->attempts,
                'reset'     => $rateLimitDetails->reset,
            ];

            if ($rateLimitDetails->reset > time()) {
                if ($rateLimitDetails->attempts > ($blockLimit + $banLimit)) {
                    if ($rateLimitDetails->status !== TypeRateLimitStatus::Banned) {
                        $rateLimitDetails->status = TypeRateLimitStatus::Banned;
                        $rateLimitDetails->reset = $totalBanTime;

                        // Add Separate Banned File
                        file_put_contents($path.'/'.str_replace(':', ':'.self::BANNED_KEY.':', $fullKey), json_encode($rateLimitDetails));
                    }
                }

                if ($rateLimitDetails->status === TypeRateLimitStatus::Banned) {
                    fclose($fp);

                    if (is_string($bannedResponse)) {
                        echo $bannedResponse;
                    }

                    if (is_callable($bannedResponse)) {
                        $bannedResponse($bannedDetails);
                    }

                    return null;
                }

                if ($rateLimitDetails->attempts > $blockLimit) {
                    $rateLimitDetails->status = TypeRateLimitStatus::Blocked;
                }

                if ($rateLimitDetails->status === TypeRateLimitStatus::Blocked) {
                     $jsonContents = json_encode($rateLimitDetails);
                    if ($jsonContents) {
                        fseek($fp, 0);
                        ftruncate($fp, 0);
                        fwrite($fp, $jsonContents);
                    }
                    fclose($fp);

                    if (is_string($blockedResponse)) {
                        echo $blockedResponse;
                    }

                    if (is_callable($blockedResponse)) {
                        $blockedResponse($details);
                    }

                    return null;
                }
            } else {
                $rateLimitDetails->attempts = 1;
                $rateLimitDetails->reset = $totalBlockTime;
            }

            $jsonContents = json_encode($rateLimitDetails);
            if ($jsonContents) {
                fseek($fp, 0);
                ftruncate($fp, 0);
                fwrite($fp, $jsonContents);
            }
            fclose($fp);
        }

        /**
         * DB Driver
         */
        if ($driver === 'db') {
            $result = Db::get(
                table: Functions::constantString('APP_RATELIMIT_DB_TABLE'),
                columns: ['name', 'attempts', 'reset', 'status'],
                where: [
                    'name' => $fullKey,
                ]
            );

            $rateLimitDetails = new RateLimitModel($result ? $result : null);
            $rateLimitDetails->name = $fullKey;
            $rateLimitDetails->attempts++;

            $mode = $result ? 'update' : 'insert';

            $details = (object) [
                'attempts'  => $rateLimitDetails->attempts,
                'remaining' => $blockLimit - $rateLimitDetails->attempts,
                'reset'     => $rateLimitDetails->reset,
            ];

            if ($rateLimitDetails->reset > time()) {
                if ($rateLimitDetails->status === TypeRateLimitStatus::Banned || $rateLimitDetails->attempts > ($blockLimit + $banLimit)) {
                    if ($rateLimitDetails->status !== TypeRateLimitStatus::Banned) {
                        if (!Db::update(Functions::constantString('APP_RATELIMIT_DB_TABLE'), ['attempts' => $rateLimitDetails->attempts, 'reset' => $totalBanTime, 'status' => 'banned'], ['name' => $fullKey])) {
                            throw new Exception('SpryPHP Error: RateLimiter could not update Databse. Make sure the system has proper DB configuration and permissions.');
                        }
                    }

                    if (is_string($bannedResponse)) {
                        echo $bannedResponse;
                    }

                    if (is_callable($bannedResponse)) {
                        $bannedResponse($bannedDetails);
                    }

                    return null;
                }

                if ($rateLimitDetails->status === TypeRateLimitStatus::Blocked || $rateLimitDetails->attempts > $blockLimit) {
                    if (!Db::update(Functions::constantString('APP_RATELIMIT_DB_TABLE'), ['attempts' => $rateLimitDetails->attempts, 'reset' => $rateLimitDetails->reset, 'status' => 'blocked'], ['name' => $fullKey])) {
                        throw new Exception('SpryPHP Error: RateLimiter could not update Databse. Make sure the system has proper DB configuration and permissions.');
                    }

                    if (is_string($blockedResponse)) {
                        echo $blockedResponse;
                    }

                    if (is_callable($blockedResponse)) {
                        $blockedResponse($details);
                    }

                    return null;
                }
            } else {
                $rateLimitDetails->attempts = 1;
                $rateLimitDetails->reset = $totalBlockTime;
            }

            if ($mode === 'insert') {
                if (!Db::insert(Functions::constantString('APP_RATELIMIT_DB_TABLE'), [
                    'name'     => $rateLimitDetails->name,
                    'path'     => $rateLimitDetails->path,
                    'user_id'  => $rateLimitDetails->userId,
                    'method'   => $rateLimitDetails->method,
                    'ip'       => $rateLimitDetails->ip,
                    'attempts' => $rateLimitDetails->attempts,
                    'reset'    => $rateLimitDetails->reset,
                    'status'   => 'active',
                ])) {
                    throw new Exception('SpryPHP Error: RateLimiter could not update Databse. Make sure the system has proper DB configuration and permissions.');
                }
            } else {
                if (!Db::update(Functions::constantString('APP_RATELIMIT_DB_TABLE'), [
                    'attempts' => $rateLimitDetails->attempts,
                    'reset'    => $rateLimitDetails->reset,
                    'status'   => 'active',
                ], ['name' => $fullKey])) {
                    throw new Exception('SpryPHP Error: RateLimiter could not update Databse. Make sure the system has proper DB configuration and permissions.');
                }
            }
        }

        return !empty($details) ? $details : null;
    }

    /**
     * Clear Expired
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function clearExpired(): bool
    {
        $driver = Functions::constantString('APP_RATELIMIT_DRIVER');

        if (empty($driver) || !TypeStorage::tryFrom($driver)) {
            throw new Exception(sprintf('SpryPHP Error: RateLimit Driver must be one of [%s].', implode(',', array_column(TypeStorage::cases(), 'value'))));
        }

        /**
         * File Driver
         */
        if ($driver === 'file') {
            $path = Functions::constantString('APP_RATELIMIT_FILE_PATH');

            if (!is_dir($path)) {
                return true;
            }

            $files = glob($path.'/*');

            if ($files === false) {
                throw new Exception(sprintf('SpryPHP Error: RateLimit Getting Files Error on path (%s/*).', $path));
            }

            foreach ($files as $file) {
                $contents = file_get_contents($file);
                $jsonData = $contents ? json_decode($contents) : null;
                $reset = is_object($jsonData) && isset($jsonData->reset) && is_scalar($jsonData->reset) ? intval($jsonData->reset) : 0;

                if ($reset < time()) {
                    unlink($file);
                }
            }
        }

        /**
         * DB Driver
         */
        if ($driver === 'db') {
            Db::delete(
                table: Functions::constantString('APP_RATELIMIT_DB_TABLE'),
                where: ['reset[<]' => time()] // @phpstan-ignore argument.type
            );
        }

        return true;
    }

    /**
     * Clear Expired
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function clearAll(): bool
    {
        $driver = Functions::constantString('APP_RATELIMIT_DRIVER');

        if (empty($driver) || !TypeStorage::tryFrom($driver)) {
            throw new Exception(sprintf('SpryPHP Error: RateLimit Driver must be one of [%s].', implode(',', array_column(TypeStorage::cases(), 'value'))));
        }

        /**
         * File Driver
         */
        if ($driver === 'file') {
            $path = Functions::constantString('APP_RATELIMIT_FILE_PATH');

            if (!is_dir($path)) {
                return true;
            }

            $files = glob($path.'/*');

            if ($files === false) {
                throw new Exception(sprintf('SpryPHP Error: RateLimit Getting Files Error on path (%s/*).', $path));
            }

            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        /**
         * DB Driver
         */
        if ($driver === 'db') {
            Db::truncate(Functions::constantString('APP_RATELIMIT_DB_TABLE'));
        }

        return true;
    }

    /**
     * Set which IPS to Exclude. Default is '127.0.0.1' and '::1'
     *
     * @param string[] $excludeIps
     *
     * @return void
     */
    public static function setExcludeIps(array $excludeIps = ['127.0.0.1', '::1']): void
    {
        self::$excludeIps = $excludeIps;
    }

    /**
     * Check if is Banned
     *
     * @param string $idKey
     *
     * @throws Exception
     *
     * @return int
     */
    private static function getBannedReset(string $idKey): int
    {
        if (empty($idKey)) {
            throw new Exception('SpryPHP Error: RateLimit could not detect Key.');
        }

        $fullKey = self::BANNED_KEY.':'.$idKey;

        $driver = Functions::constantString('APP_RATELIMIT_DRIVER');

        /**
         * File Driver
         */
        if ($driver === 'file') {
            $path = Functions::constantString('APP_RATELIMIT_FILE_PATH');
            $file = $path.'/'.$fullKey;

            $bannedFiles = glob($path.'/*:'.self::BANNED_KEY.':'.$idKey);

            if (!$bannedFiles) {
                return 0;
            }
            $longestBan = 0;
            foreach ($bannedFiles as $file) {
                $contents = file_get_contents($file);
                $rateLimitDetails = new RateLimitModel($contents ? $contents : null);
                if ($rateLimitDetails->reset > $longestBan) {
                    $longestBan = $rateLimitDetails->reset;
                }
            }

            return $longestBan > time() ? $longestBan : 0;
        }

        /**
         * DB Driver
         */
        if ($driver === 'db') {
            $result = Db::get(
                table: Functions::constantString('APP_RATELIMIT_DB_TABLE'),
                columns: ['reset'],
                where: [
                    'name' => $fullKey,
                    'status' => 'banned',
                ]
            );

            if (isset($result->reset) && is_scalar($result->reset)) {
                return intval($result->reset);
            }
        }

        return 0;
    }
}
