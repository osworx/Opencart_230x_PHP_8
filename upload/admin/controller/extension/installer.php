<?php
class ControllerExtensionInstaller extends Controller {
	public function index(): void {
		$this->load->language('extension/installer');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/installer', 'token=' . $this->session->data['token'], true)
		);

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_upload'] = $this->language->get('text_upload');
		$data['text_loading'] = $this->language->get('text_loading');

		$data['entry_upload'] = $this->language->get('entry_upload');
		$data['entry_overwrite'] = $this->language->get('entry_overwrite');
		$data['entry_progress'] = $this->language->get('entry_progress');

		$data['help_upload'] = $this->language->get('help_upload');

		$data['button_upload'] = $this->language->get('button_upload');
		$data['button_clear'] = $this->language->get('button_clear');
		$data['button_continue'] = $this->language->get('button_continue');

		$data['token'] = $this->session->data['token'];

		$directories = glob(DIR_UPLOAD . 'temp-*', GLOB_ONLYDIR);

		if ($directories) {
			$data['error_warning'] = $this->language->get('error_temporary');
		} else {
			$data['error_warning'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('extension/installer', $data));
	}

	public function upload(): void {
		$this->load->language('extension/installer');

		$json = array();

		// Check user has permission
		if (!$this->user->hasPermission('modify', 'extension/installer')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			if (!empty($this->request->files['file']['name'])) {
				if (substr($this->request->files['file']['name'], -10) != '.ocmod.zip' && substr($this->request->files['file']['name'], -10) != '.ocmod.xml') {
					$json['error'] = $this->language->get('error_filetype');
				}

				if ($this->request->files['file']['error'] != UPLOAD_ERR_OK) {
					$json['error'] = $this->language->get('error_upload_' . $this->request->files['file']['error']);
				}
			} else {
				$json['error'] = $this->language->get('error_upload');
			}
		}

		if (!$json) {
			// If no temp directory exists create it
			$path = 'temp-' . token(32);

			if (!is_dir(DIR_UPLOAD . $path)) {
				mkdir(DIR_UPLOAD . $path, 0777);
			}

			// Set the steps required for installation
			$json['step'] = array();
			$json['overwrite'] = array();

			if (strrchr($this->request->files['file']['name'], '.') == '.xml') {
				$file = DIR_UPLOAD . $path . '/install.xml';

				// If xml file copy it to the temporary directory
				move_uploaded_file($this->request->files['file']['tmp_name'], $file);

				if (file_exists($file)) {
					$json['step'][] = array(
						'text' => $this->language->get('text_xml'),
						'url'  => str_replace('&amp;', '&', $this->url->link('extension/installer/xml', 'token=' . $this->session->data['token'], true)),
						'path' => $path
					);

					// Clear temporary files
					$json['step'][] = array(
						'text' => $this->language->get('text_remove'),
						'url'  => str_replace('&amp;', '&', $this->url->link('extension/installer/remove', 'token=' . $this->session->data['token'], true)),
						'path' => $path
					);
				} else {
					$json['error'] = $this->language->get('error_file');
				}
			}

			// If zip file copy it to the temp directory
			if (strrchr($this->request->files['file']['name'], '.') == '.zip') {
				$file = DIR_UPLOAD . $path . '/upload.zip';

				move_uploaded_file($this->request->files['file']['tmp_name'], $file);

				if (file_exists($file)) {
					$zip = new \ZipArchive();

					if (defined('ZipArchive::RDONLY')) {
						$flag = \ZipArchive::RDONLY;
					} else {
						$flag = 16;
					}

					if ($zip->open($file, $flag)) {
						// Zip
						$json['step'][] = array(
							'text' => $this->language->get('text_unzip'),
							'url'  => str_replace('&amp;', '&', $this->url->link('extension/installer/unzip', 'token=' . $this->session->data['token'], true)),
							'path' => $path
						);

						// FTP
						$json['step'][] = array(
							'text' => $this->language->get('text_ftp'),
							'url'  => str_replace('&amp;', '&', $this->url->link('extension/installer/no_ftp', 'token=' . $this->session->data['token'], true)),
							'path' => $path
						);

						// Send make and array of actions to carry out
						for ($i = 0; $i < $zip->numFiles; $i++) {
							$zip_name = $zip->getNameIndex($i);

							// SQL
							if (substr($zip_name, 0, 11) == 'install.sql') {
								$json['step'][] = array(
									'text' => $this->language->get('text_sql'),
									'url'  => str_replace('&amp;', '&', $this->url->link('extension/installer/sql', 'token=' . $this->session->data['token'], true)),
									'path' => $path
								);
							}

							// XML
							if (substr($zip_name, 0, 11) == 'install.xml') {
								$json['step'][] = array(
									'text' => $this->language->get('text_xml'),
									'url'  => str_replace('&amp;', '&', $this->url->link('extension/installer/xml', 'token=' . $this->session->data['token'], true)),
									'path' => $path
								);
							}

							// PHP
							if (substr($zip_name, 0, 11) == 'install.php') {
								$json['step'][] = array(
									'text' => $this->language->get('text_php'),
									'url'  => str_replace('&amp;', '&', $this->url->link('extension/installer/php', 'token=' . $this->session->data['token'], true)),
									'path' => $path
								);
							}

							// Compare admin files
							$file = DIR_APPLICATION . substr($zip_name, 13);

							if (is_file($file) && substr($zip_name, 0, 13) == 'upload/admin/') {
								$json['overwrite'][] = substr($zip_name, 7);
							}

							// Compare catalog files
							$file = DIR_CATALOG . substr($zip_name, 15);

							if (is_file($file) && substr($zip_name, 0, 15) == 'upload/catalog/') {
								$json['overwrite'][] = substr($zip_name, 7);
							}

							// Compare image files
							$file = DIR_IMAGE . substr($zip_name, 13);

							if (is_file($file) && substr($zip_name, 0, 13) == 'upload/image/') {
								$json['overwrite'][] = substr($zip_name, 7);
							}

							// Compare system files
							$file = DIR_SYSTEM . substr($zip_name, 14);

							if (is_file($file) && substr($zip_name, 0, 14) == 'upload/system/') {
								$json['overwrite'][] = substr($zip_name, 7);
							}
						}

						// Clear temporary files
						$json['step'][] = array(
							'text' => $this->language->get('text_remove'),
							'url'  => str_replace('&amp;', '&', $this->url->link('extension/installer/remove', 'token=' . $this->session->data['token'], true)),
							'path' => $path
						);

						$zip->close();
					} else {
						$json['error'] = $this->language->get('error_unzip');
					}
				} else {
					$json['error'] = $this->language->get('error_file');
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function no_ftp(): void {
		$this->load->language('extension/installer');

		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/installer')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$directory = DIR_UPLOAD  . str_replace(array('../', '..\\', '..'), '', $this->request->post['path']) . '/upload/';

		if (!is_dir($directory)) {
			$json['error'] = $this->language->get('error_directory');
		}

		if (!$json) {
			// Get a list of files ready to upload
			$files = array();

			$path = array($directory . '*');

			while (count($path) != 0) {
				$next = array_shift($path);

				foreach (glob($next) as $file) {
					if (is_dir($file)) {
						$path[] = $file . '/*';
					}

					$files[] = $file;
				}
			}

			$root = dirname(DIR_APPLICATION).'/';

			foreach ($files as $file) {
				// Upload everything in the upload directory
				$destination = substr($file, strlen($directory));

				// Update from newer OpenCart versions:
				if (substr($destination, 0, 5) == 'admin') {
					$destination = DIR_APPLICATION . substr($destination, 5);
				} elseif (substr($destination, 0, 7) == 'catalog') {
					$destination = DIR_CATALOG . substr($destination, 7);
				} elseif (substr($destination, 0, 5) == 'image') {
					$destination = DIR_IMAGE . substr($destination, 5);
				} elseif (substr($destination, 0, 6) == 'system') {
					$destination = DIR_SYSTEM . substr($destination, 6);
				} else {
					$destination = $root.$destination;
				}

				if (is_dir($file)) {
					if (!file_exists($destination)) {
						if (!mkdir($destination)) {
							$json['error'] = sprintf($this->language->get('error_ftp_directory'), $destination);
						}
					}
				}

				if (is_file($file)) {
					if (!copy($file, $destination)) {
						$json['error'] = sprintf($this->language->get('error_ftp_file'), $file);
					}
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
 
	public function unzip(): void {
		$this->load->language('extension/installer');

		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/installer')) {
			$json['error'] = $this->language->get('error_permission');
		}

		// Sanitize the filename
		$file = DIR_UPLOAD . $this->request->post['path'] . '/upload.zip';

		if (!is_file($file) || substr(str_replace('\\', '/', realpath($file)), 0, strlen(DIR_UPLOAD)) != DIR_UPLOAD) {
			$json['error'] = $this->language->get('error_file');
		}

		if (!$json) {
			// Unzip the files
			$zip = new ZipArchive();

			if ($zip->open($file)) {
				$zip->extractTo(DIR_UPLOAD . $this->request->post['path']);
				$zip->close();
			} else {
				$json['error'] = $this->language->get('error_unzip');
			}

			// Remove Zip
			unlink($file);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function sql(): void {
		$this->load->language('extension/installer');

		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/installer')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$file = DIR_UPLOAD . $this->request->post['path'] . '/install.sql';

		if (!is_file($file) || substr(str_replace('\\', '/', realpath($file)), 0, strlen(DIR_UPLOAD)) != DIR_UPLOAD) {
			$json['error'] = $this->language->get('error_file');
		}

		if (!$json) {
			$lines = file($file);

			if ($lines) {
				try {
					$sql = '';

					foreach ($lines as $line) {
						if ($line && (substr($line, 0, 2) != '--') && (substr($line, 0, 1) != '#')) {
							$sql .= $line;

							if (preg_match('/;\s*$/', $line)) {
								$sql = str_replace(" `oc_", " `" . DB_PREFIX, $sql);

								$this->db->query($sql);

								$sql = '';
							}
						}
					}
				} catch(Exception $exception) {
					$json['error'] = sprintf($this->language->get('error_exception'), $exception->getCode(), $exception->getMessage(), $exception->getFile(), $exception->getLine());
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function xml(): void {
		$this->load->language('extension/installer');

		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/installer')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$file = DIR_UPLOAD . $this->request->post['path'] . '/install.xml';

		if (!is_file($file) || substr(str_replace('\\', '/', realpath($file)), 0, strlen(DIR_UPLOAD)) != DIR_UPLOAD) {
			$json['error'] = $this->language->get('error_file');
		}

		if (!$json) {
			$this->load->model('extension/modification');

			// If xml file just put it straight into the DB
			$xml = file_get_contents($file);

			if ($xml) {
				try {
					$dom = new DOMDocument('1.0', 'UTF-8');
					$dom->loadXml($xml);

					$name = $dom->getElementsByTagName('name')->item(0);

					if ($name) {
						$name = $name->nodeValue;
					} else {
						$name = '';
					}

					$code = $dom->getElementsByTagName('code')->item(0);

					if ($code) {
						$code = $code->nodeValue;

						// Check to see if the modification is already installed or not.
						$modification_info = $this->model_extension_modification->getModificationByCode($code);

						if ($modification_info) {
							$json['error'] = sprintf($this->language->get('error_exists'), $modification_info['name']);
						}
					} else {
						$json['error'] = $this->language->get('error_code');
					}

					$author = $dom->getElementsByTagName('author')->item(0);

					if ($author) {
						$author = $author->nodeValue;
					} else {
						$author = '';
					}

					$version = $dom->getElementsByTagName('version')->item(0);

					if ($version) {
						$version = $version->nodeValue;
					} else {
						$version = '';
					}

					$link = $dom->getElementsByTagName('link')->item(0);

					if ($link) {
						$link = $link->nodeValue;
					} else {
						$link = '';
					}

					$modification_data = array(
						'name'    => $name,
						'code'    => $code,
						'author'  => $author,
						'version' => $version,
						'link'    => $link,
						'xml'     => $xml,
						'status'  => 1
					);

					if (!$json) {
						$this->model_extension_modification->addModification($modification_data);
					}
				} catch(Exception $exception) {
					$json['error'] = sprintf($this->language->get('error_exception'), $exception->getCode(), $exception->getMessage(), $exception->getFile(), $exception->getLine());
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function php(): void {
		$this->load->language('extension/installer');

		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/installer')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$file = DIR_UPLOAD . $this->request->post['path'] . '/install.php';

		if (!is_file($file) || substr(str_replace('\\', '/', realpath($file)), 0, strlen(DIR_UPLOAD)) != DIR_UPLOAD) {
			$json['error'] = $this->language->get('error_file');
		}

		if (!$json) {
			try {
				include($file);
			} catch(Exception $exception) {
				$json['error'] = sprintf($this->language->get('error_exception'), $exception->getCode(), $exception->getMessage(), $exception->getFile(), $exception->getLine());
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function remove(): void {
		$this->load->language('extension/installer');

		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/installer')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$directory = DIR_UPLOAD . $this->request->post['path'];
		
		if (!is_dir($directory) || substr(str_replace('\\', '/', realpath($directory)), 0, strlen(DIR_UPLOAD)) != DIR_UPLOAD) {
			$json['error'] = $this->language->get('error_directory');
		}

		if (!$json) {
			// Get a list of files ready to upload
			$files = array();

			$path = array($directory);

			while (count($path) != 0) {
				$next = array_shift($path);

				// We have to use scandir function because glob will not pick up dot files.
				foreach (array_diff(scandir($next), array('.', '..')) as $file) {
					$file = $next . '/' . $file;

					if (is_dir($file)) {
						$path[] = $file;
					}

					$files[] = $file;
				}
			}

			rsort($files);

			foreach ($files as $file) {
				if (is_file($file)) {
					unlink($file);

				} elseif (is_dir($file)) {
					rmdir($file);
				}
			}

			if (file_exists($directory)) {
				rmdir($directory);
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function clear(): void {
		$this->load->language('extension/installer');

		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/installer')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$directories = glob(DIR_UPLOAD . 'temp-*', GLOB_ONLYDIR);

			if ($directories) {
				foreach ($directories as $directory) {
					// Get a list of files ready to upload
					$files = array();

					$path = array($directory);

					while (count($path) != 0) {
						$next = array_shift($path);

						// We have to use scandir function because glob will not pick up dot files.
						foreach (array_diff(scandir($next), array('.', '..')) as $file) {
							$file = $next . '/' . $file;

							if (is_dir($file)) {
								$path[] = $file;
							}

							$files[] = $file;
						}
					}

					rsort($files);

					foreach ($files as $file) {
						if (is_file($file)) {
							unlink($file);
						} elseif (is_dir($file)) {
							rmdir($file);
						}
					}

					if (file_exists($directory)) {
						rmdir($directory);
					}
				}
			}

			$json['success'] = $this->language->get('text_clear');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}