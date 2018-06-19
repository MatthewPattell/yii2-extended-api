<?php
/**
 * Created by PhpStorm.
 * User: Yarmaliuk Mikhail
 * Date: 19.06.18
 * Time: 23:27
 */

error_reporting(E_ALL);

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENABLE_ERROR_HANDLER') or define('YII_ENABLE_ERROR_HANDLER', false);
defined('YII_ENV') or define('YII_ENV', 'test');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

\Yii::setAlias('@tests', __DIR__);
\Yii::setAlias('@vendor', '/../vendor');
\Yii::setAlias('@data', __DIR__ . DIRECTORY_SEPARATOR . '_data');
