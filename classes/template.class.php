<?php
    class Template {
        protected $templateDir = __DIR__ . '/../views/';
        public $template = null;
        public $copyTemplate = null;
        private $vars = array();
        private $blocks = array();
        public $result = array ();

        public function __construct($template_dir = null) {
            if ($template_dir !== null) {
                $this->templateDir = $template_dir;
            }
        }

        public function render($template_file) {
            if (file_exists($this->templateDir.$template_file)) {
                include_once $this->templateDir.$template_file;
            } else {
                throw new Exception('No template file ' . $template_file . ' present in directory ' . $this->templateDir);
            }
        }

        public function __set($name, $value) {
            $this->vars[$name] = $value;
        }

        public function __get($name) {
            return $this->vars[$name];
        }

        public function set($name, $var) {
            if(is_array($var)) {
                if(count($var)) {
                    foreach($var as $key => $key_var) {
                        $this->set($key, $key_var);
                    }
                }
                return;
            }
            
            $var = str_replace(array("{", "["),array("_&#123;_", "_&#91;_"), $var);
                
            $this->vars[$name] = $var;
        }

        public function setBlock($name, $var) {
            if(is_array($var)) {
                if(count($var)) {
                    foreach($var as $key => $key_var) {
                        $this->setBlock($key, $key_var);
                    }
                }
                return;
            }
            
            $var = str_replace(array("{", "["),array("_&#123;_", "_&#91;_"), $var);
                
            $this->blocks[$name] = $var;
        }

        public function templ($tpl_name) {
            $tpl_name = str_replace(chr(0), '', (string)$tpl_name);
    
            $file_path = dirname($tpl_name);
            
            $url = parse_url($tpl_name);
            $tpl_name = pathinfo($url['path']);
            $tpl_name = $tpl_name['basename'];
            $type = explode( ".", $tpl_name );
            $type = strtolower(end($type));

            if ($type != "html") {
                $this->template = "Not Allowed Template Name: " .str_replace(ROOT_DIR, '', $this->templateDir)."/".$tpl_name ;
                $this->copy_template = $this->template;
                return "";
            }
    
            if($file_path && $file_path != ".") $tpl_name = $file_path."/".$tpl_name;
    
            if($tpl_name == '' || !file_exists($this->templateDir . $tpl_name)) {
                throw new Exception("Template not found: " . str_replace(ROOT_DIR, '', $this->templateDir) . $tpl_name) ;
            }
    
            $this->template = file_get_contents( $this->templateDir . $tpl_name );
            
            if(strpos($this->template, "{*") !== false) {
                $this->template = preg_replace("'\\{\\*(.*?)\\*\\}'si", '', $this->template);
            }
    
            $this->copyTemplate = $this->template;
            
            return true;
        }

        public function compile($tpl) {
            $find = $find_preg = $replace = $replace_preg = array();

            if(count($this->blocks)) {
                foreach ($this->blocks as $key_find => $key_replace) {
                    $find_preg[] = $key_find;
                    $replace_preg[] = $key_replace;
                }
                $this->copyTemplate = preg_replace($find_preg, $replace_preg, $this->copyTemplate);
            }

            foreach($this->vars as $key_find => $key_replace) {
                $find[] = stripos($key_find, '[') === false ? '{' . $key_find . '}' : $key_find;
                $replace[] = $key_replace;
            }
            
            $this->copyTemplate = str_ireplace($find, $replace, $this->copyTemplate);

            if(isset($this->result[$tpl])) $this->result[$tpl] .= $this->copyTemplate;
            else $this->result[$tpl] = $this->copyTemplate;
            
            $this->clear();
        }

        private function clear() {
            $this->vars = array();
            $this->blocks = array();
            $this->copyTemplate = $this->template;
        }
    }
