<?

class board_controller_task extends mod_controller {

    public function postTest() {
        return user::active()->exists();
    }

    /**
     * Экшн получения списка задач
     **/             
    public static function post_listTasks($p) {

        mod_profiler::beginOperation("board/listTasks",1,2);
    
        $limit = 40;
    
        $ret = array();

        // Статус для которого мы смотрим задачи
        $status = board_task_status::get($p["status"]);

        // Полный список задач
        $tasks = board_task::visible()->orderByExpr($status->order())->limit($limit);

        if($p["parentTaskID"]) {

            $tasks = $tasks->eq("epicParentTask",$p["parentTaskID"])->orderByExpr("`status` != 1")->asc("priority",true);
            $tasks->eq("status",array(board_task_status::STATUS_NEW,board_task_status::STATUS_IN_PROGRESS))
                ->orr()->gt("changed",util::now()->shift(-60));

        } else {
        
            $tasks->eq("status",$p["status"]);
            
            if($p["status"] != board_task_status::STATUS_IN_PROGRESS) {
                $tasks->eq("epicParentTask",0);
            }
            
        }

        // Учитываем поиск
        if($search = trim($p["search"])) {
        
            $search2 = util::str($search)->switchLayout();

            $tasks->joinByField("projectID");
            $tasks->like("text",$search)
                ->orr()->like("board_project.title",$search)
                ->orr()->like("text",$search2)
                ->orr()->like("board_project.title",$search2);
        }

        if(($tag = trim($p["tag"])) && $tag!="*") {
            $tasks->useTag($tag);
        }

        $tasks->page($p["page"]);

        $lastChange = null;
        
        foreach($tasks as $task) {

            // Вывод дат
            if($status->showDates()) {
                $changeDate = $task->pdata("changed")->date()->txt();
                if($lastChange != $changeDate) {
                    $ret["data"][] = array(
                        "text" => $changeDate,
                        "dateMark" => $changeDate,
                    );
                    $lastChange = $changeDate;
                }
            }

            $ret["data"][] = $task->stickerData();

        }

        $ret["pages"] = $tasks->pages();
        $ret["sortable"] = $status->sortable();
        $ret["showCreateButton"] = $status->showCreateButton();
        
        mod_profiler::endOperation();

        return $ret;
    }

    /**
     * Возвращает список статусов для табов вверху страницы
     **/
    public static function post_taskStatusList($p) {
        $ret = array();
        foreach(board_task_status::all() as $status) {
            $n = board_task::visible()->eq("status",$status->id())->count();
            $ret[] = array(
                "id" => $status->id(),
                "title" => $status->title().($n ? " ($n)" : ""),
            );
        }
        return $ret;
    }

    /**
     * Возвращает параметры одной задачи
     **/     
    public static function post_getTask($p) {

        $task = board_task::get($p["taskID"]);

        // Параметры задачи
        user::active()->checkAccessThrowException("board/getTaskParams",array(
            "task" => $task,
        ));

        $statuses = array();
        foreach(board_task_status::all() as $status) {
            $statuses[] = array(
                "id" => $status->id(),
                "text" => $task->status()->id() == $status->id() ? "<u>".$status->title()."</u>" : $status->title(),
            );
        }

        $stickerData = $task->stickerData();

        return array(
            "title" => "Задача #".$task->id()." / ".$task->project()->title(),
            "text" => $task->data("text"),
            "color" => $task->data("color"),
            "timeScheduled" => $task->data("timeScheduled"),
            "projectID" => $task->data("projectID"),
            "projectTitle" => $task->project()->title(),
            "statusText" => $task->statusText(),
            "currentStatus" => $task->status()->id(),
            "statuses" => $statuses,
            "deadline" => $task->data("deadline"),
            "deadlineDate" => $task->data("deadlineDate"),
            "tools" => $stickerData["tools"]
        );
    }

    /**
     * Контроллер сохранения задачи
     **/
    public static function post_saveTask($p) {

        $task = board_task::get($p["taskID"]);
        $data = util::a($p["data"])->filter("text","timeScheduled","projectID","color","deadline","deadlineDate")->asArray();

        // Параметры задачи
        user::active()->checkAccessThrowException("board/updateTaskParams",array(
            "task" => $task
        ));
        
        foreach($data as $key => $val) {
            $task->data($key,$val);
        }

        if($task->status()->id() == board_task_status::STATUS_DRAFT) {
            if(user::active()->checkAccess("boardUser")) {
                $task->data("status",board_task_status::STATUS_BACKLOG);
            } else {
                $task->data("status",board_task_status::STATUS_DEMAND);
            }
        }


        if ($task->fields()->changed()->count() > 0) {
            $task->logCustom("Изменение данных",0,board_task_log::TYPE_TASK_MODIFIED);
            mod::msg("Задача сохранена");
        }

        return true;
    }

