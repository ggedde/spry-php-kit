<?php declare(strict_types=1);
/**
 * This file is to handle the RateLimiter
 */

namespace SpryPhp\Provider;

use SpryPhp\Provider\Functions;
use SpryPhp\Type\TypeRateLimitBy;
use SpryPhp\Type\TypeRateLimitDriver;
use Exception;

/**
 * Class for RateLimiter
 */
class RateLimiter
{
    /**
     * Attempt to allow.
     *
     * @param string          $key
     * @param TypeRateLimitBy $by
     * @param int             $blockLimit
     * @param int             $banLimit
     * @param int             $blockTime
     * @param int             $banTime
     * @param string|callable $blockedResponse
     * @param string|callable $bannedResponse
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function attempt(string $key, TypeRateLimitBy $by, int $blockLimit = 5, int $banLimit = 5, int $blockTime = 20, int $banTime = 3600, string|callable $blockedResponse = 'You have exceeded the rate limit and have been blocked. Try again later!', string|callable $bannedResponse = 'You have exceeded the rate limit too many times and have been banned. Try again later!'): bool
    {
        if ($by->value === 'ip') {
            $ip = Functions::getIp();
            $fullKey = Functions::sanitizeString($key.'-'.str_replace('.', '_', $ip));
        }

        if ($by->value === 'user') {
            $user = Session::getUser();
            if (!empty($user->id) && is_scalar($user->id)) {
                $fullKey = Functions::sanitizeString($key.'-'.strval($user->id));
            } else {
                $userString = json_encode($user);
                if (!$userString) {
                    throw new Exception('SpryPHP Error: RateLimit could not create user Key.');
                }
                $fullKey = Functions::sanitizeString($key.'-'.md5($userString));
            }
        }

        if (empty($fullKey)) {
            throw new Exception('SpryPHP Error: RateLimit could not detect Key.');
        }

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
            if ($data) {
                $data = explode(':', $data);
            }

            $count  = !empty($data[0]) ? intval($data[0]) + 1 : 1;
            $reset  = !empty($data[1]) ? intval($data[1]) : $totalBlockTime;
            $status = !empty($data[2]) && in_array($data[2], ['active', 'blocked', 'banned'], true) ? $data[2] : 'active';

            if ($reset > time()) {
                if ($status === 'banned' || $count > ($blockLimit + $banLimit)) {
                    if ($status !== 'banned') {
                        fseek($fp, 0);
                        ftruncate($fp, 0);
                        fwrite($fp, strval($count.':'.$totalBanTime.':banned'));
                    }
                    fclose($fp);

                    if (is_string($bannedResponse)) {
                        echo $bannedResponse;
                    }

                    if (is_callable($bannedResponse)) {
                        $bannedResponse();
                    }

                    return false;
                }

                if ($status === 'blocked' || $count > $blockLimit) {
                    fseek($fp, 0);
                    ftruncate($fp, 0);
                    fwrite($fp, strval($count.':'.$reset.':blocked'));
                    fclose($fp);

                    if (is_string($blockedResponse)) {
                        echo $blockedResponse;
                    }

                    if (is_callable($blockedResponse)) {
                        $blockedResponse();
                    }

                    return false;
                }
            } else {
                $count = 1;
                $reset = $totalBlockTime;
            }

            fseek($fp, 0);
            ftruncate($fp, 0);
            fwrite($fp, strval($count.':'.$reset.':active'));
            fclose($fp);
        }

        /**
         * DB Driver
         */
        if ($driver === 'db') {
            $result = Db::get(
                table: Functions::constantString('APP_RATELIMIT_DB_TABLE'),
                columns: ['name', 'current', 'reset', 'status'],
                where: [
                    'name' => $fullKey,
                ]
            );

            $mode = $result ? 'update' : 'insert';

            $count  = !empty($result->current) && is_scalar($result->current) ? intval($result->current) + 1 : 1;
            $reset  = !empty($result->reset) && is_scalar($result->reset) ? intval($result->reset) : $totalBlockTime;
            $status = !empty($result->status) && in_array($result->status, ['active', 'blocked', 'banned'], true) ? $result->status : 'active';

            if ($reset > time()) {
                if ($status === 'banned' || $count > ($blockLimit + $banLimit)) {
                    if ($status !== 'banned') {
                        if (!Db::update(Functions::constantString('APP_RATELIMIT_DB_TABLE'), ['current' => $count, 'reset' => $totalBanTime, 'status' => 'banned'], ['name' => $fullKey])) {
                            throw new Exception('SpryPHP Error: RateLimiter could not update Databse. Make sure the system has proper DB configuration and permissions.');
                        }
                    }

                    if (is_string($bannedResponse)) {
                        echo $bannedResponse;
                    }

                    if (is_callable($bannedResponse)) {
                        $bannedResponse();
                    }

                    return false;
                }

                if ($status === 'blocked' || $count > $blockLimit) {
                    if (!Db::update(Functions::constantString('APP_RATELIMIT_DB_TABLE'), ['current' => $count, 'reset' => $reset, 'status' => 'blocked'], ['name' => $fullKey])) {
                        throw new Exception('SpryPHP Error: RateLimiter could not update Databse. Make sure the system has proper DB configuration and permissions.');
                    }

                    if (is_string($blockedResponse)) {
                        echo $blockedResponse;
                    }

                    if (is_callable($blockedResponse)) {
                        $blockedResponse();
                    }

                    return false;
                }
            } else {
                $count = 1;
                $reset = $totalBlockTime;
            }

            if ($mode === 'insert') {
                if (!Db::insert(Functions::constantString('APP_RATELIMIT_DB_TABLE'), ['name' => $fullKey, 'current' => $count, 'reset' => $reset, 'status' => 'active'])) {
                    throw new Exception('SpryPHP Error: RateLimiter could not update Databse. Make sure the system has proper DB configuration and permissions.');
                }
            } else {
                if (!Db::update(Functions::constantString('APP_RATELIMIT_DB_TABLE'), ['current' => $count, 'reset' => $reset, 'status' => 'active'], ['name' => $fullKey])) {
                    throw new Exception('SpryPHP Error: RateLimiter could not update Databse. Make sure the system has proper DB configuration and permissions.');
                }
            }
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
    public static function clearExpired(): bool
    {
        $driver = Functions::constantString('APP_RATELIMIT_DRIVER');

        if (empty($driver) || !TypeRateLimitDriver::tryFrom($driver)) {
            throw new Exception(sprintf('SpryPHP Error: RateLimit Driver must be one of [%s].', implode(',', array_column(TypeRateLimitDriver::cases(), 'value'))));
        }

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

            $files = glob($path.'/*');

            if ($files === false) {
                throw new Exception(sprintf('SpryPHP Error: RateLimit Getting Files Error on path (%s).', $path));
            }

            foreach ($files as $file) {
                $contents = file_get_contents($file);
                $details  = $contents ? explode(':', $contents) : null;
                $reset    = $details && !empty($details[1]) ? intval($details[1]) : 0;
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
}
