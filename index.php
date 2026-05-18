<?php

define("BASE_PATH", __DIR__);
define("APP_NAME", "Online Medicine Shop");
define("UPLOAD_MAX_BYTES", 2 * 1024 * 1024);

date_default_timezone_set("Asia/Dhaka");

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require BASE_PATH . "/config/db.php";

function db_bind_values($stmt, $types, $values)
{
    if ($types == "" || $values == array()) {
        return;
    }

    $refs = array();

    foreach ($values as $key => $value) {
        $refs[$key] = &$values[$key];
    }

    mysqli_stmt_bind_param($stmt, $types, ...$refs);
}

function db_select_all($conn, $sql, $types = "", $values = array())
{
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        die("SQL prepare failed: " . mysqli_error($conn));
    }

    db_bind_values($stmt, $types, $values);

    if (!mysqli_stmt_execute($stmt)) {
        die("SQL execute failed: " . mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);
    $rows = array();

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }

    mysqli_stmt_close($stmt);
    return $rows;
}

function db_select_one($conn, $sql, $types = "", $values = array())
{
    $rows = db_select_all($conn, $sql, $types, $values);

    if (isset($rows[0])) {
        return $rows[0];
    }

    return null;
}

function db_execute($conn, $sql, $types = "", $values = array())
{
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        die("SQL prepare failed: " . mysqli_error($conn));
    }

    db_bind_values($stmt, $types, $values);

    if (!mysqli_stmt_execute($stmt)) {
        die("SQL execute failed: " . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);
}

function db_last_id($conn)
{
    return mysqli_insert_id($conn);
}

function db_affected_rows($conn)
{
    return mysqli_affected_rows($conn);
}

function base_url()
{
    $script = str_replace("\\", "/", $_SERVER["SCRIPT_NAME"] ?? "");
    $folder = rtrim(dirname($script), "/");

    if ($folder == "/" || $folder == ".") {
        return "";
    }

    return $folder;
}

function app_url()
{
    return base_url() . "/index.php";
}

function route_to_params($path)
{
    $query = array();

    if (strpos($path, "?") !== false) {
        $parts = explode("?", $path, 2);
        $path = $parts[0];
        parse_str($parts[1], $query);
    }

    $path = trim($path, "/");

    if ($path == "") {
        return $query;
    }

    if ($path == "login") {
        return array_merge(array("page" => "login"), $query);
    }

    if ($path == "register") {
        return array_merge(array("page" => "register"), $query);
    }

    if ($path == "logout") {
        return array_merge(array("page" => "logout"), $query);
    }

    if ($path == "profile") {
        return array_merge(array("page" => "profile"), $query);
    }

    if ($path == "cart") {
        return array_merge(array("page" => "cart"), $query);
    }

    if ($path == "checkout") {
        return array_merge(array("page" => "checkout"), $query);
    }

    if ($path == "payment") {
        return array_merge(array("page" => "payment"), $query);
    }

    if ($path == "admin") {
        return array_merge(array("page" => "admin"), $query);
    }

    if ($path == "admin/categories") {
        return array_merge(array("page" => "admin_categories"), $query);
    }

    if ($path == "admin/medicines") {
        return array_merge(array("page" => "admin_medicines"), $query);
    }

    if ($path == "admin/medicines/create") {
        return array_merge(array("page" => "admin_create_medicine"), $query);
    }

    if ($path == "admin/customers") {
        return array_merge(array("page" => "admin_customers"), $query);
    }

    if ($path == "admin/orders") {
        return array_merge(array("page" => "admin_orders"), $query);
    }

    if ($path == "admin/history") {
        return array_merge(array("page" => "admin_history"), $query);
    }

    if ($path == "orders/success") {
        return array_merge(array("page" => "order_success"), $query);
    }

    if (preg_match("#^category/([0-9]+)$#", $path, $match)) {
        return array_merge(array("page" => "category", "id" => $match[1]), $query);
    }

    if (preg_match("#^admin/categories/edit/([0-9]+)$#", $path, $match)) {
        return array_merge(array("page" => "admin_edit_category", "id" => $match[1]), $query);
    }

    if (preg_match("#^admin/categories/delete/([0-9]+)$#", $path, $match)) {
        return array_merge(array("page" => "admin_delete_category", "id" => $match[1]), $query);
    }

    if (preg_match("#^admin/medicines/edit/([0-9]+)$#", $path, $match)) {
        return array_merge(array("page" => "admin_edit_medicine", "id" => $match[1]), $query);
    }

    if (preg_match("#^admin/medicines/delete/([0-9]+)$#", $path, $match)) {
        return array_merge(array("page" => "admin_delete_medicine", "id" => $match[1]), $query);
    }

    if (preg_match("#^admin/customers/delete/([0-9]+)$#", $path, $match)) {
        return array_merge(array("page" => "admin_delete_customer", "id" => $match[1]), $query);
    }

    return array_merge(array("page" => $path), $query);
}

