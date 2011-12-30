<?php
Class TemplateParser
		{
				static $partials;
				static function collate_partials($dir = './templates/partials')
						{
								foreach (Helpers::file_cache($dir) as $file)
										{
												if ($file['is_folder'])
														{
																self::collate_partials($file['path']);
														}
												else
														{
																self::$partials[] = $file['path'];
														}
										}
						}
				static function get_partial_template($name)
						{
								if (!self::$partials)
												self::collate_partials();
								foreach (self::$partials as $partial)
										{
												if (preg_match('/([^\/]+?)\.[\w]+?$/', $partial, $file_name))
														{
																if ($file_name[1] == $name)
																		{
																				ob_start();
																				include $partial;
																				$ob_contents = ob_get_contents();
																				ob_end_clean();
																				return $ob_contents;
																		}
														}
										}
								return 'Partial \'' . $name . '\' not found';
						}
				static function test_nested_matches($template_parts, $opening, $closing)
						{
								preg_match_all('/' . $opening . '/', $template_parts[count($template_parts) - 2], $opening_matches);
								$closing_count = count($opening_matches[0]);
								if ($closing_count > 0)
										{
												$template_parts = self::expand_match($closing_count, $template_parts, $opening, $closing);
										}
								return $template_parts;
						}
				static function expand_match($closing_count, $template_parts, $opening, $closing)
						{
								preg_match('/(' . $opening . '[\S\s]*?(' . $closing . ')([\S\s]+?\\2){' . $closing_count . ',' . $closing_count . '})([\S\s]*)/', $template_parts[0], $matches);
								$matches[1]                                 = preg_replace(array(
												'/^' . $opening . '\s+?/',
												'/\s+?' . $closing . '$/'
								), '', $matches[1]);
								$template_parts[count($template_parts) - 1] = $matches[4];
								$template_parts[count($template_parts) - 2] = $matches[1];
								return $template_parts;
						}
				static function parse($data, $template)
						{
								if (preg_match('/get[\s]+?["\']\/?(.*?)\/?["\']\s+?do\s+?([\S\s]+?)end(?!\w)/', $template))
										{
												$template = self::parse_get($data, $template);
										}
								if (preg_match('/foreach[\s]+?([\$\@].+?)\s+?do\s+?([\S\s]+)endforeach/', $template))
										{
												$template = self::parse_foreach($data, $template);
										}
								if (preg_match('/if\s*?(!)?\s*?([\$\@].+?)\s+?do\s+?([\S\s]+?)endif/', $template))
										{
												$template = self::parse_if($data, $template);
										}
								if (preg_match('/[\b\s>]:([\w\d_\-]+)\b/', $template))
										{
												$template = self::parse_includes($data, $template);
										}
								if (preg_match('/\@[\w\d_\-]+?/', $template))
										{
												$template = self::parse_vars($data, $template);
										}
								$template = str_replace("\x01", '@', $template);
								$template = str_replace("\x02", '$', $template);
								return $template;
						}
				static function parse_get(&$data, $template)
						{
								preg_match('/([\S\s]*?)get[\s]+?["\']\/?(.*?)\/?["\']\s+?do\s+?([\S\s]+?)end\b([\S\s]*)$/', $template, $template_parts);
								$template     = self::parse($data, $template_parts[1]);
								$file_path    = Helpers::url_to_file_path($template_parts[2]);
								$current_data = $data;
								if (file_exists($file_path))
										{
												$template_parts = self::test_nested_matches($template_parts, 'get[\s]+?["\']\/?.*?\/?["\']\s+?do', 'end\b');
												$data           = AssetFactory::get($file_path);
												$template .= self::parse($data, $template_parts[3]);
										}
								$data = $current_data;
								$template .= self::parse($data, $template_parts[4]);
								return $template;
						}
				static function parse_foreach($data, $template)
						{
								preg_match('/([\S\s]*?)foreach[\s]+?([\$\@].+?)\s+?do\s+?([\S\s]+?)endforeach([\S\s]*)$/', $template, $template_parts);
								$template = self::parse($data, $template_parts[1]);
								if (preg_match('/\[\d*:\d*\]$/', $template_parts[2]))
										{
												preg_match('/([\$\@].+?)\[(\d*):(\d*)\]$/', $template_parts[2], $matches);
												$template_parts[2] = $matches[1];
												$start_limit       = empty($matches[2]) ? 0 : $matches[2];
												if (!empty($matches[3]))
																$end_limit = $matches[3];
										}
								$pages = (isset($data[$template_parts[2]]) && is_array($data[$template_parts[2]]) && !empty($data[$template_parts[2]])) ? $data[$template_parts[2]] : false;
								if (is_array($pages) && isset($start_limit))
										{
												$pages = array_slice($pages, $start_limit, $end_limit);
										}
								$template_parts = self::test_nested_matches($template_parts, 'foreach[\s]+?[\$\@].+?\s+?do\s+?', 'endforeach');
								if ($pages)
										{
												foreach ($pages as $data_item)
														{
																$data_object =& AssetFactory::get($data_item);
																$template .= self::parse($data_object, $template_parts[3]);
														}
										}
								$template .= self::parse($data, $template_parts[4]);
								return $template;
						}
				static function parse_if($data, $template)
						{
								preg_match('/([\S\s]*?)if\s*?(!)?\s*?([\$\@].+?)\s+?do\s+?([\S\s]+?)endif([\S\s]*)$/', $template, $template_parts);
								$template       = self::parse($data, $template_parts[1]);
								$template_parts = self::test_nested_matches($template_parts, 'if\s*?!?\s*?[\$\@].+?\s+?do\s+?', 'endif');
								if ($template_parts[2])
										{
												if (!isset($data[$template_parts[3]]) || (empty($data[$template_parts[3]]) || !$data[$template_parts[3]]))
														{
																$template .= self::parse($data, $template_parts[4]);
														}
										}
								else
										{
												if (isset($data[$template_parts[3]]) && !empty($data[$template_parts[3]]) && ($data[$template_parts[3]]))
														{
																$template .= self::parse($data, $template_parts[4]);
														}
										}
								$template .= self::parse($data, $template_parts[5]);
								return $template;
						}
				static function parse_includes($data, $template)
						{
								preg_match('/([\S\s]*?)(?<![a-z0-9]):([\w\d_\-]+)\b([\S\s]*)$/', $template, $template_parts);
								$template       = self::parse($data, $template_parts[1]);
								$inner_template = self::get_partial_template($template_parts[2]);
								$template .= self::parse($data, $inner_template);
								$template .= self::parse($data, $template_parts[3]);
								return $template;
						}
				static function parse_vars($data, $template)
						{
								foreach ($data as $key => $value)
										{
												$var = ($key == '@root_path') ? $key . '\/?' : $key;
												if (is_string($value) && strlen($var) > 1)
																$template = preg_replace('/' . $var . '/', $value, $template);
										}
								$template = str_replace('@', "\x01", $template);
								return $template;
						}
		}
?>