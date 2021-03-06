<?php

namespace rjapitest\unit;

use Illuminate\Foundation\Testing\TestCase as TestCaseLaravel;
use rjapi\RJApiGenerator;
use rjapi\types\ConfigInterface;
use rjapi\types\DirsInterface;
use rjapi\types\JwtInterface;
use rjapi\types\PhpInterface;

abstract class TestCase extends TestCaseLaravel
{
    public const CONFIG_KEY  = 'v2';
    public const MODULE_NAME = 'V2';
    public const DIR_OUTPUT  = './tests/_output/';

    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $app->make('config');
        return $app;
    }

    public function createConfig()
    {
        $gen             = new RJApiGenerator();
        $gen->modulesDir = DirsInterface::MODULES_DIR;
        $gen->version    = self::MODULE_NAME;
        $confFile        = $gen->formatConfigPath() . 'config.php';
        // mocking config for further usage
        $arr = include $confFile;
        // to get jwt not expired for verifying in JwtTest
        $arr[JwtInterface::JWT][ConfigInterface::ACTIVATE] = 0;
        // custom sql for CustomSqlTest
        $arr['custom_sql'] = [
            'article' => [
                'enabled'  => true,
                'query'    => 'SELECT a.id, a.title FROM article a INNER JOIN tag_article ta ON ta.article_id=a.id 
                          WHERE ta.tag_id IN (
                          SELECT id FROM tag WHERE CHAR_LENGTH(title) > :tag_len
                          ) ORDER BY a.id DESC',
                'bindings' => [
                    'tag_len' => 5,
                ]
            ],
        ];
        $str               = PhpInterface::PHP_OPEN_TAG . PhpInterface::SPACE . 'return' . PhpInterface::SPACE . var_export($arr, true) . ';';
        $fp                = fopen($confFile, 'r+');
        fwrite($fp, $str);
        fclose($fp);
    }
}