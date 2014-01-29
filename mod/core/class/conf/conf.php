<?

namespace infuso\core;

/**
 * Класс для загрузки конфигурации
 **/
class conf extends component {

	private static $path = "/conf/conf.xml";
	
	private static $generalConf = null;

	private static $cache = null;

	public function clearCache() {
		self::$cache = array();
	}

	public function path() {
		return self::$path;
	}
	
	/**
	 * Возвращает параметр из общей конфигурации components.yml
	 **/
	public function general() {
	
        // Если в буфере нет конфигурации - загружаем ее
        if(self::$generalConf===null) {

            $reader = new \mod_confLoader_yaml();
            $yml = file::get(mod::app()->confPath()."/components.yml")->data();
            self::$generalConf = $reader->read($yml);

            if(!self::$generalConf) {
                self::$generalConf = array();
			}
        }

        // Если переданы параметры - извлекаем значения по ключам
        $ret = self::$generalConf;
        foreach(func_get_args() as $key) {
            $ret = $ret[$key];
        }

		return $ret;
	
	}

}
