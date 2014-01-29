<?

namespace infuso\dao;

class connection extends \infuso\core\service {

	/**
	 * ������ ������ PDO, ����������� ��� ����������
	 **/
	private $dbh;
	
	/**
	 * ���� ����, ��� ���������� � �� �����������
	 **/
	private $connected = false;

	public function defaultService() {
	    return "db";
	}
	
	public function command($query) {
	    return new command($this,$query);
	}
	
	/**
	 * ������������� ���������� � ����� ������
	 **/
	public function connect() {
		$dsn = $this->param("dsn");
		$user = $this->param("user");
		$password = $this->param("password");
	    $this->dbh = new \PDO($dsn, $user, $password);
	}
	
	/**
	 * ������� ���������� (���� ��� ��� �� ���� �������)
	 * ���������� ������ ������ PDO, ����������� ��� ����������
	 **/
	public function dbh() {
	
	    if(!$this->connected) {
		    $this->connect();
		    return $this->dbh;
	    }
	}

}
