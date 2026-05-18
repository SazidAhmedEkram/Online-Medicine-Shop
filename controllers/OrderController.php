<?php

function payment_methods()
{
    return array("Credit Card", "bKash", "Nagad", "Bank Transfer", "Cash on Delivery");
}

function checkout_page()
{
    $conn = db();

    require_role("customer");

    // fetch current cart items
    $items = cart_items($conn, current_user_id());
    if (count($items) == 0) {
        set_flash("error", "Your cart is empty.");
        redirect("/cart");
    }

    $user = user_find_by_id($conn, current_user_id());
    $address = trim($_POST["shipping_address"] ?? ($_SESSION["checkout_address"] ?? ($user["address"] ?? "")));
    $errors = array();
    $showInvoice = false;

    if (is_post()) {
        verify_csrf();

        if ($address == "") {
            $errors["shipping_address"] = "Shipping address is required.";
        } else {
            $_SESSION["checkout_address"] = $address;
            $showInvoice = true;
        }
    }

    view("customer/checkout", array(
        "title" => "Checkout",
        "items" => $items,
        "total" => cart_total($conn, current_user_id()),
        "address" => $address,
        "errors" => $errors,
        "showInvoice" => $showInvoice
    ));
}

function payment_page()
{
    $conn = db();

    require_role("customer");

    $address = trim($_SESSION["checkout_address"] ?? "");
    if ($address == "") {
        set_flash("error", "Confirm your shipping address first.");
        redirect("/checkout");
    }

    $items = cart_items($conn, current_user_id());
    if (count($items) == 0) {
        set_flash("error", "Your cart is empty.");
        redirect("/cart");
    }

    if (!is_post()) {
        view("customer/payment", array(
            "title" => "Payment",
            "methods" => payment_methods(),
            "items" => $items,
            "total" => cart_total($conn, current_user_id()),
            "errors" => array()
        ));
        return;
    }

    verify_csrf();

    $method = trim($_POST["payment_method"] ?? "");
    $errors = array();

    if (!in_array($method, payment_methods())) {
        $errors["payment_method"] = "Select a valid payment method.";
    }

    if (count($errors) > 0) {
        view("customer/payment", array(
            "title" => "Payment",
            "methods" => payment_methods(),
            "items" => $items,
            "total" => cart_total($conn, current_user_id()),
            "errors" => $errors
        ));
        return;
    }

    // place the order from the cart
    $result = order_create_from_cart($conn, current_user_id(), $address, $method);

    if (!$result["ok"]) {
        set_flash("error", $result["message"]);
        redirect("/cart");
    }

    unset($_SESSION["checkout_address"]);
    $_SESSION["last_order_id"] = $result["order_id"];
    redirect("/orders/success?id=" . $result["order_id"]);
}

function order_success()
{
    $conn = db();

    require_role("customer");

    $order_id = (int) ($_GET["id"] ?? ($_SESSION["last_order_id"] ?? 0));
    $order = order_find_for_user($conn, $order_id, current_user_id());
    $payment = payment_find_by_order_id($conn, $order_id);

    if (!$order) {
        http_response_code(404);
        view("errors/404", array("title" => "Order not found"));
        return;
    }

    view("customer/success", array(
        "title" => "Order confirmation",
        "order" => $order,
        "payment" => $payment,
        "items" => order_items($conn, $order_id)
    ));
}

function api_orders()
{
    $action = $_GET["action"] ?? "";

    if ($action == "status") {
        api_order_status();
        return;
    }

    json_response(array("ok" => false, "message" => "Invalid order API action."), 404);
}
