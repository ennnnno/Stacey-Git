<?php
Class Stacey
		{
				static $version = '2.3.0';
				var $route;
				function handle_redirects()
						{
								if (preg_match('/^\/?(index|app)\/?$/', $_SERVER['REQUEST_URI']))
										{
												header('HTTP/1.1 301 Moved Permanently');
												header('Location: ../');
												return true;
										}
								if (!preg_match('/\/$/', $_SERVER['REQUEST_URI']) && !preg_match('/\./', $_SERVER['REQUEST_URI']))
										{
												header('HTTP/1.1 301 Moved Permanently');
												header('Location:' . $_SERVER['REQUEST_URI'] . '/');
												return true;
										}
								return false;
						}
				function php_fixes()
						{
								if (function_exists('date_default_timezone_set'))
												date_default_timezone_set('Australia/Melbourne');
						}
				function set_content_type($template_file)
						{
								preg_match('/\.([\w\d]+?)$/', $template_file, $split_path);
								switch ($split_path[1])
								{
												case 'txt':
																header("Content-type: text/plain; charset=utf-8");
																break;
												case 'atom':
																header("Content-type: application/atom+xml; charset=utf-8");
																break;
												case 'rss':
																header("Content-type: application/rss+xml; charset=utf-8");
																break;
												case 'rdf':
																header("Content-type: application/rdf+xml; charset=utf-8");
																break;
												case 'xml':
																header("Content-type: text/xml; charset=utf-8");
																break;
												case 'json':
																header('Content-type: application/json; charset=utf-8');
																break;
												case 'css':
																header('Content-type: text/css; charset=utf-8');
																break;
												default:
																header("Content-type: text/html; charset=utf-8");
								}
						}
				function etag_expired($cache)
						{
								header('Etag: "' . $cache->hash . '"');
								if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) == '"' . $cache->hash . '"')
										{
												header("HTTP/1.0 304 Not Modified");
												header('Content-Length: 0');
												return false;
										}
								else
										{
												return true;
										}
						}
				function render($file_path, $template_file)
						{
								$cache = new Cache($file_path, $template_file);
								$this->set_content_type($template_file);
								if (!$this->etag_expired($cache))
												return;
								if ($cache->expired())
										{
												echo $cache->create($this->route);
										}
								else
										{
												echo $cache->render();
										}
						}
				function create_page($file_path)
						{
								if (!file_exists($file_path))
												throw new Exception('404');
								global $current_page_file_path;
								$current_page_file_path = $file_path;
								global $current_page_template_file;
								$template_name              = Page::template_name($file_path);
								$current_page_template_file = Page::template_file($template_name);
								if (empty($template_name))
												throw new Exception('404');
								if (!$current_page_template_file)
										{
												throw new Exception('A template named \'' . $template_name . '\' could not be found in the \'/templates\' folder');
										}
								$this->render($file_path, $current_page_template_file);
						}
				function __construct($get)
						{
								$this->php_fixes();
								if ($this->handle_redirects())
												return;
								$key         = preg_replace(array(
												'/\/$/',
												'/^\//'
								), '', key($get));
								$this->route = isset($key) ? $key : 'index';
								$file_path   = Helpers::url_to_file_path($this->route);
								try
										{
												$this->create_page($file_path);
										}
								catch (Exception $e)
										{
												if ($e->getMessage() == "404")
														{
																header('HTTP/1.0 404 Not Found');
																if (file_exists('./content/404'))
																		{
																				$this->create_page('./content/404', '404');
																		}
																else if (file_exists('./public/404.html'))
																		{
																				echo file_get_contents('./public/404.html');
																		}
																else
																		{
																				echo '<h1>404</h1><h2>Page could not be found.</h2><p>Unfortunately, the page you were looking for does not exist here.</p>';
																		}
														}
												else
														{
																echo '<h3>' . $e->getMessage() . '</h3>';
														}
										}
						}
		}
?>