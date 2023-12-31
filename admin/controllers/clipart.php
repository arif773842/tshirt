<?php
/**
 * @author tshirtecommerce - www.tshirtecommerce.com
 * @date: 2015-01-10
 *
 * @copyright  Copyright (C) 2015 tshirtecommerce.com. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 *
 */
if ( ! defined('ROOT')) exit('No direct script access allowed');

class Clipart extends Controllers
{

	public function index($index = 1, $cateid = 0)
	{
		include_once(ROOT.DS.'includes'.DS.'functions.php');
		$dg = new dg();

		$data = array();
		if ($index < 1) $index = 1;

		$data['page']		= 1;
		$data['cateid']		= $cateid;
		$data['title'] 		= 'Cliparts';
		$data['sub_title'] 	= 'Images of Design';

		$dgClass 			= new dg();

		$arts 			= array();

		$file = dirname(ROOT) .DS. 'data' .DS. 'arts.json';
		$setting = dirname(ROOT).DS.'data'.DS.'settings.json';
		$data['currency_symbol'] = '$';
		if (file_exists($file))
		{
			$str 			= $dg->readFile($file);
			$setting 		= $dg->readFile($setting);
			$settings 		= json_decode($setting);
			$rows 		= json_decode($str);

			/* show button move to clipart */
			$is_store = false;
			$is_moved = false;
			if ( isset($settings->store) && isset($settings->store->enable) && $settings->store->enable == 1
				&& isset($settings->merge_cliparts) && $settings->merge_cliparts == 1 )
			{
				$dgClass->redirect('index.php/clipart/merge/1');
				exit;
			}

			if(isset($rows->arts) && count($rows->arts))
			{
				$rows->arts = array_reverse($rows->arts);
				if($is_store === true)
				{
					foreach ($rows->arts as $key => $art)
					{
						if(isset($art->is_added_store))
						{
							unset($rows->arts[$key]);
							$is_moved = true;
						}
					}
				}

			}

			$data['is_store'] = $is_store;
			$data['is_moved'] = $is_moved;

			// find category
			if ($cateid > 0)
			{
				$array = array();
				$count = 0;
				for($i=0; $i<$rows->count; $i++)
				{
					if ($rows->arts[$i]->cate_id == $cateid)
					{
						$count++;
						$array[] = $rows->arts[$i];
					}
				}
				$rows->count = $count;
				$rows->arts = $array;
			}

			if (isset($rows->count) && $rows->count > 30)
			{
				$min = ($index-1) * 30;
				$max = $index * 30;
				for($i=$min; $i<$max; $i++)
				{
					if (empty($rows->arts[$i])) break;
					$arts[] = $rows->arts[$i];
				}
			}
			elseif(isset($rows->arts))
			{
				$arts 	= $rows->arts;
			}
			else
			{
				$arts 	= array();
			}

			if (isset($rows->count) && $rows->count % 30 == 0)
			{
				$data['page']	= $rows->count / 30;
			}
			elseif(isset($rows->count))
			{
				$data['page']	= (int) ($rows->count / 30) + 1;
			}
			else
			{
				$data['page']	= 0;
			}
			$data['currency_symbol'] = $settings->currency_symbol;
			$data['currency_postion'] = '';
			if(isset($settings->currency_postion))
				$data['currency_postion'] = $settings->currency_postion;
		}

		$data['arts'] 		= $arts;

		$data['index']		= $index;

		$this->view('clipart', $data);
	}

