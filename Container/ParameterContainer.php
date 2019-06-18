<?php

/*
 * This file is part of the kalamu/dynamique-config-bundle package.
 *
 * (c) ETIC Services
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kalamu\DynamiqueConfigBundle\Container;

use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Container that handle dynamque parameters
 */
class ParameterContainer implements CacheClearerInterface, CacheWarmerInterface
{

    /**
     * Cach directory
     * @var string
     */
    protected $cache_dir;

    /**
     * Name of the config filer
     * @var string
     */
    protected $php_filename = '/dynamique_config.php';

    /**
     * @var boolean
     */
    protected $debug;

    /**
     * Format file
     * @var string
     */
    protected $yaml;

    /**
     * Table of the configuration options
     * @var array
     */
    protected $config;

    protected $accessor;

    public function __construct($cache_dir, $debug, $yaml){
        $this->cache_dir = $cache_dir;
        $this->debug = $debug;
        $this->yaml = $yaml;
        $this->accessor = PropertyAccess::createPropertyAccessor();

        $this->load();
    }

    /**
     * Called on clear:cache
     * @param type $cacheDir
     */
    public function clear($cacheDir) {
        if(is_file($cacheDir.$this->php_filename)){
            unlink($cacheDir.$this->php_filename);
        }
    }

    /**
     * Called on cache warm up
     * @param type $cacheDir
     */
    public function warmUp($cacheDir) {
        file_put_contents($cacheDir.$this->php_filename, $this->compileAsPhp());
    }

    /**
     * Required for warmup
     * @return boolean
     */
    public function isOptional() {
        return true;
    }



    /**
     * Check if the parameter exists
     * @param string $key
     * @return bool
     */
    public function has($key){
        if(array_key_exists($key, $this->config)){
            return true;
        }

        if($this->accessor->isReadable((object) $this->config, $key)){
            return true;
        }
        return false;
    }

    /**
     * Get the parameter
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null){
        if($this->has($key)){
            return array_key_exists($key, $this->config) ? $this->config[$key] : $this->accessor->getValue((object) $this->config, $key);
        }

        return $default;
    }

    /**
     * Set the parameter
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value){
        if(in_array(gettype($value), array('ressource', 'object'))){
            throw new \InvalidArgumentException(sprintf("Il  n'est pas possible de définir un paramètre de type %s", gettype($value)));
        }
        $this->config[strval($key)] = $value;
        $this->save();
    }

    /**
     * Remove the parameter
     * @param string $key
     */
    public function remove($key){
        unset($this->config[$key]);
        $this->save();
    }





    /**
     * Load configuration from the cache
     * @return type
     */
    protected function load(){
        if($this->debug && is_file($this->yaml)){
            $this->config = Yaml::parse(file_get_contents($this->yaml));
            return;
        }

        if(!$this->debug){
            if(is_file($this->cache_dir.$this->php_filename)){
                $this->config = require $this->cache_dir.$this->php_filename;
                return ;
            }
            if(is_file($this->yaml)){
                $this->config = Yaml::parse(file_get_contents($this->yaml));
                $this->warmUp($this->cache_dir);
                return ;
            }
        }

        $this->config = array();
    }

    protected function save(){
        $dumper = new Dumper();

        file_put_contents($this->yaml, $dumper->dump($this->config, 2));
        $this->warmUp($this->cache_dir);
    }


    /**
     * Compile configuration in plain PHP
     * @return string
     */
    protected function compileAsPhp(){
        $config_file = "<?php \$config = array();\n";
        foreach($this->config as $name => $value){
            switch (gettype($value)){
                case 'boolean':
                    $str = $value ? 'true' : 'false';
                    break;
                case 'NULL':
                    $str = 'null';
                    break;
                case 'integer':
                case 'double':
                case 'float':
                    $str = $value;
                    break;
                case 'string':
                    $str = $this->escapeString($value);
                    break;
                default:
                    $str = 'unserialize(stripslashes("'. str_replace('\\\\', '\\\\\\\\',  addslashes(serialize($value))).'"))';
            }
            $config_file .= sprintf('$config['.$this->escapeString($name).'] = %s;', $str)."\n";
        }
        $config_file .= 'return $config;';

        return $config_file;
    }

    /**
     * Escape string for PHP
     * @param type $string
     * @return type
     */
    protected function escapeString($string){
        if(false !== strpos($string, '\\') || false !== strpos($string, '"') || false !== strpos($string, '\'')){
            return 'stripslashes("'.str_replace('\\\\', '\\\\\\\\', addslashes($string)).'")';
        }else{
            return '"'.$string.'"';
        }
    }
}