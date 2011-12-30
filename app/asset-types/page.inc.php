<?php
Class Page
		{
				var $url_path;
				var $file_path;
				var $template_name;
				var $template_file;
				var $template_type;
				var $data;
				var $all_pages;
				function __construct($url)
						{
								$this->file_path     = Helpers::url_to_file_path($url);
								$this->url_path      = $url;
								$this->template_name = self::template_name($this->file_path);
								$this->template_file = self::template_file($this->template_name);
								$this->template_type = self::template_type($this->template_file);
								PageData::create($this);
								uksort($this->data, array(
												'Helpers',
												'sort_by_length'
								));
						}
				function parse_template()
						{
								$data = TemplateParser::parse($this->data, file_get_contents($this->template_file));
								if (strtolower($this->template_type) == 'json')
										{
												$data = json_minify($data);
												$data = preg_replace('/([}\]"]),([}\]])/', '$1$2', $data);
												$data = preg_replace('/([}\]"]),([}\]])/', '$1$2', $data);
										}
								return $data;
						}
				function __set($name, $value)
						{
								$prefix                                  = is_array($value) ? '$' : '@';
								$this->data[$prefix . strtolower($name)] = $value;
						}
				static function template_type($template_file)
						{
								preg_match('/\.([\w\d]+?)$/', $template_file, $ext);
								return isset($ext[1]) ? $ext[1] : false;
						}
				static function template_name($file_path)
						{
								$txts = array_keys(Helpers::list_files($file_path, '/\.txt$/'));
								return (!empty($txts)) ? preg_replace('/([^.]*\.)?([^.]*)\.txt$/', '\\2', $txts[0]) : false;
						}
				static function template_file($template_name)
						{
								$template_file = glob('./templates/' . $template_name . '.*');
								return isset($template_file[0]) ? $template_file[0] : false;
						}
		}
?>