    /**
     * Переносит задачу в другой проект
     **/
    public function post_changeProject($p) {

        $task = board_task::get($p["taskID"]);

        if(!user::active()->checkAccess("board/changeTaskProject", array (
            "task" => $task,
        ))) {
            mod::msg(user::active()->errorText(),1);
            return;
        }

        $task->data("projectID",$p["projectID"]);

        mod::msg("Проект изменен");

    }

    /**
     * Создает новую задачу
     **/
    public function post_newTask($p) {

        $project = board_project::get($p["projectID"]);

        if(!user::active()->checkAccess("board/newTask",array(
            "project" => $project,
        ))) {
            mod::msg(user::active()->errorText(),1);
            return;
        }

        $task = reflex::create("board_task",array(
            "status" => board_task_status::STATUS_DRAFT,
            "projectID" => $project->id(),
        ));

        return $task->id();
    }

    /**
     * Возвращает задачи эпика
     **/
    public function post_getEpicSubtasks($p) {

        $task = board_task::get($p["taskID"]);

        // Параметры задачи
        if(!user::active()->checkAccess("board/getEpicSubtasks",array(
            "task" => $task
        ))) {
            mod::msg(user::active()->errorText(),1);
            return;
        }

        $ret = array();

        $tasks = $task->subtasks()->orderByExpr("`status` != 1")->asc("priority",true);
        $tasks->eq("status",array(board_task_status::STATUS_NEW,board_task_status::STATUS_IN_PROGRESS))->orr()->gt("changed",util::now()->shift(-60));
        foreach($tasks as $subtask) {
            $ret[] = $subtask->stickerData();
        }
        
        return $ret;

    }

    public function post_addEpicSubtask($p) {

        $task = board_task::get($p["taskID"]);

        // Параметры задачи
        if(!user::active()->checkAccess("board/addEpicSubtask",array(
            "task" => $task
        ))) {
            mod::msg(user::active()->errorText(),1);
            return;
        }

        $task = reflex::create("board_task",array(
            "text" => $p["data"]["text"],
            "timeScheduled" => $p["data"]["timeScheduled"],
            "epicParentTask" => $task->id(),
        ));

        if($p["take"]) {
            $task->data("status",board_task_status::STATUS_IN_PROGRESS);
        }

    }

    /**
     * Меняет статус задачи
     **/
    /*public static function post_changeTaskStatus($p) {

        $task = board_task::get($p["taskID"]);
        
        $currentTaskStatus = $task->status();
        
        
        $taskLogType = board_task_log::TYPE_TASK_STATUS_CHANGED; // по умлчанию тип таск лога у нас "Статус задачи изменен"

        $status = $p["status"];
        
        // Параметры задачи
        if(!user::active()->checkAccess("board/changeTaskStatus/$status",array(
            "task" => $task,
            "status" => $status,
        ))) {
            mod::msg(user::active()->errorText(),1);
            return;
        }

        $task->data("status",$p["status"]);

        $time = $p["time"];

        // Текст про изменение статуса
        $statusText = $task->status()->action();

        if($p["comment"]) {
            $statusText = $p["comment"];
        }

        $n = $task->storage()->setPath("/log/".$p["sessionHash"])->files()->count();
        $files = $n ? $p["sessionHash"] : "";

        if(!$p["status"]){
            
            switch($currentTaskStatus->id()){
               
                case board_task_status::STATUS_CHECKOUT:
                    $taskLogType = board_task_log::TYPE_TASK_STATUS_RETURNED; //ставим статус возвращено
                	break;
                
                default:
                    $taskLogType = board_task_log::TYPE_TASK_STATUS_CHANGED;    
                	reak;
            }    
        }
        $task->logCustom($statusText,$time,$taskLogType,$files);

        return true;
    }*/

    /**
     * Взять задачу
     **/
    public function post_takeTask($p) {

        $task = board_task::get($p["taskID"]);

        if(!user::active()->checkAccess("board/takeTask",array(
            "task" => $task,
        ))) {
            mod::msg(user::active()->errorText(),1);
            return;
        }

        $task->data("status",board_task_status::STATUS_IN_PROGRESS);
        $task->logCustom(array(
            "type" => board_task_log::TYPE_TASK_TAKEN,
        ));
    }

    /**
     * Положить задачу обратно
     **/
    public function post_stopTask($p) {

        $task = board_task::get($p["taskID"]);

        if(!user::active()->checkAccess("board/stopTask",array(
            "task" => $task,
        ))) {
            mod::msg(user::active()->errorText(),1);
            return;
        }

        $task->data("status",board_task_status::STATUS_BACKLOG);
        $task->logCustom(array(
            "text" => $p["comment"],
            "type" => board_task_log::TYPE_TASK_STOPPED,
        ));

        return true;
    }

