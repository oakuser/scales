<?php
//todo: использовать разные погрешности весов в зависимости от массы на весах
//todo: учесть миниум массы из документации на весах
//todo: Рекомендации по ассортименту в случае нахождения коллизий при тестировании планограммы
//todo: учесть массу поддона
//todo: доработать обработку диапазонов масс когда цена деления не равна точности (особенно если точность больше дискретности)

Class Scales
{
    protected $planogram = [],
        $maxDeltaCount,
        $fault,
        $scalesType;

    /**
     * Scales constructor.
     * @param $actualPlanogram = [['product_id' => int, 'weight' => int, 'qty' => int], ...]
     * @param $maxDeltaCount - максимальное изменение кол-ва товаров на весах
     */
    function __construct($actualPlanogram, $maxDeltaCount = 3, $scalesType = 'MP-300')
    {
        $this->planogram = $actualPlanogram;
        $this->maxDeltaCount = $maxDeltaCount;
        $this->scalesType = $this->getScalesType($scalesType);
    }

    public function parseWeight($weight, $deltaWeight = null)
    {
        $result = [];

        if (is_null($deltaWeight)) {
            $deltaWeight = $weight;
        }

        $variants = $this->getAllVariants();
        $variants = $this->clearVariants($variants);

        foreach ($variants as $variant) {
            $w = 0;
            foreach ($variant as $key => $value) {
                $w += $value * $this->planogram[$key]['weight'];
            }

            $fault = $this->scalesType['fault'];

            $w = round($w / $fault) * $fault;

            if ($w == $deltaWeight) {
                $result[] = $variant;
            }
        }

        return $result;
    }

    /**
     * Генерит массив из всех возможных комбинаций количеств товаров
     * @param $row
     * @param int $col
     * @param int $maxDeltaCount
     * @return array
     */
    protected function getAllVariants($row = [], $col = 0)
    {
        $arr = [];

        if (empty($row)) {
            foreach ($this->planogram as $value) {
                $row[] = 0;
            }
        }

        if (isset($this->planogram[$col])) {
            for ($cnt = 0; $cnt <= $this->planogram[$col]['qty']; $cnt++) {
                $row[$col] = $cnt;
                $arr[] = $row;
                $arr_tmp = self::getAllVariants($row, $col + 1);
                $arr = array_merge($arr, $arr_tmp);

                if (array_sum($row) >= $this->maxDeltaCount) break;
            }
        }

        return $arr;
    }

    /**
     * Чистка вариантов от дубликатов
     * @param $variants
     * @return array
     */
    protected function clearVariants($variants)
    {
        $result = [];

        foreach ($variants as $row) {
            if (array_sum($row) === 0) continue;

            $key = implode('', $row);
            $result[$key] = $row;
        }

        ksort($result);
        $result = array_values($result);

        return $result;
    }

    protected function getScalesType($typeName)
    {
        $types = [
            'MP-300' => [
                'min_weight' => 2000,
                'max_weight' => 300000,
                'faults' => [
                    2000    => 50,
                    50000   => 100,
                    200000  => 150,
                ],
                'fault' => 100,
            ],
            'MP-150' => [
                'min_weight' => 1000,
                'max_weight' => 150000,
                'faults' => [
                    1000    => 25,
                    25000   => 50,
                    100000  => 75,
                ],
                'fault' => 50,
            ],
        ];

        return isset($types[$typeName]) ? $types[$typeName] : false;
    }

    public function testPlanogram()
    {
        $variants = $this->getAllVariants();
        $variants = $this->clearVariants($variants);

        $weights = [];
        foreach ($variants as $key => $variant) {
            $variantWeight = 0;
            foreach ($variant as $planogramKey => $qty) {
                $variantWeight += $qty * $this->planogram[$planogramKey]['weight'];
            }

            $fault = $this->scalesType['fault'];
            $variantWeight = round($variantWeight / $fault) * $fault;

            $weights[$variantWeight][] = $variant;
        }

        foreach ($weights as $weight => $weightVariants) {
            if (count($weightVariants) < 2) {
                unset($weights[$weight]);
            }
        }

        return $weights;
    }
}