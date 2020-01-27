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
     * @var string
     */
    public $extraFilter = 'extraFilter';

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
     * Get filter params
     *
     * @return array
     */
    public function getFilterParams(): array
    {
        $filterParams = Yii::$app->request->getQueryParams()[$this->filterAttribute] ?? [];

        if (is_array($filterParams)) {
            return $filterParams;
        } else if (!empty($filterParams) && is_string($filterParams)) {
            return json_decode($filterParams, true);
        }

        return [];
    }

    /**
     * @inheritdoc
     */
    protected function prepareDataProvider()
    {
        $filter      = Yii::$app->request->get($this->filterAttribute);
        $extraFilter = Yii::$app->request->get($this->extraFilter);

        if (!empty($filter) && is_string($filter)) {
            $this->setFilterParams(json_decode($filter, true));
        }

        if (!empty($extraFilter) && is_string($extraFilter)) {
            $extraFilter = json_decode($extraFilter, true);
        }

        $this->prepareDataProvider = function (EIndexAction $action, $filter) use ($extraFilter) {
            /** @var ActiveDataProvider $dataProvider */
            $dataProvider = call_user_func([$action->dataFilter->searchModel, 'getDataProvider']);
            $dataProvider->query->andWhere($filter);

            if ($this->addQuery) {
                call_user_func($this->addQuery, $dataProvider->query, $extraFilter, $action->dataFilter, $dataProvider);

                if ($action->dataFilter->hasErrors()) {
                    return $action->dataFilter;
                }
            }

            if ($this->filterUser) {
                $filterUserColumn = is_callable($this->filterUser) ? call_user_func($this->filterUser) : $this->filterUser;

                if ($filterUserColumn !== null) {
                    $dataProvider->query->andWhere([$filterUserColumn => Yii::$app->user->getId()]);
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
}
