<?php

declare(strict_types=1);

namespace Egm;

final class Composer
{
    public static function json(): object
    {
        static $json;
        isset($json) || $json = json_decode(file_get_contents(dirname(__DIR__) . "/composer.json"));
        return $json;
    }
    public static function install($chMod = 0777): bool
    {
        echo 'EG Composer Installer', PHP_EOL;/*
echo 'v0.1a 2022/04/01 created', PHP_EOL;
echo 'v0.2a 2022/04/10 diferents php version', PHP_EOL;
echo 'v0.3a 2022/04/25 use /tmp', PHP_EOL;
echo 'v0.4a 2023/04/25 add creation of vendorPath.php', PHP_EOL;
echo 'v0.5a 2023/05/26 add version to verify', PHP_EOL;*/
        if ($composer = static::json()) {
            $path = $composer->config->{"vendor-dir"};
            $chMod = 0777;
            echo "valiting directory: $path", PHP_EOL;
            if (empty($path)) {
                echo "It is empty", PHP_EOL;
                return false;
            }
            if (!is_dir($path)) {
                if (!mkdir($path, $chMod, true)) {
                    echo "$path can not be created", PHP_EOL;
                    return false;
                }
            }
            echo "valiting permissions: $chMod", PHP_EOL;
            if (!substr(decoct(fileperms($path)), -3) === decoct($chMod)) {
                if (!chmod($path, $chMod)) {
                    echo "fileperms is different and it can not be assigned", PHP_EOL;
                    return false;
                }
            }
            echo "valiting https in stream_get_wrappers: ", PHP_EOL;
            if (!in_array("https", stream_get_wrappers())) {
                exit("error: https not in stream_get_wrappers(): " . print_r(stream_get_wrappers(), true));
            }
            define("composerInstaller_version", "Version");
            echo 'Downloading composer_sha384: ', $tmp = "https://composer.github.io/installer.sig?" . composerInstaller_version, PHP_EOL;
            define("composer_sha384", file_get_contents($tmp, false, stream_context_create($stream_context = [
                'http' => [
                    //"proxy" => "tcp://192.168.0.21:8080",//todo get from os
                    'follow_location' => 1
                ]
            ])));
            echo ' = ', composer_sha384, PHP_EOL;

            define("getcomposer", "https://getcomposer.org/installer");
            echo 'Downloading installer: ', getcomposer . "?" . composerInstaller_version, PHP_EOL;//https://getcomposer.org/download/latest-stable/composer.phar
            $installer = "composerInstaller" . composerInstaller_version . ".php";
            if (file_exists("{$composer->config->{"vendor-dir"} }/$installer")) {
                $stream_context = array_merge($stream_context, [
                    'http' => [
                        'header' => [
                            'If-Modified-Since: ' . date("r", filemtime("{$composer->config->{"vendor-dir"} }/$installer")) . "\r\n"
                        ]
                    ]
                ]);
                var_dump($stream_context);
            }

            if (!@copy(getcomposer, "{$composer->config->{"vendor-dir"} }/$installer", stream_context_create($stream_context))) {
                var_dump(error_get_last()["message"]);
                exit(var_dump("{$composer->config->{"vendor-dir"} }/$installer", $stream_context, http_response_code()));
            }

            echo "Verifying: $installer", PHP_EOL;
            if (($tmp = hash_file('sha384', "{$composer->config->{"vendor-dir"} }/$installer")) !== composer_sha384) {
                echo $tmp, PHP_EOL,
                    "!==", PHP_EOL,
                    composer_sha384, PHP_EOL,
                    "https://getcomposer.org/download", PHP_EOL;
                return false;
            } else {
                $argv = [
                    "--2.2",//LTS
                    //"--ansi", //required becouse sapi_windows_vt100_support(STDOUT) return false
                    "--install-dir={$composer->config->{"vendor-dir"} }",
                    "--filename=composer" . PHP_VERSION . ".phar"
                ];
                echo "Requiring: $installer", PHP_EOL;
                require "{$composer->config->{"vendor-dir"} }/$installer";
            }
            echo "Deleting: {$composer->config->{"vendor-dir"} }/$installer", PHP_EOL;
            unlink("{$composer->config->{"vendor-dir"} }/$installer");
        }
        return true;
    }
    /**
     * @return array<string, string>
     */
    public static function dB(): array
    {
        /* todo better with array ?
        var_dump(array_keys((array) static::json()->require));
         */
        $db = [];
        foreach (static::json()->require as $k => $v) {
            if (strpos($k, "yiisoft/db-") === 0) {// !== false
                $db = [$k => $v];
            }
        }
        return $db;
    }
    /**
     * Relative return is necesary
     * @return string
     * @throws \Exception
     */
    public static function vendorDir(): string
    {
        $vendorDir = static::json()->config->{"vendor-dir"};//yii3 require relative path
        if (\in_array(PHP_SAPI, ["apache2handler", "fpm-fcgi"])) {//cli cli-server apache2handler fpm-fcgi
            $vendorDir = "../$vendorDir";
        }
        return $vendorDir;
    }
}
\getenv("APP_ENV") || \putenv("APP_ENV=dev");
return Composer::vendorDir();