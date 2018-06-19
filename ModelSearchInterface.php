<?php
/**
 * Created by PhpStorm.
 * Date: 2018-01-06
 * Time: 00:13
 */

namespace MP\ExtendedApi;

use yii\data\ActiveDataProvider;

/**
 * Interface ModelSearchInterface
 * @package  MP\ExtendedApi
 * @author   Yarmaliuk Mikhail
 * @version  1.0
 */
interface ModelSearchInterface
{
    /**
     * Get data provider
     *
     * @return ActiveDataProvider
     */
    public function getDataProvider(): ActiveDataProvider;
}
