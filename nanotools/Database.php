<?php

class Database extends PDO {

    /**
     * Select.
     * @param string $sql the SQL statement, can use the PDO placeholder notation "key = :key"
     * @param array $params params to replace the placeholders
     * @param int $mode the PDO fetch mode
     * @return array array of results
     */
    public function select($sql, array $params = array(), $mode = PDO::FETCH_ASSOC) {
        $st = $this->prepare($sql);
        $this->bindData($st, $params);
        $st->execute();
        return $st->fetchAll($mode);
    }

    /**
     * Insert.
     * @param string $table table to update
     * @param array $fields associative array of data to insert
     */
    public function insert($table, array $fields) {
        $fieldKeys = implode(', ', array_keys($fields));
        $fieldValues = ':' . implode(', :', array_keys($fields));

        $sql = "INSERT INTO $table ($fieldKeys) VALUES ($fieldValues)";

        $st = $this->prepare($sql);
        $this->bindData($st, $fields);
        $st->execute();
    }

    /**
     * Update.
     * @param string $table table to update
     * @param array $fields associative array of data to update
     * @param string $where SQL WHERE clause, @see $sql param in select method
     * @param array $params params to replace the placeholders (except those already specified in $fields)
     */
    public function update($table, array $fields, $where, array $params = array()) {
        $values = null;
        foreach (array_keys($fields) as $key) {
            $values .= "$key = :$key, ";
        }
        $values = rtrim($values, ', ');

        $sql = "UPDATE $table SET $values WHERE $where";
        $st = $this->prepare($sql);

        $this->bindData($st, $fields);
        $this->bindData($st, $params);

        $st->execute();
    }

    /**
     * Delete.
     * @param string $table table to update
     * @param string $where SQL WHERE clause, @see $sql param in insert method
     * @param array $params params to replace the placeholders
     * @param int $limit SQL LIMIT clause
     */
    public function delete($table, $where, array $params = array(), $limit = 1) {
        $sql = "DELETE FROM $table WHERE $where LIMIT $limit";
        $st = $this->prepare($sql);
        $this->bindData($st, $params);
        $st->execute();
    }

    private function bindData(PDOStatement $st, array $params) {
        foreach ($params as $key => $value) {
            $key = ltrim($key, ':');
            $st->bindValue(":$key", $value);
        }
    }

}
