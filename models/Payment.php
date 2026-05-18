<?php

function payment_create($conn, $order_id, $amount, $payment_method, $transaction_id)
{
    // save payment information
    db_execute(
        $conn,
        "INSERT INTO payments (order_id, amount, payment_method, transaction_id)
         VALUES (?, ?, ?, ?)",
        "idss",
        array($order_id, $amount, $payment_method, $transaction_id)
    );
}

function payment_find_by_order_id($conn, $order_id)
{
    // fetch payment by order id
    return db_select_one(
        $conn,
        "SELECT * FROM payments WHERE order_id = ? LIMIT 1",
        "i",
        array($order_id)
    );
}
