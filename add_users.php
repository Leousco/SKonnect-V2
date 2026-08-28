<?php

// DO NOT RUN THIS UNLESS INTENDED
// THIS FILE ADDS ADMIN USERS TO THE DATABASE FOR TESTING PURPOSES.

$host    = '127.0.0.1';
$db      = 'skonnect';
$user    = 'root';
$pass    = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}

$testUsers = [
    [
        'first_name'  => 'Rey',
        'last_name'   => 'Santos',
        'middle_name' => 'Cruz',
        'gender'      => 'male',
        'birth_date'  => '2000-03-15',
        'age'         => 25,
        'email'       => 'resident@skonnect.com',
        'password'    => 'passwords',
        'role'        => 'resident',
    ],
    [
        'first_name'  => 'Maya',
        'last_name'   => 'Reyes',
        'middle_name' => 'Lim',
        'gender'      => 'female',
        'birth_date'  => '1998-07-22',
        'age'         => 27,
        'email'       => 'moderator@skonnect.com',
        'password'    => 'passwords',
        'role'        => 'moderator',
    ],
    [
        'first_name'  => 'Carlo',
        'last_name'   => 'Mendoza',
        'middle_name' => 'Bautista',
        'gender'      => 'male',
        'birth_date'  => '1995-11-05',
        'age'         => 30,
        'email'       => 'officer@skonnect.com',
        'password'    => 'passwords',
        'role'        => 'sk_officer',
    ],
];

$sql = "INSERT INTO users 
            (first_name, last_name, middle_name, gender, birth_date, age, email, password, role, is_verified, verified_at)
        VALUES 
            (:first_name, :last_name, :middle_name, :gender, :birth_date, :age, :email, :password, :role, 1, NOW())";

$stmt = $pdo->prepare($sql);

foreach ($testUsers as $u) {
    $u['password'] = password_hash($u['password'], PASSWORD_BCRYPT);
    $stmt->execute($u);
    echo "✅ Inserted [{$u['role']}] &lt;{$u['email']}&gt;<br>";
}

echo "<br><strong>Users Has Been Added.</strong>";
?>