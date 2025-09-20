<?php
require_once 'configs/database.php';

abstract class BaseModel {
    // Database connection
    protected static $_connection;

    public function __construct() {
        if (!isset(self::$_connection)) {
            // Kết nối MySQL
            self::$_connection = mysqli_connect(
                DB_HOST,     // 'mysql-db' nếu trong Docker
                DB_USER,     // 'root'
                DB_PASSWORD, // 'root'
                DB_NAME,     // 'mydb'
                DB_PORT      // 3306
            );

            // Kiểm tra lỗi kết nối
            if (!self::$_connection) {
                die("Connection failed: " . mysqli_connect_error());
            }

            // Set charset UTF8
            mysqli_set_charset(self::$_connection, "utf8");
        }
    }

    /**
     * Query in database
     */
    protected function query($sql) {
        $result = self::$_connection->query($sql);
        if ($result === false) {
            die("Error in query: " . self::$_connection->error);
        }
        return $result;
    }

    /**
     * Select statement
     */
    protected function select($sql) {
        $result = $this->query($sql);
        $rows = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    /**
     * Delete statement
     */
    protected function delete($sql) {
        return $this->query($sql);
    }

    /**
     * Update statement
     */
    protected function update($sql) {
        return $this->query($sql);
    }

    /**
     * Insert statement
     */
    protected function insert($sql) {
        return $this->query($sql);
    }
}
