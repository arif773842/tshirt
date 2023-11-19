<?php

if (!defined('ROOT')) exit('No direct script access allowed');

class dgps extends dg
{
	public function saveDesignAdmin()
	{
		$results = array();
		if ($this->is_session_started() === false) @session_start();
		if (empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === false) {
			$results['error'] = 1;
			$results['login'] = 1;
			$results['msg']	= lang('design_save_login');
			echo json_encode($results);
			exit;
		}

		// check user login
		$is_logged = $_SESSION['is_admin'];
		$user = md5($is_logged['id']);
		$data = json_decode(file_get_contents('php://input'), true);

		$uploaded = $this->folder();
		$path = ROOT .DS. $uploaded;

		if ($data['isIE'] == 'true') {
			$buffer	= $data['image'];
		} else {
			$temp = explode(';base64,', $data['image']);
			$buffer	= base64_decode($temp[1]);
		}

		$design = array();
		if (isset($data['options'])) {
			$design['options'] = $data['options'];
		}

		$design['vectors'] = $data['vectors'];
		$design['teams'] = $data['teams'];
		$design['fonts'] = $data['fonts'];
		$designer_id = $data['designer_id'];

		// check design and author
		if ($data['design_file'] != '' && $designer_id == $user && $data['design_key'] != '') {
			// override file and update
			$temp = explode('/', $data['design_file']);
			$file = $temp[count($temp) - 1];
			if ($data['isIE'] == 'true') {
				$file = str_replace('.png', '.svg', $file);
			} else {
				$file = str_replace('.svg', '.png', $file);
			}

			$path_file = $path . $file;
			$file = str_replace('\\', '/', $uploaded) .'/'. $file;
			$file = str_replace('//', '/', $file);
			$key = $data['design_key'];
			$design['design_id'] = $key;
		} else {

			$key = strtotime("now"). rand();
			if($data['isIE'] == 'true') {
				$file = 'design-' . $key . '.svg';
			} else {
				$file = 'design-' . $key . '.png';
			}

			$path_file = $path .DS. $file;
			$file = str_replace('\\', '/', $uploaded) .'/'. $file;
			$file = str_replace('//', '/', $file);
			$design['design_id'] = $key;
		}


		if (!$this->WriteFile($path_file, $buffer)) {
			$results['error'] = 1;
			$results['msg']	= lang('design_msg_save');
		} else {
			if ($is_logged['login'] == 1) {
				$cache = $this->cache('admin');
			} else {
				$cache = $this->cache();
			}
			$myDesign = $cache->get($user);
			if ($myDesign == null ) {
				$myDesign = array();
			}

			if (isset($data['attribute'])) {
				$design['attribute'] = $data['attribute'];
			}

			if (isset($data['print_type'])) {
				$design['print_type'] = $data['print_type'];
			}

			$design['image'] = $file;
			$design['parent_id'] = $data['parent_id'];
			$design['product_id'] = $data['product_id'];
			$design['product_options'] = $data['product_color'];

			$design['cliparts'] = $data['cliparts'];
			$design['colors'] = $data['colors'];
			$design['print'] = $data['print'];
			$design['images'] = $data['images'];


			// create images of design
			foreach ($design['images'] as $view => $str) {
				$src = '';
				if ($str != '') {
					if ($data['isIE'] == 'true') {
						$buffer		= $str;
						$view_file 	= 'design' .'-'. $view .'-'. $key . '.svg';
					} else {
						$temp 		= explode(';base64,', $str);
						$buffer		= base64_decode($temp[1]);
						$view_file 	= 'design' .'-'. $view .'-'. $key . '.png';
					}
					$path_file	= $path .DS. $view_file;
					$this->WriteFile($path_file, $buffer);
					$view_file	= str_replace('\\', '/', $uploaded) .'/'. $view_file;
					$view_file	= str_replace('//', '/', $view_file);
					$src		= $view_file;
				}
				$design['images'][$view] = $src;
			}

			$design['title']  			= $data['title'];
			$design['description']  	= $data['description'];

			// save design to cache
			$myDesign[$key]	= $design;
			$cache->set($user, $myDesign);

			$results['error'] = 0;

			$content = array(
				'user_id'=> $user,
				'design_id'=> $key,
				'design_key'=> $key,
				'designer_id'=> $user,
				'design_file'=> $file,
			);
			$results['content'] = $content;

		}

		echo json_encode($results);
		return;
	}

