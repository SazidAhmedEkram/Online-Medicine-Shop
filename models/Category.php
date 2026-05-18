<?php

function category_all($conn, $type = "")
{
    if ($type == "liquid" || $type == "solid") {
        // fetch categories by type
        return db_select_all(
            $conn,
            "SELECT categories.*, COUNT(medicines.id) AS medicine_count
             FROM categories
             LEFT JOIN medicines ON medicines.category_id = categories.id
             WHERE categories.category_type = ?
             GROUP BY categories.id, categories.name, categories.category_type, categories.created_at
             ORDER BY categories.category_type, categories.name",
            "s",
            array($type)
        );
    }

    // fetch all categories
    return db_select_all(
        $conn,
        "SELECT categories.*, COUNT(medicines.id) AS medicine_count
         FROM categories
         LEFT JOIN medicines ON medicines.category_id = categories.id
         GROUP BY categories.id, categories.name, categories.category_type, categories.created_at
         ORDER BY categories.category_type, categories.name"
    );
}

function category_find($conn, $id)
{
    // fetch one category
    return db_select_one($conn, "SELECT * FROM categories WHERE id = ? LIMIT 1", "i", array($id));
}

function category_create($conn, $data)
{
    // insert new category
    db_execute(
        $conn,
        "INSERT INTO categories (name, category_type) VALUES (?, ?)",
        "ss",
        array($data["name"], $data["category_type"])
    );
}

function category_update($conn, $id, $data)
{
    // update category
    db_execute(
        $conn,
        "UPDATE categories SET name = ?, category_type = ? WHERE id = ?",
        "ssi",
        array($data["name"], $data["category_type"], $id)
    );
}

function category_delete($conn, $id)
{
    // delete category
    db_execute($conn, "DELETE FROM categories WHERE id = ?", "i", array($id));
}

function category_has_medicines($conn, $id)
{
    $row = db_select_one($conn, "SELECT COUNT(*) AS total FROM medicines WHERE category_id = ?", "i", array($id));
    return (int) $row["total"] > 0;
}

function category_count($conn)
{
    $row = db_select_one($conn, "SELECT COUNT(*) AS total FROM categories");
    return (int) $row["total"];
}
