<?php
	/**
	* Database Connection
	*/
    class DbConnect {
        private $server = '51.81.160.157';
        private $dbname = 'kxs9016_resicomm';
        private $user = 'kxs9016_kxs9016';
        private $pass = 'kxs9016@mavs';
        public function connect() {
            try {
                $conn = new PDO('mysql:host=' .$this->server .';dbname=' . $this->dbname, $this->user, $this->pass);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                return $conn;
            } catch (\Exception $e) {
                echo "Database Error: " . $e->getMessage();
            }
        }

    }
?>