function url($path = "")
{
    $params = route_to_params($path);

    if ($params == array()) {
        return app_url();
    }

    return app_url() . "?" . http_build_query($params);
}

function asset($path)
{
    return base_url() . "/views/" . ltrim($path, "/");
}

function redirect($path)
{
    header("Location: " . url($path));
    exit;
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function is_post()
{
    return ($_SERVER["REQUEST_METHOD"] ?? "GET") == "POST";
}

function csrf_token()
{
    if (!isset($_SESSION["_csrf"])) {
        $_SESSION["_csrf"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["_csrf"];
}

function csrf_field()
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf()
{
    $token = $_POST["_csrf"] ?? ($_SERVER["HTTP_X_CSRF_TOKEN"] ?? "");

    if (!isset($_SESSION["_csrf"]) || !hash_equals($_SESSION["_csrf"], $token)) {
        if (wants_json()) {
            json_response(array("ok" => false, "message" => "Invalid security token. Refresh and try again."), 419);
        }

        set_flash("error", "Invalid security token. Refresh and try again.");
        redirect("/");
    }
}

function wants_json()
{
    $accept = $_SERVER["HTTP_ACCEPT"] ?? "";
    $requested = strtolower($_SERVER["HTTP_X_REQUESTED_WITH"] ?? "");

    return strpos($accept, "application/json") !== false || $requested == "xmlhttprequest";
}

function json_response($data, $status = 200)
{
    http_response_code($status);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($data);
    exit;
}

function set_flash($key, $message)
{
    $_SESSION["_flash"][$key] = $message;
}

function flash($key)
{
    if (isset($_SESSION["_flash"][$key])) {
        $message = $_SESSION["_flash"][$key];
        unset($_SESSION["_flash"][$key]);
        return $message;
    }

    return null;
}

function current_user_id()
{
    if (isset($_SESSION["user_id"])) {
        return (int) $_SESSION["user_id"];
    }

    return null;
}

function current_role()
{
    return $_SESSION["role"] ?? null;
}

function require_auth()
{
    if (!current_user_id()) {
        set_flash("error", "Please login first.");
        redirect("/login");
    }
}

function require_role($role)
{
    require_auth();

    if (current_role() != $role) {
        set_flash("error", "You are not allowed to access that page.");
        redirect("/");
    }
}

function shared_view_data()
{
    $data = array(
        "flash_success" => flash("success"),
        "flash_error" => flash("error"),
        "user_logged_in" => current_user_id() ? true : false,
        "user_role" => current_role(),
        "user_name" => $_SESSION["name"] ?? "",
        "cart_count" => 0
    );

    if ($data["user_logged_in"] && $data["user_role"] == "customer" && function_exists("cart_count")) {
        $data["cart_count"] = cart_count(db(), current_user_id());
    }

    return $data;
}

function view($template, $data = array())
{
    $data = array_merge(shared_view_data(), $data);
    extract($data);

    include BASE_PATH . "/views/partials/navbar.php";
    include BASE_PATH . "/views/" . $template . ".php";
    include BASE_PATH . "/views/partials/footer.php";
}

function field_value($source, $key, $default = "")
{
    if (isset($source[$key])) {
        return e($source[$key]);
    }

    return e($default);
}

function upload_file($field, $folder)
{
    if (!isset($_FILES[$field]) || $_FILES[$field]["error"] == UPLOAD_ERR_NO_FILE) {
        return array(null, null);
    }

    $file = $_FILES[$field];

    if ($file["error"] != UPLOAD_ERR_OK) {
        return array(null, "Upload failed.");
    }

    if ($file["size"] > UPLOAD_MAX_BYTES) {
        return array(null, "Image must be 2MB or smaller.");
    }

    $allowed = array("image/jpeg" => "jpg", "image/png" => "png");
    $mime = mime_content_type($file["tmp_name"]);

    if (!isset($allowed[$mime])) {
        return array(null, "Only JPEG and PNG images are allowed.");
    }

    $file_name = bin2hex(random_bytes(16)) . "." . $allowed[$mime];
    $relative_path = "uploads/" . trim($folder, "/") . "/" . $file_name;
    $target_folder = BASE_PATH . "/views/uploads/" . trim($folder, "/");

    if (!is_dir($target_folder)) {
        mkdir($target_folder, 0755, true);
    }

    if (!move_uploaded_file($file["tmp_name"], $target_folder . "/" . $file_name)) {
        return array(null, "Could not save uploaded file.");
    }

    return array($relative_path, null);
}

function delete_public_file($relative_path)
{
    if (!$relative_path) {
        return;
    }

    $file = realpath(BASE_PATH . "/views/" . ltrim($relative_path, "/"));
    $upload_folder = realpath(BASE_PATH . "/views/uploads");

    if ($file && $upload_folder && strpos($file, $upload_folder) === 0 && is_file($file)) {
        unlink($file);
    }
}

function create_tables_if_needed($conn)
{
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
        profile_picture VARCHAR(255) NULL,
        address TEXT NULL,
        phone VARCHAR(30) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        category_type ENUM('liquid', 'solid') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS medicines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        category_id INT NOT NULL,
        vendor_name VARCHAR(120) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        availability INT NOT NULL DEFAULT 0,
        description TEXT NULL,
        image_path VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_medicines_category FOREIGN KEY (category_id) REFERENCES categories(id)
    ) ENGINE=InnoDB");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        medicine_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_cart_item (user_id, medicine_id),
        CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_cart_medicine FOREIGN KEY (medicine_id) REFERENCES medicines(id)
    ) ENGINE=InnoDB");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        shipping_address TEXT NOT NULL,
        status ENUM('pending', 'accepted', 'rejected') NOT NULL DEFAULT 'pending',
        payment_method VARCHAR(50) NOT NULL,
        order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        medicine_id INT NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        CONSTRAINT fk_order_items_medicine FOREIGN KEY (medicine_id) REFERENCES medicines(id)
    ) ENGINE=InnoDB");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        transaction_id VARCHAR(120) NOT NULL,
        payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS remember_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        selector VARCHAR(32) NOT NULL UNIQUE,
        token_hash VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_remember_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    $admin = db_select_one($conn, "SELECT id FROM users WHERE email = ? LIMIT 1", "s", array("admin@example.com"));

    if (!$admin) {
        user_create($conn, array(
            "name" => "Admin User",
            "email" => "admin@example.com",
            "password" => "admin12345",
            "role" => "admin",
            "address" => "Dhaka",
            "phone" => "01700000000"
        ));
    }

    mysqli_query($conn, "INSERT INTO categories (name, category_type)
        SELECT 'Aspirin genre', 'solid'
        WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Aspirin genre')");

    mysqli_query($conn, "INSERT INTO categories (name, category_type)
        SELECT 'Paracetamol genre', 'solid'
        WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Paracetamol genre')");

    mysqli_query($conn, "INSERT INTO categories (name, category_type)
        SELECT 'Cough syrup genre', 'liquid'
        WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Cough syrup genre')");

    mysqli_query($conn, "INSERT INTO medicines (name, category_id, vendor_name, price, availability, description)
        SELECT 'Napa 500mg', c.id, 'Beximco Pharma', 10.00, 120, 'Paracetamol tablet for fever and mild pain.'
        FROM categories c
        WHERE c.name = 'Paracetamol genre'
        AND NOT EXISTS (SELECT 1 FROM medicines WHERE name = 'Napa 500mg')");

    mysqli_query($conn, "INSERT INTO medicines (name, category_id, vendor_name, price, availability, description)
        SELECT 'Aspirin Protect', c.id, 'Bayer', 25.00, 70, 'Solid aspirin tablet.'
        FROM categories c
        WHERE c.name = 'Aspirin genre'
        AND NOT EXISTS (SELECT 1 FROM medicines WHERE name = 'Aspirin Protect')");

    mysqli_query($conn, "INSERT INTO medicines (name, category_id, vendor_name, price, availability, description)
        SELECT 'Cough Relief Syrup', c.id, 'Square Pharma', 90.00, 35, 'Liquid cough syrup.'
        FROM categories c
        WHERE c.name = 'Cough syrup genre'
        AND NOT EXISTS (SELECT 1 FROM medicines WHERE name = 'Cough Relief Syrup')");
}

require BASE_PATH . "/models/User.php";
require BASE_PATH . "/models/Category.php";
require BASE_PATH . "/models/Medicine.php";
require BASE_PATH . "/models/Cart.php";
require BASE_PATH . "/models/Payment.php";
require BASE_PATH . "/models/Order.php";

require BASE_PATH . "/controllers/AuthController.php";
require BASE_PATH . "/controllers/MedicineController.php";
require BASE_PATH . "/controllers/CartController.php";
require BASE_PATH . "/controllers/OrderController.php";
require BASE_PATH . "/controllers/AdminController.php";

create_tables_if_needed(db());
attempt_remembered_login();
route_request();

function route_request()
{
    $page = $_GET["page"] ?? "home";

    if ($page == "home") {
        medicine_home();
    } elseif ($page == "category") {
        medicine_category((int) ($_GET["id"] ?? 0));
    } elseif ($page == "login") {
        auth_login();
    } elseif ($page == "register") {
        auth_register();
    } elseif ($page == "logout") {
        auth_logout();
    } elseif ($page == "profile") {
        auth_profile();
    } elseif ($page == "cart") {
        cart_index();
    } elseif ($page == "checkout") {
        checkout_page();
    } elseif ($page == "payment") {
        payment_page();
    } elseif ($page == "order_success") {
        order_success();
    } elseif ($page == "admin") {
        admin_dashboard();
    } elseif ($page == "admin_categories") {
        admin_categories();
    } elseif ($page == "admin_edit_category") {
        admin_edit_category((int) ($_GET["id"] ?? 0));
    } elseif ($page == "admin_delete_category") {
        admin_delete_category((int) ($_GET["id"] ?? 0));
    } elseif ($page == "admin_medicines") {
        admin_medicines();
    } elseif ($page == "admin_create_medicine") {
        admin_create_medicine();
    } elseif ($page == "admin_edit_medicine") {
        admin_edit_medicine((int) ($_GET["id"] ?? 0));
    } elseif ($page == "admin_delete_medicine") {
        admin_delete_medicine((int) ($_GET["id"] ?? 0));
    } elseif ($page == "admin_customers") {
        admin_customers();
    } elseif ($page == "admin_delete_customer") {
        admin_delete_customer((int) ($_GET["id"] ?? 0));
    } elseif ($page == "admin_orders") {
        admin_orders();
    } elseif ($page == "admin_history") {
        admin_history();
    } elseif ($page == "api_medicines") {
        api_medicines();
    } elseif ($page == "api_cart") {
        api_cart();
    } elseif ($page == "api_orders") {
        api_orders();
    } else {
        http_response_code(404);
        view("errors/404", array("title" => "Page not found"));
    }
}
