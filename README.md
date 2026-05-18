# 💊 Online Medicine Shop (PHP MVC Project)

A full-stack **Online Medicine Shop web application** built using **PHP (Core PHP)**, **MySQL**, and a simplified **MVC architecture**.  
This project demonstrates a complete e-commerce workflow including authentication, a dynamic cart system, order processing, and administrative management.

---

## 🚀 Project Overview

This is an academic project designed to simulate a real-world **online pharmacy system**.  
The system supports two primary user roles:

*   👨‍💼 **Admin:** Manages inventory, categories, users, and order processing.
*   🧑‍💻 **Customer:** Browses medicines, manages a shopping cart, and places orders.

It adheres to a **simple MVC pattern** to maintain a clean separation of logic, data, and presentation layers.

---

## 🏗️ Tech Stack

*   **Backend:** PHP 8.x (Core PHP)
*   **Database:** MySQL / MariaDB (via MySQLi with Prepared Statements)
*   **Frontend:** HTML5, CSS3, JavaScript
*   **Asynchronous Operations:** AJAX (for dynamic UX)
*   **Server Environment:** Apache (XAMPP / WAMP)

---

## 📁 Project Structure

```text
├── config/          # Database configuration and connection initialization
├── models/          # Database logic and CRUD operations
├── controllers/     # Application logic & request handling
├── views/           # UI templates (HTML + PHP components)
└── index.php        # Main entry point and routing engine
```
---

# 🚀 Installation & Setup
### 1️⃣ Clone the Repository
git clone https://github.com/SazidAhmedEkram/Online-Medicine-Shop.git

### 2️⃣ Move to Server Directory

For XAMPP:
C:\xampp\htdocs\OnlineMedicineShop

### 3️⃣ Start Local Server

Start the following services in XAMPP:

Apache

MySQL
### 4️⃣ Run the Application

Open your browser:

http://localhost/Online%20Medicine%20Shop/index.php
### 5️⃣ Database Setup (Auto Initialization)

On first run, the system automatically:
Creates the database
Creates required tables
Inserts default admin account
Seeds sample medicine data