	protected function is_session_started()
	{
	    if (php_sapi_name() !== 'cli') {
	        if (version_compare(phpversion(), '5.4.0', '>=')) {
	            return session_status() === PHP_SESSION_ACTIVE ? true : false;
	        } else {
	            return session_id() === '' ? false : true;
	        }
	    }
	    return false;
	}

	public function getImgage($str)
	{
		$data = str_replace("'", '"', $str);
		$data = json_decode($data);
		if (count($data) > 0) {
			foreach ($data as $vector) {
				if (isset($vector->img) && $vector->img != '') {
					$img = $vector->img;
					return base_url($img);
				}
			}
		}
		return '';
	}

	public function getProducts2()
	{
		$file = $this->path_data .DS. 'products.json';
		if (file_exists($file)) {
			$data = Tools::file_get_contents($file);
			$products = Tools::jsonDecode($data);
			return (isset($products->products) ? $products->products : array());
		} else {
			return array();
		}
	}

	public function reGenderAttributes($attribute, $ps_taxes, $ps_product_id = 0, $min_order = 1, $attributes = array())
	{
		if (count($attributes) < 1 || !isset($attributes['attribute'])) {
			$attributes['attribute'] = array();
		}

		$html = $this->getAttributes2($attribute, $ps_taxes, $ps_product_id, $attributes['attribute']);

		/*if (count($attributes) && isset($attributes['quantity']) && $min_order < $attributes['quantity']) {
			$min_order = $attributes['quantity'];
		}*/
		$html .= $this->quantity($min_order);

		return $html;
	}

	protected function getAttributes2($attribute, $ps_taxes, $ps_product_id = 0, $attributes = array())
	{
		if (isset($attribute->name) && $attribute->name != '')
		{
			$attrs = new stdClass();

			if (is_string($attribute->name))
				$attrs->name 		= json_decode($attribute->name);
			else
				$attrs->name 		= $attribute->name;

			if (is_string($attribute->titles))
				$attrs->titles 		= json_decode($attribute->titles);
			else
				$attrs->titles 		= $attribute->titles;

			if (is_string($attribute->prices))
				$attrs->prices 		= json_decode($attribute->prices);
			else
				$attrs->prices 		= $attribute->prices;

			if (is_string($attribute->type))
				$attrs->type 		= json_decode($attribute->type);
			else
				$attrs->type 		= $attribute->type;

			if(empty($attribute->obj))
			{
				$attribute->obj = array();
			}
			if (is_string($attribute->obj))
				$attrs->obj 		= json_decode($attribute->obj);
			else
				$attrs->obj 		= $attribute->obj;

			if(empty($attribute->required))
			{
				$attribute->required = array();
			}
			if (is_string($attribute->required))
				$attrs->required 		= json_decode($attribute->required);
			else
				$attrs->required 		= $attribute->required;

			if(empty($attribute->value))
			{
				$attribute->value = array();
			}
			if (is_string($attribute->value))
				$attrs->value 		= json_decode($attribute->value);
			else
				$attrs->value 		= $attribute->value;

			$html 				= '';
			$setttings 	= $this->getSetting();

			for ($i=0; $i<count($attrs->name); $i++)
			{
				$html 	.= '<div class="form-group product-fields">';
				$html 	.= 		'<label for="fields">'.$attrs->name[$i].'</label>';

				$id 	 	= 'attribute['.$i.']';
				$options 	= array(
					'name' => $attrs->name[$i],
					'title' => $attrs->titles[$i],
					'price' => $attrs->prices[$i],
					'type' => $attrs->type[$i],
					'id' => $id,
				);
				$options['required'] 	= 0;
				if(isset($attrs->required[$i]))
				{
					$options['required'] = $attrs->required[$i];
				}
				if( isset($attrs->obj[$i]) && $attrs->obj[$i] != '' && $attrs->obj[$i] != 'none' )
				{
					$options['obj'] = $attrs->obj[$i];
					$options['value'] = $attrs->value[$i];

					$html 	.= $this->field_action2($options, $setttings, $ps_taxes, $ps_product_id, isset($attributes[$i]) ? $attributes[$i] : false);
				}
				else
				{
					$html 	.= $this->field2($options, $setttings, $ps_taxes, $ps_product_id, isset($attributes[$i]) ? $attributes[$i] : false);
				}

				$html 	.= '</div>';
			}
			return $html;
		}
		else
		{
			return '';
		}

	}

