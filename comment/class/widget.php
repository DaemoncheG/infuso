<?

/**
 * Виджет комментария
 **/

class comment_widget extends tmp_widget {

	public function name() {
	    return "Оставить отзыв";
	}
	
	public function forID($forID) {
	    $this->param("for",$forID);
	    return $this;
	}
	
	public function execWidget() {
	
	    return tmp::exec("comment:comments",$this->param());
	}

}
