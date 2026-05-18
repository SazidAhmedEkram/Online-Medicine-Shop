<?php

function cart_index()
{
    $conn = db();

    require_role("customer");

    view("customer/cart", array(
        "title" => "Cart",
        "items" => cart_items($conn, current_user_id()),
        "total" => cart_total($conn, current_user_id())
    ));
}

function api_cart()
{
    $action = $_GET["action"] ?? "";

    if ($action == "add") {
        api_cart_add();
        return;
    }

    if ($action == "update") {
        api_cart_update();
        return;
    }

    if ($action == "remove") {
        api_cart_remove();
        return;
    }

    json_response(array("ok" => false, "message" => "Invalid cart API action."), 404);
}

function api_cart_add()
{
    $conn = db();

    require_customer_json();
    verify_csrf();

    $medicine_id = (int) ($_POST["medicine_id"] ?? 0);
    $quantity = (int) ($_POST["quantity"] ?? 0);
    $medicine = medicine_find($conn, $medicine_id);

    if (!$medicine) {
        json_response(array("ok" => false, "message" => "Medicine not found."), 404);
    }

    if ($quantity < 1) {
        json_response(array("ok" => false, "message" => "Quantity must be a positive number."), 422);
    }

    $current_quantity = cart_quantity_for($conn, current_user_id(), $medicine_id);

    if (($current_quantity + $quantity) > (int) $medicine["availability"]) {
        json_response(array("ok" => false, "message" => "Requested quantity exceeds available stock."), 422);
    }

    // add medicine to cart
    cart_add($conn, current_user_id(), $medicine_id, $quantity);

    header("Content-Type: application/json");
    echo json_encode(array(
        "ok" => true,
        "message" => "Added to cart.",
        "cartCount" => cart_count($conn, current_user_id())
    ));
    exit;
}

function api_cart_update()
{
    $conn = db();

    require_customer_json();
    verify_csrf();

    $medicine_id = (int) ($_POST["medicine_id"] ?? 0);
    $quantity = (int) ($_POST["quantity"] ?? 0);
    $medicine = medicine_find($conn, $medicine_id);

    if (!$medicine) {
        json_response(array("ok" => false, "message" => "Medicine not found."), 404);
    }

    if ($quantity < 1 || $quantity > (int) $medicine["availability"]) {
        json_response(array("ok" => false, "message" => "Quantity must be between 1 and available stock."), 422);
    }

    // update cart quantity
    cart_set_quantity($conn, current_user_id(), $medicine_id, $quantity);
    json_response(cart_json_payload($conn));
}

function api_cart_remove()
{
    $conn = db();

    require_customer_json();
    verify_csrf();

    $medicine_id = (int) ($_POST["medicine_id"] ?? 0);

    // remove cart item
    cart_remove($conn, current_user_id(), $medicine_id);
    json_response(cart_json_payload($conn));
}

function cart_json_payload($conn)
{
    $items = cart_items($conn, current_user_id());
    $total = cart_total($conn, current_user_id());

    ob_start();
    include BASE_PATH . "/views/partials/cart_items.php";
    $html = ob_get_clean();

    return array(
        "ok" => true,
        "html" => $html,
        "total" => number_format($total, 2),
        "cartCount" => cart_count($conn, current_user_id())
    );
}

function require_customer_json()
{
    if (!current_user_id() || current_role() != "customer") {
        json_response(array("ok" => false, "message" => "Please login as a customer."), 401);
    }
}
