<?php

function admin_dashboard()
{
    $conn = db();

    require_role("admin");

    view("admin/dashboard", array(
        "title" => "Admin Dashboard",
        "counts" => array(
            "medicines" => medicine_count($conn),
            "categories" => category_count($conn),
            "customers" => user_count_customers($conn),
            "pending_orders" => order_count_pending($conn)
        )
    ));
}

function admin_categories()
{
    $conn = db();

    require_role("admin");

    $errors = array();
    $old = array();

    if (is_post()) {
        verify_csrf();
        $old = category_form_data();
        $errors = validate_category($old);

        if (count($errors) == 0) {
            // save new category
            category_create($conn, $old);
            set_flash("success", "Category created.");
            redirect("/admin/categories");
        }
    }

    view("admin/categories", array(
        "title" => "Categories",
        "categories" => category_all($conn),
        "errors" => $errors,
        "old" => $old
    ));
}

function admin_edit_category($id)
{
    $conn = db();

    require_role("admin");

    $category = category_find($conn, (int) $id);
    if (!$category) {
        http_response_code(404);
        view("errors/404", array("title" => "Category not found"));
        return;
    }

    $errors = array();
    $old = $category;

    if (is_post()) {
        verify_csrf();
        $old = category_form_data();
        $errors = validate_category($old);

        if (count($errors) == 0) {
            // update category
            category_update($conn, (int) $id, $old);
            set_flash("success", "Category updated.");
            redirect("/admin/categories");
        }
    }

    view("admin/category_form", array(
        "title" => "Edit Category",
        "category" => $old,
        "errors" => $errors
    ));
}

function admin_delete_category($id)
{
    $conn = db();

    require_role("admin");
    verify_csrf();

    if (category_has_medicines($conn, (int) $id)) {
        set_flash("error", "Cannot delete a category that still has medicines.");
    } else {
        category_delete($conn, (int) $id);
        set_flash("success", "Category deleted.");
    }

    redirect("/admin/categories");
}

function admin_medicines()
{
    $conn = db();

    require_role("admin");

    view("admin/medicines", array(
        "title" => "Medicines",
        "medicines" => medicine_all($conn)
    ));
}

function admin_create_medicine()
{
    $conn = db();

    require_role("admin");

    $errors = array();
    $old = array();

    if (is_post()) {
        verify_csrf();
        $old = medicine_form_data();
        $errors = validate_medicine($conn, $old);
        list($image_path, $upload_error) = upload_file("image", "medicines");

        if ($upload_error) {
            $errors["image"] = $upload_error;
        } else {
            $old["image_path"] = $image_path;
        }

        if (count($errors) == 0) {
            // save medicine
            medicine_create($conn, $old);
            set_flash("success", "Medicine created.");
            redirect("/admin/medicines");
        }

        if ($image_path) {
            delete_public_file($image_path);
            $old["image_path"] = null;
        }
    }

    view("admin/medicine_form", array(
        "title" => "Add Medicine",
        "medicine" => $old,
        "categories" => category_all($conn),
        "errors" => $errors,
        "isEdit" => false
    ));
}

function admin_edit_medicine($id)
{
    $conn = db();

    require_role("admin");

    $medicine = medicine_find($conn, (int) $id);
    if (!$medicine) {
        http_response_code(404);
        view("errors/404", array("title" => "Medicine not found"));
        return;
    }

    $errors = array();
    $old = $medicine;

    if (is_post()) {
        verify_csrf();
        $old = array_merge($medicine, medicine_form_data());
        $errors = validate_medicine($conn, $old);
        list($image_path, $upload_error) = upload_file("image", "medicines");

        if ($upload_error) {
            $errors["image"] = $upload_error;
        } else {
            $old["image_path"] = $image_path;
        }

        if (count($errors) == 0) {
            // update medicine
            medicine_update($conn, (int) $id, $old);

            if ($image_path) {
                delete_public_file($medicine["image_path"] ?? null);
            }

            set_flash("success", "Medicine updated.");
            redirect("/admin/medicines");
        }

        if ($image_path) {
            delete_public_file($image_path);
            $old["image_path"] = $medicine["image_path"] ?? null;
        }
    }

    view("admin/medicine_form", array(
        "title" => "Edit Medicine",
        "medicine" => $old,
        "categories" => category_all($conn),
        "errors" => $errors,
        "isEdit" => true
    ));
}

