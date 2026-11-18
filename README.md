# MITDQL Student Information System & Portal (PHP PDO)

A secure, modern Student Information System built with PHP and **PDO (PHP Data Objects)** using a MySQL database named **`MITDQL`** and table **`studinfo`**.

---

## 📌 Project Overview & Requirements

- **Database Name:** `MITDQL`
- **Primary Table:** `studinfo`
- **Fields:**
  - `Name` (VARCHAR 150)
  - `IdNumber` (VARCHAR 50, UNIQUE)
  - `Course` (VARCHAR 100)
  - `Year` (VARCHAR 20)
  - `Subject` (VARCHAR 150)
  - `Image` (VARCHAR 255 - stores photo filename)
  - `Grade` (VARCHAR 10)
  - `Password` (VARCHAR 255 - hashed student login password)
  - `id` (INT AUTO_INCREMENT Primary Key)
  - `created_at` (TIMESTAMP)

### 👥 User Roles & Access Control

1. **Admin Portal:**
   - Put / Add new student records with `Name`, `IdNumber`, `Course`, `Year`, `Subject`, `Grade`, optional initial `Image`, and initial password.
   - Manage all student records (Edit all details, delete students, search / filter).
   - Full administrative control.

2. **Student Portal:**
   - Log in using their `IdNumber` and Password.
   - View their enrolled `Course`, `Year`, `Subject`, and `Grade` (**Read-Only / Locked**).
   - **Upload and update their profile photo (`Image`)**.
   - **Update their `Name` ONLY** (academic details cannot be modified by students).

---

## 🔑 Default Credentials

| Role | Username / ID | Password | Access Path |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin` | `admin123` | Index page &rarr; Admin Tab |
| **Sample Student** | `2026-0001` | `password123` | Index page &rarr; Student Tab |

*(Note: When an Admin adds a new student and leaves the password field blank, it automatically defaults to the student's `IdNumber`)*.

---

## 📁 Directory Structure

```text
MITDQL_Portal/
│
├── config/
│   └── db.php                 # PDO connection & automatic database/table migration
├── includes/
│   ├── auth.php               # Session management & CSRF protection
│   ├── header.php             # Responsive Bootstrap 5 navigation & flash alerts
│   └── footer.php             # Footer template
├── uploads/
│   └── default_avatar.svg     # Default avatar SVG + uploaded student photos
├── assets/
│   └── style.css              # Custom styling
├── admin/
│   ├── dashboard.php          # Admin student list, search & stats
│   ├── add_student.php        # Form for admin to register new student
│   ├── edit_student.php       # Form for admin to update all student details
│   └── delete_student.php     # Action to delete student & cleanup photo
├── student/
│   └── dashboard.php          # Student account view: edit Name & upload Image only
├── database.sql               # MySQL script with table schema & seed data
├── index.php                  # Unified landing portal (Student & Admin Login)
├── logout.php                 # Secure session destruction
├── setup.php                  # Browser diagnostics & 1-click DB setup
└── README.md                  # Documentation
```

---

## 🚀 How to Run the Project

### Method 1: Using PHP Built-In Server (Recommended & Fast)

1. Ensure MySQL / MariaDB is running (e.g. start MySQL in the XAMPP Control Panel).
2. Open PowerShell or Command Prompt in the project folder:
   ```powershell
   cd "C:\Users\Christian Lucero\.gemini\antigravity\scratch\MITDQL_Portal"
   ```
3. Run the PHP built-in web server:
   ```powershell
   & "C:\xampp\php\php.exe" -S localhost:8000
   ```
   *(Or just `php -S localhost:8000` if PHP is in your environment PATH)*
4. Open your browser and navigate to:
   ```text
   http://localhost:8000
   ```
   *The system will automatically create the `MITDQL` database, `studinfo` table, and default admin upon your first visit!*

---

### Method 2: Running via XAMPP Apache

1. Open your File Explorer and copy the project folder:
   `C:\Users\Christian Lucero\.gemini\antigravity\scratch\MITDQL_Portal`
   into:
   `C:\xampp\htdocs\MITDQL_Portal`
2. Start both **Apache** and **MySQL** in your **XAMPP Control Panel**.
3. Open your browser and visit:
   ```text
   http://localhost/MITDQL_Portal/
   ```

---

## 🛡️ Security Highlights

- **PDO Prepared Statements:** Parameterized queries used everywhere to prevent SQL injection.
- **CSRF Protection:** Anti-CSRF tokens for all state-changing forms.
- **File Upload Hardening:** Strict MIME type and extension validation (`jpg`, `jpeg`, `png`, `webp`, `gif`) with server-side size limits (3MB).
- **Password Security:** BCRYPT password hashing (`password_hash` & `password_verify`).
- **Role Isolation:** Middleware guards (`requireAdmin()`, `requireStudent()`) protecting restricted pages.
