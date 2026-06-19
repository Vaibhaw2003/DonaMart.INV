# SmartINV — Smart Inventory & Billing Management System

SmartINV is a PHP-based inventory, purchasing, and GST billing system for small and medium businesses. It tracks products, suppliers, customers, purchases, and sales invoices from a single admin dashboard, with role-based access for Admin, Manager, and Staff users.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-database-4479A1?logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-blue)

---

## ✨ Features

- **Dashboard** — at-a-glance stats on revenue, stock, and recent activity
- **Inventory** — Products and Categories with stock tracking and low-stock alerts
- **Parties** — Supplier and Customer management with purchase/order history
- **Transactions** — Purchase orders (with automatic stock updates) and Sales & GST Invoices
- **Reports & Analytics** — sales, purchases, and stock reports
- **User Accounts** — profile management, avatar upload, password change with visibility toggle
- **Role-based Access** — Admin, Manager, and Staff roles with a quick demo-login switcher
- **Activity Logging** — audit trail of key actions (create/update/delete) per module
- **Dark / Light Mode** — theme preference saved via cookie + `localStorage`
- **Invoice Tools** — PDF generation and printable invoice layout
- **DataTables-powered lists** — search, sort, and pagination on every record list

---

## 🎨 Design System — "Ledger" Theme

The UI follows a custom **Ledger** design language built to feel like a physical accounting ledger rather than a generic SaaS dashboard:

| Token | Value | Use |
|---|---|---|
| Ink Green | `#1E2A21` | Primary text / structural color |
| Marigold | `#C97A1A` | Single accent — buttons, active states, highlights |
| Warm Paper | `#EEF0E7` / `#FBFAF3` | Page background / surface |

**Typography**
- **IBM Plex Sans** — UI text
- **Fraunces** — headings / display
- **IBM Plex Mono** — invoice numbers, SKUs, stat figures (ledger-tape look)

All theme variables live in `assets/css/style.css` as CSS custom properties (`--primary`, `--bg`, `--surface`, `--border`, `--text`, etc.), making the palette easy to retheme without touching markup.

---

## 🛠 Tech Stack

- **Backend:** PHP 8+
- **Database:** MySQL / MariaDB
- **Frontend:** Bootstrap 5, Bootstrap Icons, vanilla JS
- **Tables:** DataTables (Bootstrap 5 styling)
- **Alerts/Modals:** SweetAlert2, Bootstrap modals
- **Fonts:** Google Fonts (IBM Plex Sans, IBM Plex Mono, Fraunces)

---

## 📁 Project Structure

```
SmartINV/
├── admin/                 # Admin panel pages (products, customers, suppliers, etc.)
│   ├── dashboard.php
│   ├── products.php
│   ├── categories.php
│   ├── customers.php
│   ├── suppliers.php
│   ├── purchases.php
│   ├── profile.php
│   └── ...
├── ajax/                  # AJAX endpoints (e.g. purchase detail fetch)
├── assets/
│   └── css/
│       └── style.css      # "Ledger" theme — single stylesheet, all components
├── includes/
│   ├── config.php         # App constants, DB connection settings
│   ├── auth.php           # Login / session / role guard helpers
│   ├── functions.php      # Shared helpers (formatDate, formatCurrency, db(), etc.)
│   ├── header.php
│   ├── sidebar.php
│   ├── app_header.php
│   └── footer.php
├── login.php               # Login page (light/dark themed, quick demo access)
└── README.md
```

---

## 🚀 Getting Started

### Prerequisites

- PHP 8.0 or higher
- MySQL / MariaDB
- Apache (e.g. via [XAMPP](https://www.apachefriends.org/)) or any PHP-capable web server

### Installation

1. **Clone the repository** into your server's web root:
   ```bash
   git clone https://github.com/<your-username>/smartinv.git
   ```
   For XAMPP users, this typically means cloning into `C:\xampp\htdocs\smartinv`.

2. **Create the database** and import the schema:
   ```sql
   CREATE DATABASE smartinv;
   ```
   Then import the provided `.sql` file (if included) via phpMyAdmin or the CLI:
   ```bash
   mysql -u root -p smartinv < database/smartinv.sql
   ```

3. **Configure the app** — open `includes/config.php` and set:
   ```php
   define('APP_URL', 'http://localhost/smartinv');
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'smartinv');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

4. **Set folder permissions** for file uploads (avatars, product images, invoice PDFs) if running on Linux:
   ```bash
   chmod -R 755 uploads/
   ```

5. **Visit the app** in your browser:
   ```
   http://localhost/smartinv/login.php
   ```

### Default / Demo Login

The login screen includes a **Quick Demo Access** panel for trying out each role:

| Role | Email |
|---|---|
| Admin | `admin@smartinv.com` |
| Manager | `manager@smartinv.com` |
| Staff | `staff@smartinv.com` |

> Update or remove these accounts before deploying to production.

---

## 🧩 Key Conventions

- **POST → Redirect → GET**: every create/update form redirects after a successful save (`header('Location: ...'); exit;`) to prevent duplicate submissions on page refresh, with success messages passed via `$_SESSION['flash_message']`.
- **Soft validation feedback**: failed validations re-render the form with the submitted values intact (no redirect), so users don't lose their input.
- **Styling is class-based, not inline** — components use shared classes (`.table`, `.card`, `.badge`, etc.) defined once in `style.css`, so the entire UI can be re-themed by editing a single file.

---

## 🤝 Contributing

Contributions, issues, and feature requests are welcome. Feel free to open an issue or submit a pull request.

1. Fork the project
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 📄 License

This project is licensed under the MIT License — see the [LICENSE](LICENSE) file for details.

---

## 📸 Screenshots

> _Add screenshots of the Dashboard, Customer List, and Login page here to showcase the Ledger theme._