	/*
	* Import clipart export from store via tools
	 */
	public function import()
	{
		if (!is_dir(dirname(ROOT).DS.'data'.DS.'import')) {
		    @mkdir(dirname(ROOT).DS.'data'.DS.'import', 0755, true);
		}

		$file 	= dirname(ROOT) .DS. 'data' .DS. 'import' .DS. 'cliparts.json';
		if(file_exists($file))
		{
			$content 	= file_get_contents($file);
			if($content !== false)
			{
				$data = json_decode($content, true);
				if( isset($data['arts']) && $data['arts'] > 0 )
				{
					unlink($file);
					$rows 	= $data['arts'];
					if(count($rows) == 0) return;

					$data 	= array();
					foreach ($rows as $art)
					{
						$key 		= md5($art['file_name']);
						$data[$key]	= $art;
					}

					/* get index of clipart */
					$index 	= 0;
					$file 	= dirname(ROOT) .DS. 'data' .DS. 'arts.json';
					if( file_exists($file) )
					{
						$content 	= file_get_contents($file);
						$arts 	= json_decode($content, true);

						$index 	= 0;
						if( isset($arts['arts']) && count($arts['arts']) )
						{
							foreach($arts['arts'] as $row)
							{
								if ($row['clipart_id'] > $index)
								{
									$index = $row['clipart_id'];
								}

								$key 		= md5($row['file_name']);
								if( isset($data[$key]) )
								{
									unset($data[$key]);
								}
							}
						}
						if(count($data) == 0) return;

						$index = $index + 1;
						foreach ($data as $key => $art)
						{
							$art['clipart_id'] = $index;
							$arts['arts'][] 	= $art;
							$index++;
						}
						$arts['count'] = count($arts['arts']);
					}
					else
					{
						$arts 	= array(
							'arts' => $rows,
							'count' => count($rows),
						);
					}
					$dgClass 	= new dg();
					$dgClass->WriteFile($file, json_encode($arts));
					$this->merge();
				}
			}
		}
		else
		{
			echo 'No';
		}
	}

	// move all clipart, categories to store */
	public function merge($reload = 0)
	{
		$dg = new dg();

		/* get categories store */
		$file 	= dirname(ROOT) .DS. 'data' .DS. 'store' .DS. 'art_categories.json';
		if (!file_exists($file)) {
			file_put_contents($file, '');
		}
		if(file_exists($file))
		{
			$str 		= $dg->readFile($file);
			$data		= json_decode($str, true);
		}
		if (!isset($data) || $data == false || $data == null) {
			$data = array();
		}

		$ids = array();

		// move categories */
		if(isset($data))
		{
			$store_categories 	= array();
			for($i=0; $i<count($data); $i++)
			{
				$store_categories[$data[$i]['id']] = $data[$i];
			}

			$categories 	= $this->categoriestree(true, false);

			$categories 	= json_decode( json_encode($categories), true);
			if ($categories == null || $categories == false) {
				$categories = array();
			}

			if( count($categories) )
			{
				$shop_categories = array();
				foreach ($categories as $j => $cate)
				{
					if(isset($cate['is_shop']) && $cate['is_shop'] == 1) continue;
					if($cate['id'] == 0) continue;

					if(count($data))
                    {
                        $check_cate = true;
                        foreach($data as $v)
                        {
                            if($v['id'] == $cate['id'] && $v['title'] == $cate['title'])
                            {
                                $check_cate = false;
                                break;
                            }
                        }
                        if($check_cate == false)
                            continue;
                    }

					$new_id = '11111'.$cate['id'];

					$ids[$cate['id']] = $new_id;

					if(isset($store_categories[$new_id ])) continue;

					if( isset($cate['children']) && count($cate['children']) )
					{
						foreach($cate['children'] as $i => $child)
						{
							$id_child 				= '11111'.$child['id'];
							$child['parent_id'] 	= $new_id;
							$child['is_shop'] 		= 1;
							$ids[$child['id']] 		= $id_child;
							$child['id'] 			= $id_child;
							$cate['children'][$i] 	= $child;
						}
					}

					$categories[$j]['is_added_store'] = 1;

					$cate['is_shop'] 		= 1;
					$cate['id'] 			= $new_id;
					$shop_categories[] 		= $cate;
				}

				/* add categories */
				if(count($shop_categories))
				{

					foreach ($shop_categories as $cate)
					{
						$data[] = $cate;
					}
					/* update store categories */
					$dg->WriteFile($file, json_encode($data));

					/* update shop categories */
					$file 	= dirname(ROOT) .DS. 'data' .DS. 'categories_art.json';
					$dg->WriteFile($file, json_encode($categories));
				}
			}
		}

		/* move art */
		$file 	= dirname(ROOT) .DS. 'data' .DS. 'arts.json';
		if (file_exists($file))
		{
			$str 		= $dg->readFile($file);
			$arts		= json_decode($str, true);
			/* get art of store */
			$file_store 	= dirname(ROOT) .DS. 'data' .DS. 'store' .DS. 'arts_info.json';
			if(!file_exists($file_store))
			{
				$myfile = fopen($file_store, "w");
				fclose($myfile);
			}
			$file_store_id 	= dirname(ROOT) .DS. 'data' .DS. 'store' .DS. 'arts.json';

			$arts_ids		= json_decode($dg->readFile($file_store_id), true);
			if ($arts_ids == null || $arts_ids == false) {
				$arts_ids = array();
			}

			if(file_exists($file_store) && count($arts['arts']))
			{
				$str 		= $dg->readFile($file_store);
				$art_store  = json_decode($str, true);

				foreach ($arts['arts'] as $key => $art)
				{
					$id = '22222'.$art['clipart_id'];

					if(count($arts_ids) && in_array($id, $arts_ids)) continue;
					if(isset($art_store[$id])) continue;

					if(empty($art['is_added_store']))
					{
						$arts_ids[] = $id;
					}

					$art['clipart_id'] = $id;
					$art['id'] = $id;
					if( isset($ids[$art['cate_id']]) )
					{
						$art['cate_id'] = $ids[$art['cate_id']];
					}
					$art['is_shop'] = 1;
					$art['is_added_store'] = 1;
					$art_store[$id] = $art;
					$arts['arts'][$key]['is_added_store'] = 1;
				}
				$dg->WriteFile($file_store, json_encode($art_store));
				$dg->WriteFile($file, json_encode($arts));
				$dg->WriteFile($file_store_id, json_encode($arts_ids));
			}
		}

		if($reload == 1)
		{
			$dg->redirect('index.php/store/arts');
		}
		$dg->redirect('index.php/clipart');
	}

