<?php
/**
 * Created by PhpStorm.
 * User: Yarmaliuk Mikhail
 * Date: 22.03.18
 * Time: 22:28
 */

namespace MP\ExtendedApi;

use yii\data\ActiveDataFilter;
use yii\data\DataFilter;
use yii\db\Expression;

/**
 * Class    EActiveDataFilter
 * @package MP\ExtendedApi
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class EActiveDataFilter extends ActiveDataFilter
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $conditionBuilders = [
            'LIKE LOWER' => function (string $operator, $condition, string $attribute) {
                return ['LIKE', "LOWER($attribute)", new Expression("LOWER('$condition')"), false];
            },
        ];

        $filterControls = [
            'like lower' => 'LIKE LOWER',
        ];

        $operatorTypes = [
            'LIKE LOWER' => [DataFilter::TYPE_STRING],
        ];

        $this->conditionBuilders = array_merge($this->conditionBuilders, $conditionBuilders);
        $this->filterControls    = array_merge($this->filterControls, $filterControls);
        $this->operatorTypes     = array_merge($this->operatorTypes, $operatorTypes);
    }
}
