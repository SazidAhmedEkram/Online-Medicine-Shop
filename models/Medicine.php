<?php
// fetch for the all medicine
function medicine_all($conn, $filters = array())
{
    // start medicine query
    $sql = "SELECT medicines.*, categories.name AS category_name, categories.category_type
            FROM medicines
            JOIN categories ON categories.id = medicines.category_id
            WHERE 1 = 1";
    $types = "";
    $values = array();

    if (isset($filters["q"]) && $filters["q"] != "") {
        $sql .= " AND medicines.name LIKE ?";
        $types .= "s";
        $values[] = "%" . $filters["q"] . "%";
    }

    if (isset($filters["vendor"]) && $filters["vendor"] != "") {
        $sql .= " AND medicines.vendor_name LIKE ?";
        $types .= "s";
        $values[] = "%" . $filters["vendor"] . "%";
    }

    if (isset($filters["genre"]) && $filters["genre"] != "") {
        $sql .= " AND medicines.category_id = ?";
        $types .= "i";
        $values[] = (int) $filters["genre"];
    }

    if (isset($filters["type"]) && ($filters["type"] == "liquid" || $filters["type"] == "solid")) {
        $sql .= " AND categories.category_type = ?";
        $types .= "s";
        $values[] = $filters["type"];
    }

    if (isset($filters["category_id"]) && $filters["category_id"] != "") {
        $sql .= " AND medicines.category_id = ?";
        $types .= "i";
        $values[] = (int) $filters["category_id"];
    }

    $sql .= " ORDER BY medicines.created_at DESC, medicines.name ASC";
    return db_select_all($conn, $sql, $types, $values);
}

function medicine_find($conn, $id)
{
    // fetch one medicine
    return db_select_one(
        $conn,
        "SELECT medicines.*, categories.name AS category_name, categories.category_type
         FROM medicines
         JOIN categories ON categories.id = medicines.category_id
         WHERE medicines.id = ?
         LIMIT 1",
        "i",
        array($id)
    );
}

// create the medicine 
function medicine_create($conn, $data)
{
    // insert new medicine
    db_execute(
        $conn,
        "INSERT INTO medicines (name, category_id, vendor_name, price, availability, description, image_path)
         VALUES (?, ?, ?, ?, ?, ?, ?)",
        "sisdiss",
        array(
            $data["name"],
            $data["category_id"],
            $data["vendor_name"],
            $data["price"],
            $data["availability"],
            $data["description"],
            $data["image_path"]
        )
    );
}

function medicine_update($conn, $id, $data)
{
    if ($data["image_path"]) {
        // update medicine with new image
        db_execute(
            $conn,
            "UPDATE medicines
             SET name = ?, category_id = ?, vendor_name = ?, price = ?, availability = ?, description = ?, image_path = ?
             WHERE id = ?",
            "sisdissi",
            array(
                $data["name"],
                $data["category_id"],
                $data["vendor_name"],
                $data["price"],
                $data["availability"],
                $data["description"],
                $data["image_path"],
                $id
            )
        );
    } else {
        // update medicine without changing image
        db_execute(
            $conn,
            "UPDATE medicines
             SET name = ?, category_id = ?, vendor_name = ?, price = ?, availability = ?, description = ?
             WHERE id = ?",
            "sisdisi",
            array(
                $data["name"],
                $data["category_id"],
                $data["vendor_name"],
                $data["price"],
                $data["availability"],
                $data["description"],
                $id
            )
        );
    }
}

function medicine_delete($conn, $id)
{
    // delete medicine
    db_execute($conn, "DELETE FROM medicines WHERE id = ?", "i", array($id));
}

function medicine_in_pending_order($conn, $id)
{
    $row = db_select_one(
        $conn,
        "SELECT COUNT(*) AS total
         FROM order_items
         JOIN orders ON orders.id = order_items.order_id
         WHERE order_items.medicine_id = ? AND orders.status = 'pending'",
        "i",
        array($id)
    );

    return (int) $row["total"] > 0;
}

function medicine_in_any_order($conn, $id)
{
    $row = db_select_one(
        $conn,
        "SELECT COUNT(*) AS total FROM order_items WHERE medicine_id = ?",
        "i",
        array($id)
    );

    return (int) $row["total"] > 0;
}

function medicine_vendors($conn)
{
    // fetch unique vendor names
    $rows = db_select_all($conn, "SELECT DISTINCT vendor_name FROM medicines ORDER BY vendor_name");
    $vendors = array();

    foreach ($rows as $row) {
        $vendors[] = $row["vendor_name"];
    }

    return $vendors;
}

function medicine_count($conn)
{
    $row = db_select_one($conn, "SELECT COUNT(*) AS total FROM medicines");
    return (int) $row["total"];
}
