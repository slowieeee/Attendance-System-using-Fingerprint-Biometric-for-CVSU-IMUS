<?php
$stored_hash = '$2y$10$B/ND8HSwSlnHkqdrSfnlMOE54Hc9coOVL29uVQqpoivb9MhedSHjK';
$input_password = 'test';

if (password_verify($input_password, $stored_hash)) {
    echo "Password matches!";
} else {
    echo "Password does not match.";
}
?>