    /**
     * Отправляет задачу на доработку
     * Если это делает менеджер, задача попадает в бэклог
     * Если клиент — в «Заявки»
     **/
    public function post_revisionTask($p) {

        $task = board_task::get($p["taskID"]);

        $newStatus = board_task_status::STATUS_BACKLOG;

        if(!user::active()->checkAccess("board/revisionTaskToBacklog",array(
            "task" => $task,
        ))) {
            $newStatus = board_task_status::STATUS_DEMAND;
            if(!user::active()->checkAccess("board/revisionTaskToDemand",array(
                "task" => $task,
            ))) {
                mod::msg(user::active()->errorText(),1);
                return;
            }
        }

        $task->data("status",$newStatus);
        $task->logCustom(array(
            "text" => $p["comment"],
            "type" => board_task_log::TYPE_TASK_REVISED,
        ));

        return true;
    }

    /**
     * Помечает задачу как сделанную
     **/
    public function post_doneTask($p) {

        $task = board_task::get($p["taskID"]);

        if(!user::active()->checkAccess("board/doneTask",array(
            "task" => $task,
        ))) {
            mod::msg(user::active()->errorText(),1);
            return;
        }

        $task->data("status",board_task_status::STATUS_CHECKOUT);
        $task->logCustom(array(
            "text" => $p["comment"],
            "type" => board_task_log::TYPE_TASK_DONE,
        ));

        return true;
    }

    /**
     * Закрывает задачу (проверено)
     **/
    public function post_completeTask($p) {

        $task = board_task::get($p["taskID"]);

        if(!user::active()->checkAccess("board/completeTask",array(
            "task" => $task,
        ))) {
            mod::msg(user::active()->errorText(),1);
            return;
        }

        $task->data("status",board_task_status::STATUS_COMPLETED);
        $task->logCustom(array(
            "text" => $p["comment"],
            "type" => board_task_log::TYPE_TASK_COMPLETED,
        ));

        return true;
    }

    /**
     * Отменяет задачу
     **/
    public function post_cancelTask($p) {

        $task = board_task::get($p["taskID"]);

        if(!user::active()->checkAccess("board/cancelTask",array(
            "task" => $task,
        ))) {
            mod::msg(user::active()->errorText(),1);
            return;
        }

        $task->data("status",board_task_status::STATUS_CANCELLED);
        $task->logCustom(array(
            "type" => board_task_log::TYPE_TASK_CANCELLED,
        ));

        return true;
    }

    /**
     * Контроллер получения времени, потраченного на задачу
     **/
    public function post_getTaskTime($p) {

        $task = board_task::get($p["taskID"]);

        // Параметры задачи
        if(!user::active()->checkAccess("board/getTaskTime",array(
            "task" => $task
        ))) {
            mod::msg(user::active()->errorText(),1);
            return;
        }

        $d = $task->timeSpentProgress() + 59;

        $hours = floor($d/60/60);
        $minutes = floor($d/60)%60;

        return array(
            "hours" => $hours,
            "minutes" => $minutes,
        );

    }

    /**
     * Контроллер добавления в бэклог
     **/
    public function post_moveToBacklog($p) {

        $task = board_task::get($p["taskID"]);

        // Параметры задачи
        if(!user::active()->checkAccess("board/task/moveToBacklog",array(
            "task" => $task
        ))) {
            mod::msg(user::active()->errorText(),1);
            return;
        }

        $task->data("status",board_task_status::STATUS_BACKLOG);
        $task->logCustom(array(
            "type" => board_task_log::TYPE_TASK_MOVED_TO_BACKLOG,
        ));

    }

    /**
     * Сохраняет сортировку набора задач
     **/
    public function post_saveSort($p) {

        foreach($p["idList"] as $n=>$id) {
            $task = board_task::get($id);
            // Параметры задачи
            if(user::active()->checkAccess("board/sortTask",array(
                "task" => $task,
            ))) {
                $task->suspendTaskEvents();
                $task->data("priority",$n);
                $task->store();
                $task->unsuspendTaskEvents();
            } else {
                mod::msg(user::active()->errorText(),1);
                continue;
            }

        }

        mod::msg("Сортировка сохранена");

    }

    public function post_pauseTask($p) {

        $task = board_task::get($p["taskID"]);

        // Параметры задачи
        if(!user::active()->checkAccess("board/pauseTask",array(
            "task" => $task,
        ))) {
            mod::msg(user::active()->errorText(),1);
            return;
        }

        $task->pauseToggle();

    }
    
    public function post_updateNotice($p) {

        $task = board_task::get($p["taskID"]);

        // Параметры задачи
        if(!user::active()->checkAccess("board/updateTaskNotice",array(
            "task" => $task,
        ))) {
            mod::msg(user::active()->errorText(),1);
            return;
        }

        $task->data("notice",$p["notice"]);

    }

}
