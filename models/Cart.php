<?php

function cart_items($conn, $user_id)
{
    // fetch all cart items for one user
    return db_select_all(
        $conn,
        "SELECT cart.id AS cart_id, cart.quantity, medicines.id AS medicine_id,
                medicines.name, medicines.vendor_name, medicines.price, medicines.availability,
                medicines.image_path, categories.name AS category_name,
                (cart.quantity * medicines.price) AS subtotal
         FROM cart
         JOIN medicines ON medicines.id = cart.medicine_id
         JOIN categories ON categories.id = medicines.category_id
         WHERE cart.user_id = ?
         ORDER BY cart.added_at DESC",
        "i",
        array($user_id)
    );
}

function cart_count($conn, $user_id)
{
    $row = db_select_one($conn, "SELECT COALESCE(SUM(quantity), 0) AS total FROM cart WHERE user_id = ?", "i", array($user_id));
    return (int) $row["total"];
}

function cart_total($conn, $user_id)
{
    $row = db_select_one(
        $conn,
        "SELECT COALESCE(SUM(cart.quantity * medicines.price), 0) AS total
         FROM cart
         JOIN medicines ON medicines.id = cart.medicine_id
         WHERE cart.user_id = ?",
        "i",
        array($user_id)
    );
    return (float) $row["total"];
}

function cart_quantity_for($conn, $user_id, $medicine_id)
{
    $row = db_select_one(
        $conn,
        "SELECT quantity FROM cart WHERE user_id = ? AND medicine_id = ? LIMIT 1",
        "ii",
        array($user_id, $medicine_id)
    );

    if ($row) {
        return (int) $row["quantity"];
    }
    return 0;
}

function cart_add($conn, $user_id, $medicine_id, $quantity)
{
    // add item to cart or increase quantity
    db_execute(
        $conn,
        "INSERT INTO cart (user_id, medicine_id, quantity)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity), added_at = CURRENT_TIMESTAMP",
        "iii",
        array($user_id, $medicine_id, $quantity)
    );
}

function cart_set_quantity($conn, $user_id, $medicine_id, $quantity)
{
    // change cart quantity
    db_execute(
        $conn,
        "UPDATE cart SET quantity = ? WHERE user_id = ? AND medicine_id = ?",
        "iii",
        array($quantity, $user_id, $medicine_id)
    );
}

function cart_remove($conn, $user_id, $medicine_id)
{
    // remove item from cart
    db_execute($conn, "DELETE FROM cart WHERE user_id = ? AND medicine_id = ?", "ii", array($user_id, $medicine_id));
}

function cart_clear($conn, $user_id)
{
    // clear the full cart
    db_execute($conn, "DELETE FROM cart WHERE user_id = ?", "i", array($user_id));
}
