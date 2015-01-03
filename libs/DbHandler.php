<?php
 
/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 */
class DbHandler {
 
    private $db;
 
    function __construct() {
        require_once dirname(__FILE__) . '/Database.php';
        // opening db connection
        try {
            $this->db = new Database();
        } catch (PDOException $e) {
            die('Database connection could not be established.');
        }
    }
 
    /* ------------- `users` table method ------------------ */
 
    /**
     * Creating new user
     * @param String $name User full name
     * @param String $email User login email id
     * @param String $password User login password
     */
    public function createUser($name, $email, $password) {
        require_once 'PassHash.php';
        $response = array();
 
        // First check if user already existed in db
        if (!$this->isUserExists($email)) {
            // Generating password hash
            $password_hash = PassHash::hash($password);
 
            // Generating API key
            $api_key = $this->generateApiKey();
 
            // insert query
            $stmt = $this->db->prepare("INSERT INTO users(name, email, password_hash, api_key, status) values(:name, :email, :pas_hash, :key, 1)");
            $result = $stmt->execute(array(':name' => $name, ':email' => $email, ':pas_hash' => $password_hash, ':key' => $api_key));
 
            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                return USER_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create user
                return USER_CREATE_FAILED;
            }
        } else {
            // User with same email already existed in the db
            return USER_ALREADY_EXISTED;
        }
 
        return $response;
    }
 
    /**
     * Checking user login
     * @param String $email User login email id
     * @param String $password User login password
     * @return boolean User login status success/fail
     */
    public function checkLogin($email, $password) {
        // fetching user by email
        $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE email = :email");
 
        $stmt->execute(array(":email" => $email));
 
        if ($stmt->rowCount() > 0) {
            // Found user with the email
            // Now verify the password
 
            $res = $stmt->fetch();
 
            if (PassHash::check_password($res->password_hash, $password)) {
                // User password is correct
                return TRUE;
            } else {
                // user password is incorrect
                return FALSE;
            }
        } else {
            // user not existed with the email
            return FALSE;
        }
    }
 
    /**
     * Checking for duplicate user by email address
     * @param String $email email to check in db
     * @return boolean
     */
    private function isUserExists($email) {
        $stmt = $this->db->prepare("SELECT id from users WHERE email = :email");
        $stmt->execute(array(":email" => $email));
        $num_rows = $stmt->rowCount();
        return $num_rows > 0;
    }
 
    /**
     * Fetching user by email
     * @param String $email User email id
     */
    public function getUserByEmail($email) {
        $stmt = $this->db->prepare("SELECT name, email, api_key, status, created_at FROM users WHERE email = :email");
        if ($stmt->execute(array(":email" => $email))) {
            $user = $stmt->fetch();
            return $user;
        } else {
            return NULL;
        }
    }
 
    /**
     * Fetching user api key
     * @param String $user_id user id primary key in user table
     */
    public function getApiKeyById($user_id) {
        $stmt = $this->db->prepare("SELECT api_key FROM users WHERE id = :user");
        if ($stmt->execute(array(':user'=>$user_id))) {
            $api_key = $stmt->fetchAll();
            return $api_key;
        } else {
            return NULL;
        }
    }
 
    /**
     * Fetching user id by api key
     * @param String $api_key user api key
     */
    public function getUserId($api_key) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE api_key = :key");
        if ($stmt->execute(array(":key" => $api_key))) {
            $user_id = $stmt->fetch();
            return $user_id;
        } else {
            return NULL;
        }
    }
 
    /**
     * Validating user api key
     * If the api key is there in db, it is a valid key
     * @param String $api_key user api key
     * @return boolean
     */
    public function isValidApiKey($api_key) {
        $stmt = $this->db->prepare("SELECT id from users WHERE api_key = :key");
        $stmt->execute(array(":key" => $api_key));
        $num_rows = $stmt->rowCount();
        return $num_rows > 0;
    }
 
    /**
     * Generating random Unique MD5 String for user Api key
     */
    private function generateApiKey() {
        return md5(uniqid(rand(), true));
    }
 
    /* ------------- `tasks` table method ------------------ */
 
    /**
     * Creating new task
     * @param String $user_id user id to whom task belongs to
     * @param String $task task text
     */
    public function createTask($user_id, $task) {        
        $stmt = $this->db->prepare("INSERT INTO tasks(task) VALUES(:task)");
        $result = $stmt->execute(array(":task" => $task));
 
        if ($result) {
            // task row created
            // now assign the task to user
            $new_task_id = $this->db->lastInsertId();
            $res = $this->createUserTask($user_id, $new_task_id);
            if ($res) {
                // task created successfully
                return $new_task_id;
            } else {
                // task failed to create
                return NULL;
            }
        } else {
            // task failed to create
            return NULL;
        }
    }
 
    /**
     * Fetching single task
     * @param String $task_id id of the task
     */
    public function getTask($task_id, $user_id) {
        $stmt = $this->db->prepare("SELECT t.id, t.task, t.status, t.created_at from tasks t, user_tasks ut WHERE t.id = :tid AND ut.task_id = t.id AND ut.user_id = :uid");

        if ($stmt->execute(array(":tid"=>$task_id,":uid"=>$user_id))) {
            $task = $stmt->fetch();
            return $task;
        } else {
            return NULL;
        }
    }
 
    /**
     * Fetching all user tasks
     * @param String $user_id id of the user
     */
    public function getAllUserTasks($user_id) {
        $stmt = $this->db->prepare("SELECT t.* FROM tasks t, user_tasks ut WHERE t.id = ut.task_id AND ut.user_id = :user");
        if($stmt->execute(array(':user' => $user_id))) {
            $tasks = $stmt->fetchAll();
            return $tasks;
        } 
        return null;
    }
 
    /**
     * Updating task
     * @param String $task_id id of the task
     * @param String $task task text
     * @param String $status task status
     */
    public function updateTask($user_id, $task_id, $task, $status) {
        $stmt = $this->db->prepare("UPDATE tasks t, user_tasks ut set t.task = ?, t.status = ? WHERE t.id = ? AND t.id = ut.task_id AND ut.user_id = ?");
        $stmt->execute(array($task, $status, $task_id, $user_id));
        $num_affected_rows = $stmt->rowCount();
        return $num_affected_rows > 0;
    }
 
    /**
     * Deleting a task
     * @param String $task_id id of the task to delete
     */
    public function deleteTask($user_id, $task_id) {
        $stmt = $this->db->prepare("DELETE t FROM tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
        $stmt->execute(array($task_id, $user_id));
        $num_affected_rows = $stmt->rowCount();
        return $num_affected_rows > 0;
    }
 
    /* ------------- `user_tasks` table method ------------------ */
 
    /**
     * Function to assign a task to user
     * @param String $user_id id of the user
     * @param String $task_id id of the task
     */
    public function createUserTask($user_id, $task_id) {
        $stmt = $this->db->prepare("INSERT INTO user_tasks(user_id, task_id) values(?, ?)");
        $result = $stmt->execute(array($user_id, $task_id));
        return $result;
    }
 
}
