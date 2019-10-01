<?php
/**
 * Created by PhpStorm.
 * Date: 2017-12-07
 * Time: 03:12
 */

namespace MP\ExtendedApi;

use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\rest\IndexAction;
use yii\web\BadRequestHttpException;

/**
 * Class    EUpdateAllAction
 * @package MP\ExtendedApi
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class EUpdateAllAction extends IndexAction
{
    /**
     * @var string
     */
    public $filterAttribute = 'filter';

    /**
     * @var string
     */
    public $updatedAttribute = 'updatedAttributes';

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
     * @var array
     */
    private $_updatedModels = [];

    /**
     * Get deleted models
     *
     * @return array
     */
    public function getUpdatedModels(): array
    {
        return $this->_updatedModels;
    }

    /**
     * @inheritdoc
     */
    protected function prepareDataProvider()
    {
        $filter            = Yii::$app->request->get($this->filterAttribute);
        $queryParams       = Yii::$app->request->getQueryParams();
        $updatedAttributes = [];

        if (!empty($filter)) {
            $queryParams[$this->filterAttribute] = json_decode($filter, true);
        }

        if (!empty($queryParams[$this->updatedAttribute])) {
            $updatedAttributes = json_decode($queryParams[$this->updatedAttribute], true);
        }

        if (empty($updatedAttributes)) {
            throw new BadRequestHttpException("Param '{$this->updatedAttribute}' cannot be empty");
        }

        Yii::$app->request->setQueryParams($queryParams);

        $this->prepareDataProvider = function (EIndexAction $action, $filter) use ($extraFilter) {
            /** @var ActiveDataProvider $dataProvider */
            $dataProvider = call_user_func([$action->dataFilter->searchModel, 'getDataProvider']);
            $dataProvider->query->andWhere($filter);

            if ($this->addQuery) {
                call_user_func($this->addQuery, $dataProvider->query, $extraFilter, $action->dataFilter);

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

        $dataProvider = parent::prepareDataProvider();
        /** @var ActiveQuery $query */
        $query = $dataProvider->query;
        $query
            ->limit(-1)
            ->offset(-1)
            ->orderBy([]);

        $countUpdated = 0;

        foreach ($query->each() as $model) {
            /** @var $model ActiveRecord */
            $model->setAttributes($updatedAttributes);

            if ($model->save()) {
                $this->_updatedModels[] = $model;
                $countUpdated++;
            }
        }

        Yii::$app->response->headers->set('X-Total-Updated', $countUpdated);

        return;
    }
}
