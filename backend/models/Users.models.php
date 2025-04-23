<?php
require_once __DIR__ . '/../config/db_connect.php';

// CREATE
function createUser($username, $email, $password, $first_name, $last_name) {
    global $conn;
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $verification_code = bin2hex(random_bytes(16)); // Random verification code
    $sql = "INSERT INTO users (username, email, password, first_name, last_name, verification_code) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssss", $username, $email, $hash, $first_name, $last_name, $verification_code);
    return mysqli_stmt_execute($stmt);
}

// READ (all)
function getAllUsers() {
    global $conn;
    $sql = "SELECT id, username, email, first_name, last_name, role, status, is_verified, created_at, updated_at FROM users";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// READ (ID)
function getUserById($id) {
    global $conn;
    $id = mysqli_real_escape_string($conn, $id);
    $sql = "SELECT id, username, email, first_name, last_name, role, status, is_verified, created_at, updated_at FROM users WHERE id = '$id' LIMIT 1";
    $result = mysqli_query($conn, $sql);
    return ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result) : null;
}

// SEARCH user by username or email
function searchUsers($keyword) {
    global $conn;
    $keyword = mysqli_real_escape_string($conn, $keyword);
    $sql = "SELECT id, username, email, first_name, last_name, role, status, is_verified, created_at, updated_at 
            FROM users 
            WHERE username LIKE '%$keyword%' OR email LIKE '%$keyword%'";
    $result = mysqli_query($conn, $sql);

    $users = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
    }
    return $users;
}

// UPDATE
function updateUser($id, $fields) {
    global $conn;

    $setParts = [];
    $values = [];

    foreach ($fields as $key => $value) {
        $setParts[] = "$key = ?";
        $values[] = $value;
    }

    if (empty($setParts)) return false;

    $sql = "UPDATE users SET " . implode(', ', $setParts) . " WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);

    $types = str_repeat("s", count($values)) . "i";
    $values[] = $id;

    mysqli_stmt_bind_param($stmt, $types, ...$values);
    return mysqli_stmt_execute($stmt);
}

// DELETE
function deleteUser($id) {
    global $conn;
    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    return mysqli_stmt_execute($stmt);
}

// Verify user
function verifyUser($email, $code) {
    global $conn;
    $sql = "UPDATE users SET is_verified = 1, verification_code = NULL WHERE email = ? AND verification_code = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $email, $code);
    return mysqli_stmt_execute($stmt);
}

// Generate reset token
function generateResetToken($email) {
    global $conn;
    $token = bin2hex(random_bytes(16));
    $sql = "UPDATE users SET reset_token = ? WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $token, $email);
    if (mysqli_stmt_execute($stmt)) {
        return $token;
    }
    return false;
}

// Reset password
function resetPassword($email, $token, $newPassword) {
    global $conn;
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET password = ?, reset_token = NULL WHERE email = ? AND reset_token = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $hash, $email, $token);
    return mysqli_stmt_execute($stmt);
}
?>
