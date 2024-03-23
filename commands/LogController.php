<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\BaseConsole as Console;
use app\components\NginxLogParser;
use ZipArchive;
use UAParser\Parser;
use app\models\Log;

/**
 * Import nginx log file to database
 */
class LogController extends Controller
{

    /**
     * @var string Show errors in console. "yes" or "no"
     */
    public string $showErrors = 'no';
    /**
     * @var string Log errors. yes" or "no"
     */
    public string $logErrors = 'no';

    private string $errorMessage = '';

    private string $tmpDir = '';

    public function __construct($id, $module, $config = [])
    {
        $this->tmpDir = Yii::getAlias('@app/runtime/tmp');

        parent::__construct($id, $module, $config);
    }

    /**
     * Command to import nginx log file to database
     *
     * @param string|null $filePath Path to log file
     * @return int
     * @throws \Exception
     */
    public function actionImport(string $filePath = null): int
    {
        if (empty($filePath)) {
            return $this->exitError('Please, specify path to the log file');
        }

        if (!file_exists($filePath)) {
            return $this->exitError('Log file "' . $filePath . '" not found');
        }

        $allowedExtensions = ['txt', 'log', 'zip'];

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        if (!in_array($extension, $allowedExtensions)) {
            return $this->exitError('Wrong file extension. Supported only .txt, .log and .zip files.');
        }

        if ($extension === 'zip') {
            $files = $this->unzip($filePath);

            if (!$files && !empty($errorMessage)) {
                $this->exitError($errorMessage);
            }
        } else {
            $files = [$filePath];
        }

        $parse = new NginxLogParser();
        $parser = Parser::create();

        $dailyUrl = [];
        $dailyBrowser = [];

        foreach ($files as $file) {
            $handle = fopen($file, 'r');

            if ($handle) {

                $i = 0;

                $data = [];

                while (($line = fgets($handle)) !== false) {
                    try {
                        $entry = $parse->parse($line);
                    } catch (\Exception $e) {
                        $this->error($e);
                        continue;
                    }

                    try {
                        $result = $parser->parse($entry->http_user_agent);

                        $browser = null;
                        $os = null;

                        if ($result->device->family !== 'Spider') {
                            $browser = $result->ua->family;
                            $os = $result->os->family;

                            if (!empty($result->os->major)) {
                                $os .= ' ' . $result->os->major;
                            }
                        }

                        $architecture = null;

                        if (str_contains($line, 'x86')) {
                            $architecture = 'x86';
                        }

                        if (str_contains($line, 'x64') ||
                            str_contains($line, 'Win64') ||
                            str_contains($line, 'x86_64')) {
                            $architecture = 'x64';
                        }

                        $url = explode(' ', $entry->request)[1];

                        $timestamp = strtotime($entry->time_local);
                        $date = date('Y-m-d', $timestamp);
                        $time = date('H:i:s', $timestamp);
                        $urlHash = md5($url);

                        $data[] = [
                            'ip' => $entry->ip,
                            'url' => $url,
                            'urlHash' => $urlHash,
                            'userAgent' => $entry->http_user_agent,
                            'os' => $os,
                            'arch' => $architecture,
                            'browser' => $browser,
                            'date' => $date,
                            'time' => $time
                        ];

                        if (!isset($dailyUrl[$date][$urlHash])) {
                            $dailyUrl[$date][$urlHash] = 1;
                        } else {
                            $dailyUrl[$date][$urlHash]++;
                        }

                        if (!isset($dailyBrowser[$date][$browser])) {
                            $dailyBrowser[$date][$browser] = 1;
                        } else {
                            $dailyBrowser[$date][$browser]++;
                        }

                        $i++;

                        if ($i === 100) {
                            Yii::$app->db->createCommand()
                                ->batchInsert('log', ['ip', 'url', 'urlHash', 'userAgent', 'os', 'arch', 'browser', 'date', 'time'], $data)
                                ->execute();

                            $i = 0;
                            $data = [];
                        }
                    } catch (\Exception $e) {
                        $this->error($e);
                    }
                }

                fclose($handle);
            } else {
                return $this->exitError('Cannot read file "' . $file . '"');
            }
        }

        $dailyUrlData = [];

        foreach ($dailyUrl as $date => $data) {
            foreach ($data as $urlHash => $total) {
                $dailyUrlData[] = [
                    'date'    => $date,
                    'urlHash' => $urlHash,
                    'total'   => $total
                ];
            }
        }

        Yii::$app->db->createCommand()
            ->batchInsert('log_daily_url', ['date', 'urlHash', 'total'], $dailyUrlData)
            ->execute();

        $dailyBrowserData = [];

        foreach ($dailyBrowser as $date => $data) {
            foreach ($data as $browser => $total) {
                $dailyBrowserData[] = [
                    'date'    => $date,
                    'browser' => $browser,
                    'total'   => $total
                ];
            }
        }

        Yii::$app->db->createCommand()
            ->batchInsert('log_daily_browser', ['date', 'browser', 'total'], $dailyBrowserData)
            ->execute();

        $this->clearDir($this->tmpDir);

        return ExitCode::OK;
    }

    public function options($actionID): array
    {
        if ($actionID === 'import') {
            return ['showErrors', 'logErrors'];
        }

        return [];
    }

    private function unzip(string $zipFile): array|false
    {
        $files = [];

        if (!is_dir($this->tmpDir)) {
            if (mkdir($this->tmpDir, 755)) {
                $this->errorMessage = 'Cannot create temporary directory';
                return false;
            }
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFile) === true) {

            if (!is_dir($this->tmpDir)) {
                mkdir($this->tmpDir, 0755, true);
            }

            $zip->extractTo($this->tmpDir);
            $zip->close();

            $extractedFiles = scandir($this->tmpDir);

            foreach ($extractedFiles as $file) {
                if ($file !== '.' && $file !== '..') {
                    $files[] = $this->tmpDir . '/' . $file;
                }
            }
        } else {
            $this->errorMessage = 'Unable to open zip file';
            return false;
        }

        return $files;
    }

    private function clearDir(string $dir): void
    {
        $files = scandir($dir);

        foreach ($files as $file) {
            $filePath = $dir . '/' . $file;

            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
    }

    private function error(string $errorMessage): void
    {
        if ($this->showErrors === 'yes') {
            $this->stdout($errorMessage . PHP_EOL, Console::FG_RED);
        }

        if ($this->logErrors === 'yes') {
            Yii::warning($errorMessage);
        }
    }

    private function exitError(string $errorMessage): int
    {
        $this->error($errorMessage);

        return ExitCode::UNSPECIFIED_ERROR;
    }

}