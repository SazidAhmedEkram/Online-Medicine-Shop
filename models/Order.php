<?php

function order_create_from_cart($conn, $user_id, $address, $payment_method)
{
    // fetch cart items before placing order
    $items = cart_items($conn, $user_id);

    if (count($items) == 0) {
        return array("ok" => false, "message" => "Your cart is empty.");
    }

    mysqli_begin_transaction($conn);

    $total = 0;

    foreach ($items as $item) {
        if ((int) $item["quantity"] < 1 || (int) $item["quantity"] > (int) $item["availability"]) {
            mysqli_rollback($conn);
            return array("ok" => false, "message" => $item["name"] . " does not have enough stock.");
        }
        $total = $total + (float) $item["subtotal"];
    }

    // create order row
    db_execute(
        $conn,
        "INSERT INTO orders (user_id, total_amount, shipping_address, status, payment_method)
         VALUES (?, ?, ?, 'pending', ?)",
        "idss",
        array($user_id, $total, $address, $payment_method)
    );

    $order_id = db_last_id($conn);

    foreach ($items as $item) {
        // save each ordered item
        db_execute(
            $conn,
            "INSERT INTO order_items (order_id, medicine_id, quantity, unit_price)
             VALUES (?, ?, ?, ?)",
            "iiid",
            array($order_id, $item["medicine_id"], $item["quantity"], $item["price"])
        );

        // reduce medicine stock
        db_execute(
            $conn,
            "UPDATE medicines SET availability = availability - ? WHERE id = ? AND availability >= ?",
            "iii",
            array($item["quantity"], $item["medicine_id"], $item["quantity"])
        );

        if (db_affected_rows($conn) == 0) {
            mysqli_rollback($conn);
            return array("ok" => false, "message" => $item["name"] . " went out of stock.");
        }
    }

    $transaction_id = strtoupper(bin2hex(random_bytes(6)));
    payment_create($conn, $order_id, $total, $payment_method, $transaction_id);

    cart_clear($conn, $user_id);
    mysqli_commit($conn);

    return array("ok" => true, "order_id" => $order_id);
}

function order_find_for_user($conn, $order_id, $user_id)
{
    // fetch one order for one customer
    return db_select_one(
        $conn,
        "SELECT *
         FROM orders
         WHERE orders.id = ? AND orders.user_id = ?
         LIMIT 1",
        "ii",
        array($order_id, $user_id)
    );
}

function order_items($conn, $order_id)
{
    // fetch ordered items
    return db_select_all(
        $conn,
        "SELECT order_items.*, medicines.name, medicines.vendor_name
         FROM order_items
         JOIN medicines ON medicines.id = order_items.medicine_id
         WHERE order_items.order_id = ?
         ORDER BY order_items.id",
        "i",
        array($order_id)
    );
}

function order_all($conn)
{
    // fetch all orders for admin page
    return db_select_all(
        $conn,
        "SELECT orders.*, users.name AS customer_name, users.email AS customer_email
         FROM orders
         JOIN users ON users.id = orders.user_id
         ORDER BY orders.order_date DESC"
    );
}

function order_accepted_history($conn)
{
    // fetch accepted order history
    return db_select_all(
        $conn,
        "SELECT orders.*, users.name AS customer_name, users.email AS customer_email, users.phone,
                GROUP_CONCAT(CONCAT(medicines.name, ' x ', order_items.quantity) ORDER BY medicines.name SEPARATOR ', ') AS medicines
         FROM orders
         JOIN users ON users.id = orders.user_id
         JOIN order_items ON order_items.order_id = orders.id
         JOIN medicines ON medicines.id = order_items.medicine_id
         WHERE orders.status = 'accepted'
         GROUP BY orders.id, orders.user_id, orders.total_amount, orders.shipping_address,
                  orders.status, orders.payment_method, orders.order_date,
                  users.name, users.email, users.phone
         ORDER BY orders.order_date DESC"
    );
}

function order_count_pending($conn)
{
    $row = db_select_one($conn, "SELECT COUNT(*) AS total FROM orders WHERE status = 'pending'");
    return (int) $row["total"];
}

function order_update_status($conn, $order_id, $status)
{
    if ($status != "accepted" && $status != "rejected") {
        return false;
    }

    $order = db_select_one($conn, "SELECT status FROM orders WHERE id = ? LIMIT 1", "i", array($order_id));
    if (!$order) {
        return false;
    }

    if ($order["status"] == $status) {
        return true;
    }

    mysqli_begin_transaction($conn);

    if ($status == "rejected" && $order["status"] != "rejected") {
        order_restore_stock($conn, $order_id);
    }

    if ($status == "accepted" && $order["status"] == "rejected") {
        $stock_ok = order_reserve_stock_again($conn, $order_id);

        if (!$stock_ok) {
            mysqli_rollback($conn);
            return false;
        }
    }

    // update order status
    db_execute($conn, "UPDATE orders SET status = ? WHERE id = ?", "si", array($status, $order_id));
    mysqli_commit($conn);
    return true;
}

function order_restore_stock($conn, $order_id)
{
    // return stock when order is rejected
    db_execute(
        $conn,
        "UPDATE medicines
         JOIN order_items ON order_items.medicine_id = medicines.id
         SET medicines.availability = medicines.availability + order_items.quantity
         WHERE order_items.order_id = ?",
        "i",
        array($order_id)
    );
}

function order_reserve_stock_again($conn, $order_id)
{
    $items = order_items($conn, $order_id);

    foreach ($items as $item) {
        // reduce stock again when rejected order becomes accepted
        db_execute(
            $conn,
            "UPDATE medicines SET availability = availability - ? WHERE id = ? AND availability >= ?",
            "iii",
            array($item["quantity"], $item["medicine_id"], $item["quantity"])
        );

        if (db_affected_rows($conn) == 0) {
            return false;
        }
    }

    return true;
}