	protected function attributePrice2($price, $setttings, $ps_taxes = 0, $ps_product_id = 0)
	{
		$html = '';

		if ($price != '' && $price != '0') {
			if (isset($setttings->currency_symbol)) {
				$currency = $setttings->currency_symbol;
			} else {
				$currency = '$';
			}

			if (strpos($price, '-') !== false) {
				$price = str_replace('-', '', $price);
				$add = '-';
			} elseif (strpos($price, '+') !== false) {
				$price = str_replace('+', '', $price);
				$add = '+';
			} else {
				$price = $price;
				$add = '+';
			}

			if ((float)$ps_taxes > 0) {
				$address = new Address();
				$address->id_country = Context::getContext()->country->id;

				$delivery = new Address((int) Context::getContext()->cart->id_address_delivery);
		        $address->id_state = $delivery->id_state ? $delivery->id_state : 0;
		        $address->postcode = $delivery->postcode ? $delivery->postcode : 0;

		        $tax_manager = TaxManagerFactory::getManager($address, Product::getIdTaxRulesGroupByIdProduct((int) $ps_product_id, Context::getContext()));
		        $product_tax_calculator = $tax_manager->getTaxCalculator();

				//add taxes to price on prestashop
				$price = $product_tax_calculator->addTaxes($price);
			}

			if (isset($setttings->currency_postion) && $setttings->currency_postion == 'right') {
				$html = ' ('.$add.$price.$currency.')';
			} else {
				$html = ' ('.$add.$currency.$price.')';
			}
		}
		return $html;
	}

	protected function field_action2($options, $setttings, $ps_taxes, $ps_product_id, $current_attribute = false)
	{
		$name 		= $options['name'];
		$obj 		= $options['obj'];
		$obj_value 	= $options['value'];
		$required 	= $options['required'];
		$title 		= $options['title'];
		$price 		= $options['price'];
		$type 		= $options['type'];
		$id 		= $options['id'];

		$class_required = '';
		if ($required == 1) {
			$class_required = 'required';
		}

		$action = "design.attribute.init(this, '".$obj."')";
		if ($obj == 'image') {
			$action = 'onclick="'.$action.'"';
		} else {
			$action = 'onchange="'.$action.'"';
		}

		$html = '<div class="dg-poduct-fields '.$class_required.'">';
		switch ($obj) {
			case 'image':
				for ($i = 0; $i < count($title); $i++) {
					if( isset($obj_value[$i]) &&  $obj_value[$i] != '') {
						$html_price = $this->attributePrice2($price[$i], $setttings, $ps_taxes, $ps_product_id);

						$html .= '<label class="radio-inline attr-img pull-left dg-tooltip" title="'.htmlentities($title[$i]).$html_price.'">';
						$html .= 	'<img '.$action.' src="'.$obj_value[$i].'" class="img-responsive" alt="'.$title[$i].'" width="10">';
						$html .= 	'<input type="radio" name="'.$id.'" value="'.$i.'">';
						$html .= '</label>';
					}
				}
			break;

			default:
				$html .= '<select '.$action.' class="form-control input-sm '.$class_required.'" name="'.$id.'">';
				for ($i = 0; $i < count($title); $i++) {
					if ($price[$i] != '0') {
						$html_price = $this->attributePrice2($price[$i], $setttings, $ps_taxes, $ps_product_id);
					} else {
						$html_price = '';
					}
					if ($current_attribute !== false && $current_attribute == $i){
						$html .= '<option data-value="'.$obj_value[$i].'" value="'.$i.'" selected>'.$title[$i].$html_price.'</option>';
					} else {
						$html .= '<option data-value="'.$obj_value[$i].'" value="'.$i.'">'.$title[$i].$html_price.'</option>';
					}
				}
				$html .= '</select>';
			break;
		}
		$html	.= '</div>';

		return $html;
	}

