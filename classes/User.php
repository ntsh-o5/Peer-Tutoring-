<?php
// classes/User.php

class User {
    protected $id;
    protected $name;
    protected $email;
    protected $role;

    // Constructor to instantiate a base User object if needed
    public function __construct($id, $name, $email, $role = 'learner') {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->role = $role;
    }

    // Explicit Object Getters needed by login.php
    public function getId()   { return $this->id; }
    public function getName() { return $this->name; }
    public function getRole() { return $this->role; }

    /**
     * Authenticate Method (Case-Insensitive Mode)
     */
    public static function authenticate($email, $password, $pdo) {
        if (!$pdo) {
            return null;
        }

        try {
            // 💡 FIX: Wrap both the column and input placeholder in LOWER() for case-insensitivity
            $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1");
            $stmt->execute([$email]);
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row) {
                $dbId           = $row['id'] ?? null;
                $dbName         = $row['name'] ?? null;
                $dbEmail        = $row['email'] ?? null;
                $dbPasswordHash = $row['password_hash'] ?? null;
                $dbRole         = $row['role'] ?? null;

                // Cryptographically verify the password string against the database hash
                if (password_verify($password, $dbPasswordHash)) {
                    
                    switch (trim($dbRole)) {
                        case 'admin':
                            require_once __DIR__ . '/Admin.php';
                            return new Admin($dbId, $dbName, $dbEmail);
                        case 'learner':
                            require_once __DIR__ . '/Learner.php';
                            return new Learner($dbId, $dbName, $dbEmail);
                        case 'tutor':
                            require_once __DIR__ . '/Tutor.php';
                            return new Tutor($dbId, $dbName, $dbEmail);
                        default:
                            return new self($dbId, $dbName, $dbEmail, $dbRole);
                    }
                }
            }
        } catch (PDOException $e) {
            error_log("Authentication database engine exception error: " . $e->getMessage());
            return null;
        }

        return null; 
    }
}