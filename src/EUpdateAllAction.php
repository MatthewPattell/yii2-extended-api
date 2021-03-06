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
    public string $filterAttribute = 'filter';

    /**
     * @var string
     */
    public string $extraFilter = 'extraFilter';

    /**
     * @var string
     */
    public string $updatedAttribute = 'updatedAttributes';

    /**
     * Add custom query condition
     * @see \Closure params
     *
     * @var null|array
     */
    public ?array $addQuery = null;

    /**
     * Column name
     * @see \Closure
     *
     * @var null|array
     */
    public ?array $filterUser = null;

    /**
     * @var array
     */
    private array $_updatedModels = [];

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
        $extraFilter       = Yii::$app->request->get($this->extraFilter);
        $queryParams       = Yii::$app->request->getQueryParams();
        $updatedAttributes = [];

        if (!empty($filter)) {
            $queryParams[$this->filterAttribute] = json_decode($filter, true);
        }

        if (!empty($extraFilter) && is_string($extraFilter)) {
            $extraFilter = json_decode($extraFilter, true);
        }

        if (!empty($queryParams[$this->updatedAttribute])) {
            $updatedAttributes = json_decode($queryParams[$this->updatedAttribute], true);
        }

        if (empty($updatedAttributes)) {
            throw new BadRequestHttpException("Param '{$this->updatedAttribute}' cannot be empty");
        }

        Yii::$app->request->setQueryParams($queryParams);

        $this->prepareDataProvider = function (EUpdateAllAction $action, $filter) use ($extraFilter) {
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
                $filterUserColumn = call_user_func($this->filterUser);

                if ($filterUserColumn !== null) {
                    $dataProvider->query->andWhere([$filterUserColumn => Yii::$app->user->getId()]);
                }
            }

            return $dataProvider;
        };

        $dataProvider = parent::prepareDataProvider();
        $countUpdated = 0;

        if ($dataProvider instanceof ActiveDataProvider) {
            /** @var ActiveQuery $query */
            $query = $dataProvider->query;
            $query
                ->limit(-1)
                ->offset(-1)
                ->orderBy([]);

            foreach ($query->each() as $model) {
                /** @var $model ActiveRecord */
                $model->setAttributes($updatedAttributes);

                if ($model->save()) {
                    $this->_updatedModels[] = $model;
                    $countUpdated++;
                }
            }
        }

        Yii::$app->response->headers->set('X-Total-Updated', $countUpdated);

        return $dataProvider;
    }
}
