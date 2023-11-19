<?php 

$platform   = isset($params['platform']) ? $params['platform'] : 'wordpress';
$data = $params['data'];
$total = $params['total'];
$qty = $params['quantity'];

if ($platform == 'opencart') {
	$tax = isset($data['price_taxes']) ? $data['price_taxes'] : 0;
	$eco = isset($data['price_eco']) ? $data['price_eco'] : 0;

	if ($tax > 0) {
		$total->old += $total->old * $tax + $eco * $qty;
		$total->printing += $total->printing * $tax;
		$total->sale += $total->sale * $tax + $eco * $qty;
		$total->clipart += $total->clipart * $tax;
	}
}
$GLOBALS['total'] = $total;

?>