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
use yii\web\NotFoundHttpException;

/**
 * Class    EDeleteAllAction
 * @package MP\ExtendedApi
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class EDeleteAllAction extends IndexAction
{
    /**
     * @var string
     */
    public string $filterAttribute = 'filter';

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
     * Delete all without condition
     *
     * @var bool
     */
    public bool $hardDelete = false;

    /**
     * @var array
     */
    private array $_deletedModels = [];

    /**
     * Get deleted models
     *
     * @return array
     */
    public function getDeletedModels(): array
    {
        return $this->_deletedModels;
    }

    /**
     * @inheritdoc
     */
    protected function prepareDataProvider()
    {
        $filter      = Yii::$app->request->get($this->filterAttribute);
        $queryParams = Yii::$app->request->getQueryParams();

        if (!empty($filter)) {
            $queryParams[$this->filterAttribute] = json_decode($filter, true);
        }

        if (!$this->hardDelete && (empty($queryParams[$this->filterAttribute]) || empty(array_filter($queryParams[$this->filterAttribute])))) {
            throw new NotFoundHttpException("Param '{$this->filterAttribute}' cannot be empty");
        }

        Yii::$app->request->setQueryParams($queryParams);

        $this->prepareDataProvider = function (EDeleteAllAction $action, $filter) {
            /** @var ActiveDataProvider $dataProvider */
            $dataProvider = call_user_func([$action->dataFilter->searchModel, 'getDataProvider']);
            $dataProvider->query->andWhere($filter);

            if ($this->addQuery) {
                call_user_func($this->addQuery, $dataProvider->query, null, $action->dataFilter, $dataProvider);
            }

            if ($this->filterUser) {
                $filterUserColumn = call_user_func($this->filterUser);

                if ($filterUserColumn !== null) {
                    $dataProvider->query->andWhere([$filterUserColumn => Yii::$app->user->getId()]);
                }
            }

            return $dataProvider;
        };

        if ($this->hardDelete) {
            $this->modelClass::deleteAll();
        } else {
            $dataProvider = parent::prepareDataProvider();

            if ($dataProvider instanceof ActiveDataProvider) {
                /** @var ActiveQuery $query */
                $query = $dataProvider->query;
                $query
                    ->limit(-1)
                    ->offset(-1)
                    ->orderBy([]);

                $countDeleted = 0;

                foreach ($query->each() as $model) {
                    /** @var $model ActiveRecord */
                    if ($model->delete()) {
                        $this->_deletedModels[] = $model;
                        $countDeleted++;
                    }
                }
            }
        }

        Yii::$app->response->headers->set('X-Total-Deleted', $countDeleted);

        return;
    }
}