	// find ajax
	public function ajax($index = 1, $cateid = 0)
	{
		include_once(ROOT.DS.'includes'.DS.'functions.php');
		$dg = new dg();

		$data = array();
		if ($index < 1) $index = 1;

		$data['cateid']		= $cateid;
		$data['page']		= 1;
		$data['title'] 		= 'Manager';
		$data['sub_title'] 	= 'Images of Design';

		$dgClass 			= new dg();

		$arts 				= array();

		$file = dirname(ROOT) .DS. 'data' .DS. 'arts.json';
		$setting = dirname(ROOT).DS.'data'.DS.'settings.json';
		$data['currency_symbol'] = '$';
		if (file_exists($file))
		{
			$cate_id = array();
			$cate = dirname(ROOT) .DS. 'data' .DS. 'categories_art.json';
			if (file_exists($cate))
			{
				$cates = $dg->readFile($cate);
				$cates = json_decode($cates, true);
				if(is_array($cates))
				{
					foreach($cates as $val)
					{
						if($val['id'] == $cateid && !in_array($cateid, $cate_id))
							$cate_id[] = $cateid;
						if(in_array($val['parent_id'], $cate_id))
							$cate_id[] = $val['id'];
					}
				}
			}

			$str 		= $dg->readFile($file);
			$setting 		= $dg->readFile($setting);
			$settings 		= json_decode($setting);
			$rows 		= json_decode($str);
			if ($cateid > 0)
			{
				$array = array();
				$count = 0;
				for($i=0; $i<$rows->count; $i++)
				{
					if (empty($rows->arts[$i])) continue;
					if (in_array($rows->arts[$i]->cate_id, $cate_id))
					{
						$count++;
						$array[] = $rows->arts[$i];
					}
				}
				$rows->count = $count;
				$rows->arts = $array;
			}

			if ($rows->count > 30)
			{
				$min = ($index-1) * 30;
				$max = $index * 30;
				for($i=$min; $i<$max; $i++)
				{
					if (empty($rows->arts[$i])) break;
					$arts[] = $rows->arts[$i];
				}
			}
			else
			{
				$arts 	= $rows->arts;
			}

			if ($rows->count % 30 == 0)
			{
				$data['page']	= $rows->count / 30;
			}
			else
			{
				$data['page']	= (int) ($rows->count / 30) + 1;
			}
			$data['currency_symbol'] = $settings->currency_symbol;
		}
		$data['arts'] 	= $arts;

		$data['index']		= $index;

		$this->modal('ajax_clipart', $data);
	}

