<?php
// classes/Tutor.php
require_once 'User.php';

class Tutor extends User {
    public function __construct($id, $name, $email) {
        // Intercept and pass 'tutor' up automatically
        parent::__construct($id, $name, $email, 'tutor');
    }
}