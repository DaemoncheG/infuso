<?

class board_task extends reflex {

	public static function all() {
	    return reflex::get(get_class())
	        ->desc("priority")
	        ->joinByField("projectID")
	        ->neq("board_project.id",0);
	}

	public static function visible() {
	    $list = self::all();
	    $projects = board_project::visible()->limit(0)->idList();
	    $list->eq("projectID",$projects);
	    return $list;
	}

	public function reflex_beforeStorageChange() {
	    return board_security::test("board:upload");
	}

	public function reflex_beforeStorageView() {
	    return user::active()->checkAccess("board:upload");
	}

	public static function get($id) { return reflex::get(get_class(),$id); }

	public static function statusList() {
	    return array(
	        "0" => "Не готово",
	        "1" => "Готово",
	    );
	}

	public function project() { return $this->pdata("projectID"); }
	public function reflex_parent() { return $this->project(); }

	public static function reflex_root() {
	    return self::all()->title("Все задачи")->param("tab","system");
	}

	public function reflex_children() {
	    return array(
	        $this->getLogCustom()->title("Затраченное время"),
	    );
	}

	public function reflex_title() {
	    return util::str($this->data("text"))->ellipsis(50)."";
	}

	public function text() { return $this->data("text"); }

	public function responsibleUser() { return $this->pdata("responsibleUser"); }

	// ----------------------------------------------------------------------- Триггеры

	public function reflex_beforeCreate() {
	    $this->data("changed",util::now());
	    $this->data("created",util::now());
	}

	public function reflex_afterCreate() {
	    $this->log("Создано");
	}

	public function reflex_beforeStore() {

	    // Устанавливаем новую дату изменения только если задача активна
	    // Иначе мы можем влезть в статистику по прошлому периоду
	    if($this->field("status")->changed())
	        if($this->status()->active())
	            $this->data("changed",util::now());
	}

	public function updateTimeSpent() {
	    $this->data("timeSpent",$this->getLogCustom()->sum("timeSpent"));
	}

	public function reflex_repair() {
	    $this->updateTImeSpent();
	}

	// ----------------------------------------------------------------------------- Затраченное время

	public function getLogCustom() { return board_task_log::all()->eq("taskID",$this->id()); }
	public function logCustom($text,$time=0) {
	    $this->getLogCustom()->create(array(
	        "taskID" => $this->id(),
	        "text" => $text,
	        "timeSpent" => $time,
	    ));
	}

	public function timeSpent() { return $this->data("timeSpent"); }
	public function timeSceduled() { return $this->data("timeSceduled"); }

	// ----------------------------------------------------------------------------- Статус

	public function status() {
	    return board_task_status::get($this->data("status"));
	}

	// ----------------------------------------------------------------------------- Стикер

	public function hangDays() {
	    return round((util::now()->stamp() - $this->pdata("changed")->stamp())/60/60/24);
	}

	public function stickerData($p=array()) {

	    $ret = array();

	    // Текст стикера
	    $ret["text"] = "<b>{$this->id()}.</b> ";

	    // Сколько задача висит в этом статусе
	    if($this->status()->active()) {
	        $d = (util::now()->stamp() - $this->pdata("changed")->stamp())/60/60/24;
	        $d = round($d);
	        if($d>=3)
	            $data["text"].= "<span style='background:red;color:white;display:inline-block;padding:0px 4px;' >$d</span> ";
	    }

	    // Бонусные задачи
	    if($this->data("bonus"))
	        $ret["text"].= "<span style='color:white;background:green;font-style:italic;padding:0px 4px;'>б</span> ";

	    // Просрочка
	    if($p["showHang"]) {
	        $h = $this->hangDays();
	        if($h>3)
	            $ret["text"].= "<span style='color:white;background:red;padding:0px 4px;'>$h</span> ";
	    }

	    if($p["showProject"])
	        $ret["text"].= "<b>".$this->project()->title().".</b> ";
	    $ret["text"].= util::str($this->data("text"))->ellipsis(200)->secure()."";

	    // Статусная часть стикера
	    $ret["info"] = "";
	    $ret["info"].= $this->timeSpent()."/".$this->timeSceduled()."ч. ";

	    // Цвет стикера
	    $ret["color"] = $this->data("color");

	    // Нижня подпись
	    if($this->responsibleUser()->exists())
	        $ret["bottom"] = "<nobr>".$this->responsibleUser()->title()."</nobr> ";

	    if($this->data("deadline"))
	        $ret["bottom"].= $this->pdata("deadlineDate")->left();

	    $ret["my"] = $this->responsibleUser()->id() == user::active()->id();

	    $ret["id"] = $this->id();

	    $ret["deadline"] = !!$this->data("deadline");
	    $ret["deadlineDate"] = $this->data("deadlineDate");

	    $d = util::now()->stamp() - $this->pdata("deadlineDate")->stamp();
	    $ret["fuckup"] = $ret["deadline"] && $d>0;

	    $ret["projectID"] = $this->project()->id();

	    $ret["sort"] = $this->status()->stickerParam("sort");
	    if($p["sort"]===false || $p["sort"]===0)
	        $ret["sort"] = false;

	    return $ret;
	}

	public static function postTest() {
		return true;
	}
	
	public static function post_send($p) {
	    $text = util::str($p["text"])->secure()->trim()."";
	    if(!$text) return;
	    reflex::create(get_class(),array(
	        "text" => $text,
	    ));
	    header("location:./");
	}

	public static function post_setStatus($p) {
	    self::get($p["taskID"])->setStatus($p["status"]);
	}

	public function setStatus($status) {
	    $list = self::statusList();
	    $statusName = $list[$status];
	    $this->data("status",$status);
	    $this->log($statusName);
	}
	
}
