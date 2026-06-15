# Billing Management System

## Overview

Billing Management System is a web-based application developed to simplify billing, customer management, and invoice generation for businesses. The system provides an efficient way to manage customers, products, sales transactions, and billing records through an easy-to-use dashboard.

The project is designed to reduce manual work, improve billing accuracy, and maintain organized business records.

---

## Features

### Admin Dashboard

* Secure admin login
* Dashboard overview with statistics
* User-friendly interface

### Customer Management

* Add new customers
* Edit customer details
* Delete customer records
* Search customers quickly

### Product Management

* Add products with pricing
* Update product information
* Delete products
* Manage product inventory

### Billing & Invoicing

* Create new bills
* Automatic bill calculations
* Generate professional invoices
* Print invoices
* View billing history

### Reports

* Sales reports
* Customer transaction records
* Billing summaries
* Daily and monthly sales tracking

### Security

* Secure authentication system
* Input validation
* SQL Injection protection using PDO
* Session management

---

## Technologies Used

### Frontend

* HTML5
* CSS3
* Bootstrap 5
* JavaScript

### Backend

* PHP 8+

### Database

* MySQL

### Server

* Apache (XAMPP)

---

## Project Structure

```text
BillingManagement/
│
├── admin/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
│
├── config/
│   └── db.php
│
├── customers/
├── products/
├── invoices/
├── reports/
├── uploads/
│
├── index.php
├── login.php
├── logout.php
└── README.md
```

## Installation

### Step 1: Clone or Download Project

```bash
git clone https://github.com/your-username/billing-management-system.git
```

### Step 2: Move Project

Copy the project folder to:

```text
xampp/htdocs/
```

### Step 3: Create Database

Open phpMyAdmin and create a database:

```sql
CREATE DATABASE billing_management;
```

### Step 4: Import Database

Import the provided SQL file into the database.

### Step 5: Configure Database

Update database credentials in:

```php
config/db.php
```

Example:

```php
$host = "localhost";
$dbname = "billing_management";
$username = "root";
$password = "";
```

### Step 6: Run Project

Start Apache and MySQL from XAMPP.

Open:

```text
http://localhost/BillingManagement/
```

---

## Future Enhancements

* GST Invoice Support
* PDF Invoice Generation
* Inventory Management
* Barcode Integration
* Email Invoice Delivery
* Multi-User Access Control
* Sales Analytics Dashboard

---

## Author

**Vaibhaw Singh**
MCA Student | PHP Developer

Email: [vaibhawrajput05@gmail.com](mailto:vaibhawrajput05@gmail.com)

---

## License

This project is developed for educational and business management purposes.