function admin_delete_medicine($id)
{
    $conn = db();

    require_role("admin");
    verify_csrf();

    $medicine = medicine_find($conn, (int) $id);
    if (!$medicine) {
        set_flash("error", "Medicine not found.");
        redirect("/admin/medicines");
    }

    if (medicine_in_pending_order($conn, (int) $id)) {
        set_flash("error", "Cannot delete medicine that is in a pending order.");
        redirect("/admin/medicines");
    }

    if (medicine_in_any_order($conn, (int) $id)) {
        set_flash("error", "Cannot delete medicine because it appears in purchase history.");
        redirect("/admin/medicines");
    }

    medicine_delete($conn, (int) $id);
    delete_public_file($medicine["image_path"] ?? null);
    set_flash("success", "Medicine deleted.");

    redirect("/admin/medicines");
}

function admin_customers()
{
    $conn = db();

    require_role("admin");

    view("admin/customers", array(
        "title" => "Customers",
        "customers" => user_all_customers($conn)
    ));
}

function admin_delete_customer($id)
{
    $conn = db();

    require_role("admin");
    verify_csrf();

    if (user_delete_customer($conn, (int) $id)) {
        set_flash("success", "Customer deleted with related cart and orders.");
    } else {
        set_flash("error", "Customer not found.");
    }

    redirect("/admin/customers");
}

function admin_orders()
{
    $conn = db();

    require_role("admin");

    view("admin/orders", array(
        "title" => "Purchase Requests",
        "orders" => order_all($conn)
    ));
}

function admin_history()
{
    $conn = db();

    require_role("admin");

    view("admin/history", array(
        "title" => "Purchase History",
        "orders" => order_accepted_history($conn)
    ));
}

function api_order_status()
{
    $conn = db();

    require_admin_json();
    verify_csrf();

    $order_id = (int) ($_POST["order_id"] ?? 0);
    $status = trim($_POST["status"] ?? "");

    // update order status
    $updated = order_update_status($conn, $order_id, $status);

    if (!$updated) {
        json_response(array("ok" => false, "message" => "Could not update order."), 422);
    }

    header("Content-Type: application/json");
    echo json_encode(array("ok" => true, "message" => "Order status updated.", "status" => $status));
    exit;
}

function category_form_data()
{
    return array(
        "name" => trim($_POST["name"] ?? ""),
        "category_type" => trim($_POST["category_type"] ?? "")
    );
}

function validate_category($data)
{
    $errors = array();

    if ($data["name"] == "" || strlen($data["name"]) > 120) {
        $errors["name"] = "Category name is required and must be under 120 characters.";
    }

    if ($data["category_type"] != "liquid" && $data["category_type"] != "solid") {
        $errors["category_type"] = "Choose liquid or solid.";
    }

    return $errors;
}

function medicine_form_data()
{
    return array(
        "name" => trim($_POST["name"] ?? ""),
        "category_id" => (int) ($_POST["category_id"] ?? 0),
        "vendor_name" => trim($_POST["vendor_name"] ?? ""),
        "price" => trim($_POST["price"] ?? ""),
        "availability" => trim($_POST["availability"] ?? ""),
        "description" => trim($_POST["description"] ?? ""),
        "image_path" => null
    );
}

function validate_medicine($conn, $data)
{
    $errors = array();

    if ($data["name"] == "" || strlen($data["name"]) > 150) {
        $errors["name"] = "Medicine name is required and must be under 150 characters.";
    }

    if (!category_find($conn, (int) $data["category_id"])) {
        $errors["category_id"] = "Choose a valid category.";
    }

    if ($data["vendor_name"] == "" || strlen($data["vendor_name"]) > 120) {
        $errors["vendor_name"] = "Vendor name is required and must be under 120 characters.";
    }

    if (!is_numeric($data["price"]) || (float) $data["price"] <= 0) {
        $errors["price"] = "Price must be greater than 0.";
    }

    if (!ctype_digit((string) $data["availability"])) {
        $errors["availability"] = "Stock must be 0 or higher.";
    }

    if (strlen($data["description"]) > 1000) {
        $errors["description"] = "Description must be under 1000 characters.";
    }

    return $errors;
}

function require_admin_json()
{
    if (!current_user_id() || current_role() != "admin") {
        json_response(array("ok" => false, "message" => "Admin access required."), 403);
    }
}