	// add, edit art
	function edit($id = 0)
	{
		$data 	= array();

		if ($id == 0)
		{
			$data['sub_title'] = lang('art_add', true);
			$art 	= new stdClass();
		}
		else
		{
			$data['sub_title'] = lang('art_edit', true);
			$file 	= dirname(ROOT) .DS. 'data' .DS. 'arts.json';
			$art 	= new stdClass();
			if (file_exists($file))
			{
				$str 		= file_get_contents($file);
				$rows 	= json_decode($str);
				if (isset($rows->arts) && count($rows->arts) > 0)
				{
					foreach($rows->arts as $row)
					{
						if ($row->clipart_id == $id)
						{
							$art = $row;
							break;
						}
					}
				}
			}
		}
		$data['art'] = $art;

		$data['title'] 	= 'Clipart';
		$data['id'] 	= $id;

		$data['categories'] 	= $this->categoriestree(true);

		$this->view('clipart_edit', $data);
	}

	// save clipart
	public function save()
	{
		$dgClass 				= new dg();
		if (!empty($_POST))
		{
			$art = $_POST['art'];
			if (isset($_FILES["file"]["name"]) && $_FILES["file"]["name"] != '')
			{
				// create folder
				$root		= dirname(ROOT) .DS. 'uploaded' .DS. 'cliparts' .DS. $art['cate_id'];
				if (!file_exists($root))
				{
					 mkdir($root, 0755, TRUE);
				}

				$upload_path = $root .DS. 'print' .DS;
				if(!is_dir($upload_path))
				{
					mkdir($upload_path, 0755, TRUE);
				}

				// upload file
				require_once dirname(ROOT) .DS. 'includes' .DS. 'upload.php';
				$uploader   =   new Uploader();
				$uploader->setDir($upload_path);

				$uploader->setExtensions(array('jpg','jpeg','png','gif','svg'));
				$uploader->setMaxSize(10);
				$uploader->sameName(false);

				if($uploader->uploadFile('file'))
				{
					$image  		=  $uploader->getUploadName();
					$extension 		= $uploader->getExtension($image);
					$extension		= strtolower($extension);

					$url 				= site_url('uploaded/cliparts/');
					$url				= str_replace('/admin/', '/', $url);
					$art['file_type'] 	= $extension;
					$art['file_name'] 	= $image;
					$art['path'] 		= $url;
					$art['url'] 		= $url. $art['cate_id'] .'/';

					// create folder thumb
					$thumbs	= $root .DS. 'thumbs';
					if(!is_dir($thumbs)) mkdir($thumbs, 0755, TRUE);

					$medium	= $root .DS. 'medium';
					if(!is_dir($medium)) mkdir($medium, 0755, TRUE);

					if ($extension == 'svg')
					{
						$art['change_color'] = 1;
						$art['thumb'] 	= 'print/' . $image;
						$art['medium'] 	= 'print/' . $image;
					}
					else
					{
						require_once ROOT .DS. 'includes' .DS. 'thumb.php';
						$thumb		= new thumb($upload_path . $image);
						$thumb->resize(null, 300, 300, $thumbs .DS. md5($image) .'.'.$extension);
						$thumb->resize(null, 500, 500, $medium .DS. md5($image.'medium').'.'.$extension);

						$art['thumb'] 	= 'thumbs/' . md5($image) .'.'. $extension;
						$art['medium'] 	= '/medium/' . md5($image.'medium') .'.'. $extension;
						$art['change_color'] = 0;
					}
				}
				else
				{
					echo $uploader->getMessage();
				}
			}

			$art['price'] = (float) $art['price'];

			$file 	= dirname(ROOT) .DS. 'data' .DS. 'arts.json';
			// check file
			if (!file_exists($file))
			{
				$dgClass->redirect('index.php/clipart');
				return;
			}

			$data 	= file_get_contents($file);
			$arts 	= json_decode($data);
			$is_new = true;
			$conent	= array();
			if (isset($_POST['id']) && $_POST['id'] > 0)
			{
				$id 	= $_POST['id'];
				// update clipart
				if (isset($arts->arts) && count($arts->arts) > 0)
				{
					foreach($arts->arts as $row)
					{
						if ($row->clipart_id == $id)
						{
							$art['clipart_id']	= $id;
							$conent[] 			= $art;
							$is_new			= false;
						}
						else
						{
							$conent[] = $row;
						}
					}
				}
			}

			if ($is_new === true)
			{
				$index = 0;
				foreach($arts->arts as $row)
				{
					if ($row->clipart_id > $index)
					{
						$index = $row->clipart_id;
					}
					$conent[] = $row;
				}
				$art['clipart_id']	= $index + 1;
				$conent[] = $art;
				$arts->count = $arts->count + 1;
			}
			$arts->arts	= $conent;
			$dgClass->WriteFile($file, json_encode($arts));
			$dgClass->redirect('index.php/clipart');
		}
		else
		{
			$dgClass->redirect('index.php/clipart');
		}
	}

