<?php
/**
 * Created by PhpStorm.
 * Date: 2017-12-07
 * Time: 03:12
 */

namespace MP\ExtendedApi;

use Yii;
use yii\base\Event;
use yii\data\ActiveDataProvider;
use yii\rest\IndexAction;

/**
 * Class    EIndexAction
 * @package MP\ExtendedApi
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class EIndexAction extends IndexAction
{
    const EVENT_AFTER_PREPARE_DATAP_ROVIDER = 'afterPrepareDataProvider';

    /**
     * @var string
     */
    public $filterAttribute = 'filter';

    /**
     * Add custom query condition
     *
     * @var null|\Closure
     */
    public $addQuery = null;

    /**
     * Column name
     *
     * @var null|string
     */
    public $filterUser = null;

    /**
     * @inheritdoc
     */
    protected function prepareDataProvider()
    {
        $filter = Yii::$app->request->get($this->filterAttribute);

        if (!empty($filter) && is_string($filter)) {
            $this->setFilterParams(json_decode($filter, true));
        }

        $this->prepareDataProvider = function (EIndexAction $action, $filter) {
            /** @var ActiveDataProvider $dataProvider */
            $dataProvider = call_user_func([$action->dataFilter->searchModel, 'getDataProvider']);
            $dataProvider->query->andWhere($filter);

            if ($this->addQuery) {
                call_user_func($this->addQuery, $dataProvider->query);
            }

            if ($this->filterUser) {
                $filterUserValue = is_callable($this->filterUser) ? call_user_func($this->filterUser) : $this->filterUser;

                if ($filterUserValue !== null) {
                    $dataProvider->query->andWhere([$this->filterUser => $filterUserValue]);
                }
            }

            return $dataProvider;
        };

        $this->trigger(self::EVENT_AFTER_PREPARE_DATAP_ROVIDER, new Event());

        return parent::prepareDataProvider();
    }

    /**
     * Set filter params
     *
     * @param array $filterParams
     *
     * @return void
     */
    public function setFilterParams(array $filterParams): void
    {
        $queryParams = Yii::$app->request->getQueryParams();

        $queryParams[$this->filterAttribute] = $filterParams;

        Yii::$app->request->setQueryParams($queryParams);
    }

    /**
     * Get filter params
     *
     * @return array
     */
    public function getFilterParams(): array
    {
        $filterParams = Yii::$app->request->getQueryParams()[$this->filterAttribute] ?? [];

        if (is_array($filterParams)) {
            return $filterParams;
        } elseif (!empty($filterParams) && is_string($filterParams)) {
            return json_decode($filterParams, true);
        }

        return [];
    }
}
