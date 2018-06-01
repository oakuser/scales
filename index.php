<?php
require_once 'Scales.php';

$planogram = [
    ['product_id' => 1, 'weight' => 387, 'qty' => 5],
    ['product_id' => 2, 'weight' => 402, 'qty' => 5],
    ['product_id' => 3, 'weight' => 510, 'qty' => 5],
];

$scales = new Scales($planogram);

/*$result = $scales->parseWeight(1200);

foreach ($result as $row) {
    $w = 0;
    foreach ($row as $key => $value) {
    //for ($key = 0; $key < count($row); $key++) {
        $w += $row[$key] * $planogram[$key]['weight'];
    }

    echo implode('', $row) . ' ' . $w . "\n";
}*/

$result = $scales->testPlanogram();
$qtyVariants = 0;
foreach ($result as $weight => $variants) {
    foreach ($variants as $variant) {
        echo implode('', $variant) . ' ' . $weight . "\n";
        $qtyVariants++;
    }
}
echo $qtyVariants . " variants\n";
