<?php

function medicine_home()
{
    $conn = db();

    $filters = array(
        "q" => trim($_GET["q"] ?? ""),
        "vendor" => trim($_GET["vendor"] ?? ""),
        "genre" => trim($_GET["genre"] ?? ""),
        "type" => trim($_GET["type"] ?? "")
    );

    view("customer/home", array(
        "title" => "Home",
        "categories" => category_all($conn),
        "medicines" => medicine_all($conn, $filters),
        "vendors" => medicine_vendors($conn),
        "filters" => $filters,
        "activeCategory" => null
    ));
}

function medicine_category($id)
{
    $conn = db();
    $category = category_find($conn, (int) $id);

    if (!$category) {
        http_response_code(404);
        view("errors/404", array("title" => "Category not found"));
        return;
    }

    $filters = array(
        "category_id" => (int) $id,
        "type" => trim($_GET["type"] ?? ""),
        "q" => trim($_GET["q"] ?? ""),
        "vendor" => trim($_GET["vendor"] ?? "")
    );

    view("customer/home", array(
        "title" => $category["name"],
        "categories" => category_all($conn),
        "medicines" => medicine_all($conn, $filters),
        "vendors" => medicine_vendors($conn),
        "filters" => $filters,
        "activeCategory" => $category
    ));
}

function api_medicines()
{
    $action = $_GET["action"] ?? "search";

    if ($action == "search") {
        api_search_medicines();
        return;
    }

    json_response(array("ok" => false, "message" => "Invalid medicine API action."), 404);
}

function api_search_medicines()
{
    $conn = db();

    $filters = array(
        "q" => trim($_GET["q"] ?? ""),
        "vendor" => trim($_GET["vendor"] ?? ""),
        "genre" => trim($_GET["genre"] ?? ""),
        "type" => trim($_GET["type"] ?? "")
    );

    // fetch medicines for ajax search
    $medicines = medicine_all($conn, $filters);

    ob_start();
    foreach ($medicines as $medicine) {
        include BASE_PATH . "/views/partials/medicine_card.php";
    }
    $html = ob_get_clean();

    header("Content-Type: application/json");
    echo json_encode(array(
        "ok" => true,
        "count" => count($medicines),
        "html" => $html
    ));
    exit;
}
