<?php
Class AssetFactory
		{
				static $store;
				static $asset_subclasses = array();
				static function &create($file_path)
						{
								self::get_asset_subclasses();
								$data = array();
								if (!is_string($file_path))
												return $data;
								preg_match('/\.([\w\d]+?)$/', $file_path, $split_path);
								if (isset($split_path[1]) && !is_dir($file_path))
										{
												$asset = 'Asset';
												foreach (self::$asset_subclasses as $asset_type => $identifiers)
														{
																if (in_array(strtolower($split_path[1]), $identifiers))
																				$asset = $asset_type;
														}
												$asset = new $asset($file_path);
												return $asset->data;
										}
								else
										{
												$page = new Page(Helpers::file_path_to_url($file_path));
												return $page->data;
										}
						}
				static function &get($key)
						{
								if (!isset(self::$store[$key]))
												self::$store[$key] =& self::create($key);
								return self::$store[$key];
						}
				static function get_asset_subclasses()
						{
								if (empty(self::$asset_subclasses))
										{
												foreach (get_declared_classes() as $class)
														{
																if (strtolower(get_parent_class($class)) == 'asset')
																				self::$asset_subclasses[$class] = eval('return ' . $class . '::$identifiers;');
														}
										}
						}
		}
?>