	// delete arts
	public function delete()
	{
		$dgClass 				= new dg();
		if (isset($_POST['ids']))
		{
			$ids = $_POST['ids'];

			$file 	= dirname(ROOT) .DS. 'data' .DS. 'arts.json';
			$arts 	= array();
			if (file_exists($file))
			{
				$str 	= file_get_contents($file);
				$rows 	= json_decode($str);
				$n		= 0;
				if (isset($rows->arts) && count($rows->arts) > 0)
				{
					foreach($rows->arts as $row)
					{
						if (!in_array($row->clipart_id, $ids))
						{
							$arts[] = $row;
							$n++;
						}
					}
				}
				$content = new stdClass();
				$content->count = $n;
				$content->arts = $arts;
				$dgClass->WriteFile($file, json_encode($content));
			}
		}
		$dgClass->redirect('index.php/clipart');
	}

	// get list category
	public function categoriestree($return = true, $store = true)
	{
		$path = dirname(ROOT) .DS. 'data' .DS. 'categories_art.json';
		$dg = new dg();
		$setting = dirname(ROOT).DS.'data'.DS.'settings.json';
		if (file_exists($setting) && $store)
		{
			$setting 		= $dg->readFile($setting);
			$settings 		= json_decode($setting);
			if ( isset($settings->store) && isset($settings->store->enable) && $settings->store->enable == 1)
			{
				$path = dirname(ROOT) .DS. 'data' . DS . 'store' .DS. 'art_categories.json';
			}
		}
		$categories = array();
		if (file_exists($path))
		{
			$str	= file_get_contents($path);
			$categories = json_decode($str);
			if ($categories == false || $categories == null) {
				$categories = array();
			}

			if (count($categories) < 1) {
				$categories = json_decode($dg->readFile(dirname(ROOT) .DS. 'data' .DS. 'categories_art.json'));
				if ($categories == false || $categories == null) {
					$categories = array();
				}
			}

			if (count($categories) > 0)
			{
				$new = array();
				foreach ($categories as $a){
					if ($a->id == 0) continue;
					$new[$a->parent_id][] = $a;
				}
				if (isset($new[0]))
					$tree = $this->createTree($new, $new[0]);
				else
					$tree = $this->createTree($new, $new);

				$categories = $tree;
			}
		}
		$all 				= array();
		$all[0]				= new stdClass();
		$all[0]->id 		= 0;
		$all[0]->title 		= 'All Art';
		$all[0]->children 	= array();
		$all[0]->parent_id 	= 0;


		$categories = array_merge($all, $categories);

		if ($return === true)
		{
			return $categories;

		}
		else
		{
			echo json_encode($categories);
			exit();
		}
	}

	public function createTree(&$list, $parent){
		$tree = array();
		foreach ($parent as $k=>$l)
		{
			if( isset($l->children) && count($l->children) > 0 )
			{
				$l->isFolder = 1;
			}
			$tree[] = $l;
		}
		return $tree;
	}

