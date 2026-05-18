<?php

function attempt_remembered_login()
{
    $conn = db();

    if (current_user_id() || !isset($_COOKIE["remember_me"])) {
        return;
    }

    $parts = explode(":", $_COOKIE["remember_me"]);
    if (count($parts) != 2) {
        return;
    }

    $selector = $parts[0];
    $token = $parts[1];

    // clean old tokens and fetch saved login
    remember_delete_expired($conn);
    $saved_token = remember_find_token($conn, $selector);

    if (!$saved_token || !password_verify($token, $saved_token["token_hash"])) {
        remember_delete_token($conn, $selector);
        clear_remember_cookie();
        return;
    }

    login_user($saved_token);
}

function auth_register()
{
    $conn = db();

    if (!is_post()) {
        view("auth/register", array("title" => "Register", "errors" => array(), "old" => array()));
        return;
    }

    verify_csrf();

    $data = array(
        "name" => trim($_POST["name"] ?? ""),
        "email" => strtolower(trim($_POST["email"] ?? "")),
        "password" => $_POST["password"] ?? "",
        "role" => $_POST["role"] ?? "customer",
        "address" => trim($_POST["address"] ?? ""),
        "phone" => trim($_POST["phone"] ?? "")
    );

    // validate form fields
    $errors = validate_registration($conn, $data);

    if (count($errors) > 0) {
        view("auth/register", array("title" => "Register", "errors" => $errors, "old" => $data));
        return;
    }

    // create user account
    user_create($conn, $data);
    set_flash("success", "Account created. Please login.");
    redirect("/login");
}

// implemented the login authentication
function auth_login()
{
    $conn = db();

    if (!is_post()) {
        view("auth/login", array("title" => "Login", "errors" => array(), "old" => array()));
        return;
    }

    verify_csrf();

    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";
    $remember = isset($_POST["remember"]);
    $errors = array();

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors["email"] = "Enter a valid email.";
    }

    if ($password == "") {
        $errors["password"] = "Password is required.";
    }

    $user = null;
    if (count($errors) == 0) {
        // find user by email
        $user = user_find_by_email($conn, $email);
    }

    if (count($errors) == 0 && (!$user || !password_verify($password, $user["password_hash"]))) {
        $errors["login"] = "Email or password is incorrect.";
    }

    if (count($errors) > 0) {
        view("auth/login", array("title" => "Login", "errors" => $errors, "old" => array("email" => $email)));
        return;
    }

    login_user($user);

    if ($remember) {
        remember_user($conn, $user["id"]);
    }

    redirect("/");
}

function auth_logout()
{
    $conn = db();

    verify_csrf();

    if (isset($_COOKIE["remember_me"])) {
        $parts = explode(":", $_COOKIE["remember_me"]);
        if (isset($parts[0])) {
            remember_delete_token($conn, $parts[0]);
        }
        clear_remember_cookie();
    }

    $_SESSION = array();
    session_destroy();
    redirect("/login");
}

function login_user($user)
{
    session_regenerate_id(true);
    $_SESSION["user_id"] = (int) $user["id"];
    $_SESSION["name"] = $user["name"];
    $_SESSION["role"] = $user["role"];
}

function remember_user($conn, $user_id)
{
    $selector = bin2hex(random_bytes(16));
    $token = bin2hex(random_bytes(32));
    $expires = time() + (30 * 24 * 60 * 60);
    $token_hash = password_hash($token, PASSWORD_DEFAULT);

    // save remember-me token in database
    remember_store_token($conn, $user_id, $selector, $token_hash, date("Y-m-d H:i:s", $expires));

    setcookie("remember_me", $selector . ":" . $token, array(
        "expires" => $expires,
        "path" => "/",
        "httponly" => true,
        "samesite" => "Lax"
    ));
}

function clear_remember_cookie()
{
    setcookie("remember_me", "", array(
        "expires" => time() - 3600,
        "path" => "/",
        "httponly" => true,
        "samesite" => "Lax"
    ));
}

