<?

class reflex_task_handler implements mod_handler {

	private static $timeout = 2;

	public function on_mod_cron() {
	    $start = microtime(true);
	    while(microtime(true) - $start < self::$timeout) {
			self::execOne();
			reflex::storeAll();
		}
	}
	
   /* public function where() {

        $q = trim($this->data("query"));

        if(!$q) {
            return 1;
		}

        if(($q*1).""==$q."") {
            return " `id`='$q' ";
		}

        return $q;
    } */

    /**
     * Возвращает список задач, которые уже могут быть выполнены
     **/
    public static function tasksToLaunch() {
        return reflex_task::all()
            ->leq("nextLaunch",util::now())
            ->eq("completed",0);
    }
	
    /**
     * Выполняет одно задание
     **/
    public static function execOne() {

        $tasks = self::tasksToLaunch();
        $total = $tasks->count();

        if($total==0) {
            return;
        }

        // $n - хранится в кэше и увеличивается на 1 с каждым запуском крона
        $n = mod_cache::get("01h1b4yw6kbz2l9y6orj");
        if(!$n) {
            $n = 0;
        }

        // Выбираем задачу в зависимости от $n
        // Т.о. каждый на запуск крона задачи будут поочередно вызываны
        $task = $tasks->limit(1)->page($n%$total+1)->one();

        mod_cache::set("01h1b4yw6kbz2l9y6orj",$n+1);

        $task->exec();
    }

}