	public function editCategory($id = 0)
	{
		$data = array();

		$data['id'] 	= $id;

		$file = dirname(ROOT) .DS. 'data' .DS. 'categories_art.json';
		$categories		= array();

		$data['category'] = array(
			'id' => 0,
			'title' => '',
			'parent_id' => 0
		);
		if (file_exists($file))
		{
			$str 		= file_get_contents($file);
			$array 		= json_decode($str);
			if (count($array))
			{
				foreach($array as $category)
				{
					if ($category->parent_id == 0)
					{
						$categories[] = $category;
					}
				}
				if ($id > 0)
				{
					foreach($array as $category)
					{
						if ($category->id == $id)
						{
							$data['category'] =  array(
								'id' => $category->id,
								'title' => $category->title,
								'parent_id' => $category->parent_id
							);
							break;
						}
					}
				}
			}
		}
		$data['categories']	= $categories;

		$this->modal('category', $data);
	}

	// save category
	public function saveCategory()
	{
		$dgClass 				= new dg();
		if (!empty($_POST))
		{
			$id 		= $_POST['id'];
			$title 		= $_POST['title'];
			$parent_id 	= $_POST['parent_id'];

			// get categories
			$file = dirname(ROOT) .DS. 'data' .DS. 'categories_art.json';
			$categories		= array();
			$categories[0]	= new stdClass();
			$categories[0]->id = 0;
			$categories[0]->title = 'All art';
			$categories[0]->parent_id = '0';

			if (file_exists($file))
			{
				$str 		= file_get_contents($file);
				$categories = json_decode($str);
			}

			if ($parent_id == 0)
				$level = 1;
			else
				$level = 2;

			if ($id == 0)
			{
				// add new category
				$index = 0;
				foreach($categories as $category)
				{
					if ($category->id > $index)
						$index = $category->id;
				}
				$index = $index + 1;

				$category = array(
					'id' => $index,
					'title' => $title,
					'parent_id' => $parent_id
				);
				$categories[] = $category;
			}
			else
			{
				// edit category
				$index = $id;
				$array = array();
				foreach($categories as $category)
				{
					if ($category->id == $index)
					{
						$array[] = array(
							'id' => $index,
							'title' => $title,
							'parent_id' => $parent_id
						);
					}
					else
					{
						$array[] = $category;
					}
				}
				$categories = $array;
			}

			$check = $dgClass->WriteFile($file, json_encode($categories));
			echo 1;
			exit();
		}
		else
		{
			$dgClass->redirect('index.php/clipart');
		}
	}

	// delete category
	public function deleteCategory($id)
	{
		$file 		= dirname(ROOT) .DS. 'data' .DS. 'categories_art.json';
		$str 		= file_get_contents($file);
		$categories = json_decode($str);
		$array = array();
		foreach($categories as $category)
		{
			if ($category->id != $id)
			{
				$array[] = $category;
			}
		}
		$categories = $array;

		$dgClass 				= new dg();
		$check = $dgClass->WriteFile($file, json_encode($categories));
		echo $check;
		exit();
	}

	public function export()
	{
		$zipname = dirname(ROOT).DS.'export_cliparts.zip';
		if (file_exists($zipname)) {
			unlink($zipname);
		}

		$json = array('error' => 0, 'message' => 'Export success', 'link' => '');

		$dir_data = dirname(ROOT).DS.'data'.DS;
		$files = array(
			$dir_data.'arts.json',
			$dir_data.'categories_art.json'
		);

		// check store valid
		$dgClass = new dg();
		$settings = $dgClass->getSetting();
		if (isset($settings->store) && isset($settings->store->enable) && $settings->store->enable == 1) {
			$dir_data_store = $dir_data.'store'.DS;
			if (file_exists($dir_data_store.'art_categories.json')) {
				$files[] = $dir_data_store.'art_categories.json';
			}
			if (file_exists($dir_data_store.'arts_info.json')) {
				$files[] = $dir_data_store.'arts_info.json';
			}
			if (file_exists($dir_data_store.'arts.json')) {
				$files[] = $dir_data_store.'arts.json';
			}
		}

		$zip = new ZipArchive;
		$zip->open($zipname, ZipArchive::CREATE);
		foreach ($files as $file) {
		  $zip->addFile($file);
		}
		$zip->close();

		$json['link'] = site_url('export_cliparts.zip');
		$json['link'] = str_replace('/tshirtecommerce/admin/', '/tshirtecommerce/', $json['link']);

		echo json_encode($json);
		return;
	}
}

?>
