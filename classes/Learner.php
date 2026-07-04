<?php
// classes/Learner.php
require_once 'User.php';

class Learner extends User {
    public function __construct($id, $name, $email) {
        // Intercept and pass 'learner' up automatically
        parent::__construct($id, $name, $email, 'learner');
    }
}