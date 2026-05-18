<?php

function user_create($conn, $data)
{
    // hash the password before saving
    $password_hash = password_hash($data["password"], PASSWORD_DEFAULT);

    // insert the new user
    db_execute(
        $conn,
        "INSERT INTO users (name, email, password_hash, role, address, phone) VALUES (?, ?, ?, ?, ?, ?)",
        "ssssss",
        array($data["name"], $data["email"], $password_hash, $data["role"], $data["address"], $data["phone"])
    );

    return db_last_id($conn);
}

function user_find_by_email($conn, $email)
{
    // fetch one user by email
    return db_select_one($conn, "SELECT * FROM users WHERE email = ? LIMIT 1", "s", array($email));
}

function user_find_by_id($conn, $id)
{
    // fetch one user by id
    return db_select_one($conn, "SELECT * FROM users WHERE id = ? LIMIT 1", "i", array($id));
}

function user_email_exists($conn, $email, $skip_id = 0)
{
    if ($skip_id > 0) {
        $user = db_select_one($conn, "SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1", "si", array($email, $skip_id));
    } else {
        $user = db_select_one($conn, "SELECT id FROM users WHERE email = ? LIMIT 1", "s", array($email));
    }

    return $user ? true : false;
}

function user_update_profile($conn, $id, $data)
{
    if ($data["profile_picture"]) {
        // update profile with new picture
        db_execute(
            $conn,
            "UPDATE users SET name = ?, email = ?, address = ?, phone = ?, profile_picture = ? WHERE id = ?",
            "sssssi",
            array($data["name"], $data["email"], $data["address"], $data["phone"], $data["profile_picture"], $id)
        );
    } else {
        // update profile without changing picture
        db_execute(
            $conn,
            "UPDATE users SET name = ?, email = ?, address = ?, phone = ? WHERE id = ?",
            "ssssi",
            array($data["name"], $data["email"], $data["address"], $data["phone"], $id)
        );
    }
}

function user_update_password($conn, $id, $password)
{
    // hash the new password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    db_execute($conn, "UPDATE users SET password_hash = ? WHERE id = ?", "si", array($password_hash, $id));
}

function user_all_customers($conn)
{
    // fetch all customers for admin page
    return db_select_all(
        $conn,
        "SELECT id, name, email, phone, address, created_at
         FROM users
         WHERE role = 'customer'
         ORDER BY created_at DESC"
    );
}

function user_delete_customer($conn, $id)
{
    // delete customer account
    db_execute($conn, "DELETE FROM users WHERE id = ? AND role = 'customer'", "i", array($id));
    return db_affected_rows($conn) > 0;
}

function user_count_customers($conn)
{
    $row = db_select_one($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'customer'");
    return (int) $row["total"];
}

function remember_store_token($conn, $user_id, $selector, $token_hash, $expires_at)
{
    // save remember-me token
    db_execute(
        $conn,
        "INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at) VALUES (?, ?, ?, ?)",
        "isss",
        array($user_id, $selector, $token_hash, $expires_at)
    );
}

function remember_find_token($conn, $selector)
{
    // fetch remember-me token with user data
    return db_select_one(
        $conn,
        "SELECT remember_tokens.*, users.id AS id, users.name, users.email, users.role
         FROM remember_tokens
         JOIN users ON users.id = remember_tokens.user_id
         WHERE remember_tokens.selector = ? AND remember_tokens.expires_at > NOW()
         LIMIT 1",
        "s",
        array($selector)
    );
}

function remember_delete_token($conn, $selector)
{
    // delete one remember-me token
    db_execute($conn, "DELETE FROM remember_tokens WHERE selector = ?", "s", array($selector));
}

function remember_delete_for_user($conn, $user_id)
{
    // delete all remember-me tokens for one user
    db_execute($conn, "DELETE FROM remember_tokens WHERE user_id = ?", "i", array($user_id));
}

function remember_delete_expired($conn)
{
    // clean old remember-me tokens
    db_execute($conn, "DELETE FROM remember_tokens WHERE expires_at <= NOW()");
}
