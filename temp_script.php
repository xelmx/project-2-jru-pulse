<?php
$user_roles_config = [
    'lyleearl.rementizo@my.jru.edu' => ['role' => 'admin'],
    'juancarlosmiguel.timoteo@my.jru.edu' => ['role' => 'admin'],
    'retteranzelshayne.romero@my.jru.edu' => ['role' => 'admin'],
    'isaacace.gatchalian@my.jru.edu' => ['role' => 'office_head', 'office_name' => 'Information Technology Office'],
    'officehead.it@jru.edu.ph' => ['role' => 'office_head', 'office_name' => 'Human Resources Department'], // Assuming this was a typo in your image and you meant an email
    'finance.head@jru.edu.ph' => ['role' => 'office_head', 'office_name' => 'Finance Office'] // Assuming this too
];
echo json_encode($user_roles_config, JSON_UNESCAPED_SLASHES);
?>