function validate_registration($conn, $data)
{
    $errors = array();

    if ($data["name"] == "" || strlen($data["name"]) > 100) {
        $errors["name"] = "Name is required and must be under 100 characters.";
    }

    if (!filter_var($data["email"], FILTER_VALIDATE_EMAIL)) {
        $errors["email"] = "Enter a valid email.";
    } elseif (user_email_exists($conn, $data["email"])) {
        $errors["email"] = "This email is already registered.";
    }

    if (strlen($data["password"]) < 8) {
        $errors["password"] = "Password must be at least 8 characters.";
    }

    if ($data["role"] != "admin" && $data["role"] != "customer") {
        $errors["role"] = "Choose a valid role.";
    }

    if ($data["address"] == "") {
        $errors["address"] = "Address is required.";
    }

    if ($data["phone"] == "" || !preg_match("/^[0-9+\-\s]{6,30}$/", $data["phone"])) {
        $errors["phone"] = "Enter a valid phone number.";
    }

    return $errors;
}

function auth_profile()
{
    $conn = db();

    require_auth();

    // fetch logged-in user
    $user = user_find_by_id($conn, current_user_id());

    if (!$user) {
        $_SESSION = array();
        session_destroy();
        redirect("/login");
    }

    if (!is_post()) {
        view("customer/profile", array("title" => "Profile", "user" => $user, "errors" => array()));
        return;
    }

    verify_csrf();

    $data = array(
        "name" => trim($_POST["name"] ?? ""),
        "email" => strtolower(trim($_POST["email"] ?? "")),
        "address" => trim($_POST["address"] ?? ""),
        "phone" => trim($_POST["phone"] ?? ""),
        "current_password" => $_POST["current_password"] ?? "",
        "new_password" => $_POST["new_password"] ?? "",
        "profile_picture" => null
    );

    $errors = validate_profile($conn, $data, $user);
    list($upload_path, $upload_error) = upload_file("profile_picture", "profiles");

    if ($upload_error) {
        $errors["profile_picture"] = $upload_error;
    } else {
        $data["profile_picture"] = $upload_path;
    }

    if (count($errors) > 0) {
        if ($upload_path) {
            delete_public_file($upload_path);
            $data["profile_picture"] = $user["profile_picture"] ?? null;
        }

        $old_user = array_merge($user, $data);
        view("customer/profile", array("title" => "Profile", "user" => $old_user, "errors" => $errors));
        return;
    }

    // update profile information
    user_update_profile($conn, $user["id"], $data);

    if ($data["new_password"] != "") {
        user_update_password($conn, $user["id"], $data["new_password"]);
        remember_delete_for_user($conn, $user["id"]);
    }

    if ($upload_path) {
        delete_public_file($user["profile_picture"] ?? null);
    }

    $_SESSION["name"] = $data["name"];
    set_flash("success", "Profile updated.");
    redirect("/profile");
}

function validate_profile($conn, $data, $user)
{
    $errors = array();

    if ($data["name"] == "" || strlen($data["name"]) > 100) {
        $errors["name"] = "Name is required and must be under 100 characters.";
    }

    if (!filter_var($data["email"], FILTER_VALIDATE_EMAIL)) {
        $errors["email"] = "Enter a valid email.";
    } elseif (user_email_exists($conn, $data["email"], $user["id"])) {
        $errors["email"] = "That email is already used by another account.";
    }

    if ($data["address"] == "") {
        $errors["address"] = "Address is required.";
    }

    if ($data["phone"] == "" || !preg_match("/^[0-9+\-\s]{6,30}$/", $data["phone"])) {
        $errors["phone"] = "Enter a valid phone number.";
    }

    if ($data["new_password"] != "") {
        if (strlen($data["new_password"]) < 8) {
            $errors["new_password"] = "New password must be at least 8 characters.";
        }

        if ($data["current_password"] == "" || !password_verify($data["current_password"], $user["password_hash"])) {
            $errors["current_password"] = "Current password is required to change password.";
        }
    }

    return $errors;
}