	protected function field2($options, $setttings, $ps_taxes, $ps_product_id, $current_attribute = false)
	{
		$name 	= $options['name'];
		$title 	= $options['title'];
		$price 	= $options['price'];
		$type 	= $options['type'];
		$id 	= $options['id'];

		$class_required = '';
		if (isset($options['required']) && $options['required'] == 1) {
			$class_required = 'required';
		}

		$html = '<div class="dg-poduct-fields '.$class_required.'" data-type="'.$type.'">';
		switch ($type) {
			case 'checkbox':
				for ($i = 0; $i < count($title); $i++) {
					$html .= '<label class="checkbox-inline">';
					if ($current_attribute !== false && is_array($current_attribute) && count($current_attribute) && in_array($i, $current_attribute)) {
						$html .= 	'<input type="checkbox" name="'.$id.'['.$i.']" value="'.$i.'" checked> '.$title[$i];
					} else {
						$html .= 	'<input type="checkbox" name="'.$id.'['.$i.']" value="'.$i.'"> '.$title[$i];
					}

					$html .= $this->attributePrice2($price[$i], $setttings, $ps_taxes, $ps_product_id);
					$html .= '</label>';
				}
			break;

			case 'selectbox':
				$html .= '<select class="form-control input-sm" name="'.$id.'">';

				for ($i = 0; $i < count($title); $i++) {
					if ($price[$i] != '0') {
						$html_price = $this->attributePrice2($price[$i], $setttings, $ps_taxes, $ps_product_id);
					} else {
						$html_price = '';
					}
					if ($current_attribute !== false && $current_attribute == $i) {
						$html .= '<option value="'.$i.'" selected>'.$title[$i].$html_price.'</option>';
					} else {
						$html .= '<option value="'.$i.'">'.$title[$i].$html_price.'</option>';
					}

				}
				$html .= '</select>';
			break;

			case 'radio':
				for ($i = 0; $i < count($title); $i++) {
					$html .= '<label class="radio-inline">';
					if ($current_attribute !== false && $current_attribute == $i) {
						$html .= 	'<input type="radio" name="'.$id.'" value="'.$i.'" checked> '.$title[$i];
					} else {
						$html .= 	'<input type="radio" name="'.$id.'" value="'.$i.'"> '.$title[$i];
					}

					$html .= $this->attributePrice2($price[$i], $setttings, $ps_taxes, $ps_product_id);
					$html .= '</label>';
				}
			break;

			case 'textlist':
				$html 		.= '<style>.product-quantity{display:none;}</style><ul class="p-color-sizes list-number col-md-12">';
				for ($i = 0; $i < count($title); $i++) {
					$html .= '<li>';
					if ($price[$i] != '0') {
						$html_price = '<small>'.$this->attributePrice2($price[$i], $setttings, $ps_taxes, $ps_product_id).'</small>';
					} else {
						$html_price = '';
					}
					$html .= 	'<label data-id="'.$title[$i].'">'.$title[$i].$html_price.'</label>';
					if ($current_attribute !== false && is_array($current_attribute) && count($current_attribute)) {
						foreach ($current_attribute as $key => $value) {
							if ($i == $key && !empty($value) && $value > 0) {
								$html .= '<input type="text" class="form-control input-sm size-number" name="'.$id.'['.$i.']" value="'.$value.'">';
							} else {
								$html .= '<input type="text" class="form-control input-sm size-number" name="'.$id.'['.$i.']">';
							}
							break;
						}
					}
					$html .= '</li>';
				}
				$html .= '</ul>';
			break;
		}
		$html .= '</div>';

		return $html;
	}
}
