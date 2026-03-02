# Personal Finance Dashboard

# Personal Finance Dashboard

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php)
![MySQL](https://img.shields.io/badge/MySQL-5.7-00758F?logo=mysql)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6-F0DB4F?logo=javascript&logoColor=000)
![XAMPP](https://img.shields.io/badge/XAMPP-ready-orange)

A compact, accurate README describing only what is implemented in this repository. No assumptions.

## What this project does (implemented)

- User registration via `register_process.php` — passwords are hashed with `password_hash()`.
- User login via `login_process.php` — authentication uses `password_verify()` and sets `$_SESSION['user_id']`.
- Session-based protection: `dashboard.php` and `home.php` check for `$_SESSION['user_id']`.
- Add transaction: form on `dashboard.php` posts to `add_transaction.php` which inserts into the `transactions` table.
- Recent transactions: `dashboard.php` runs a server-side query and renders recent rows from `transactions`.
- Save profile income details: modal on `dashboard.php` posts to `save_profile.php` which inserts a row into `income_details`.
- Tracker targets: client-side dashboard JS posts JSON to `save_tracker_target.php`; the script INSERTs or UPDATEs `tracker_targets` for the logged-in user.
- Logout: `logout.php` clears the session and redirects to `login.php`.

Notes: the dashboard also uses `localStorage` for client-side transaction and tracker caching (UI convenience) but server-side storage is the source of truth for transactions and tracker targets when saved via the provided endpoints.

## Tech stack

- PHP (mysqli)
- MySQL
- HTML, CSS, JavaScript (vanilla)
- Intended for local development on XAMPP / Apache + PHP

## Project files (quick)

- `config.php` / `db_connect.php` — database connection (procedural and OO `mysqli` variants).
- `register.php` / `register_process.php` — registration UI and insertion (uses `mysqli_query`).
- `login.php` / `login_process.php` — login UI and auth (uses non-prepared SELECT + `password_verify`).
- `dashboard.php` — protected dashboard UI; server-side queries for user, transactions, totals, and tracker values; contains client-side JS that uses `localStorage` and calls `save_tracker_target.php`.
- `add_transaction.php` — inserts transaction rows (uses interpolated SQL).
- `save_profile.php` — inserts into `income_details` (prepared statement).
- `save_tracker_target.php` — INSERT/UPDATE for `tracker_targets` (prepared statements, JSON input).
- `save_currency.php` — updates `users.currency` (prepared statement) — endpoint exists but dashboard currently stores currency selection in `localStorage`.
- `script.js`, `style.css` — small UI scripts and styles used by the auth pages.

## Database (create these tables)

Run these table definitions in your `index` database (phpMyAdmin or MySQL client). The SQL matches the columns the code uses.

```sql
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100),
  last_name VARCHAR(100),
  email VARCHAR(255) UNIQUE,
  password VARCHAR(255),
  currency VARCHAR(16) DEFAULT 'USD',
  theme VARCHAR(16) DEFAULT 'light',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  transaction_name VARCHAR(255) NOT NULL,
  type ENUM('Income','Expense') NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  transaction_date DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS income_details (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  monthly_income DECIMAL(12,2),
  income_source VARCHAR(255),
  income_date DATE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tracker_targets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  income_target DECIMAL(12,2) DEFAULT 0,
  expense_target DECIMAL(12,2) DEFAULT 0,
  savings_target DECIMAL(12,2) DEFAULT 0,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Installation (local / XAMPP)

1. Install XAMPP and start Apache + MySQL.
2. Copy the repository into `C:\xampp\htdocs\Project_folder`.
3. Create a database named `index` and run the SQL above.
4. Open `http://localhost/Project_folder/register.php` to create an account, then log in via `login.php`.

## Security (what is implemented)

- Passwords are hashed on registration (`password_hash`) and verified at login (`password_verify`).
- Session checks (`$_SESSION['user_id']`) protect the dashboard and other pages.
- Prepared statements are used in `save_profile.php`, `save_tracker_target.php`, and `save_currency.php` for those write paths.

## Known limitations (accurate)

- Several queries use interpolated SQL (e.g., `login_process.php`, `add_transaction.php`, `register_process.php`) and should be converted to prepared statements.
- Output is not consistently escaped when rendering user input (risk of XSS).
- No CSRF protection on forms or AJAX endpoints.
- The dashboard mixes server-saved transactions with client `localStorage` copies; these are not fully synchronized.

## Next steps I can implement (pick one)

- Convert all DB queries to prepared statements and standardize the DB connection.
- Escape output and add basic CSRF protection.
- Provide a `schema.sql` file with sample seed data and a demo `screenshot.png`.

---

Author: Nandini Gouda

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `first_name` VARCHAR(100),
  `last_name` VARCHAR(100),
  `email` VARCHAR(255) UNIQUE,
  `password` VARCHAR(255),
  `currency` VARCHAR(16) DEFAULT 'USD',
  `theme` VARCHAR(16) DEFAULT 'light',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- transactions table
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `transaction_name` VARCHAR(255) NOT NULL,
  `type` ENUM('Income','Expense') NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `transaction_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- income_details table (profile)
CREATE TABLE IF NOT EXISTS `income_details` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `monthly_income` DECIMAL(12,2),
  `income_source` VARCHAR(255),
  `income_date` DATE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- tracker_targets table
CREATE TABLE IF NOT EXISTS `tracker_targets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `income_target` DECIMAL(12,2) DEFAULT 0,
  `expense_target` DECIMAL(12,2) DEFAULT 0,
  `savings_target` DECIMAL(12,2) DEFAULT 0,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Notes:
- The code expects a database named `index` by default (see `config.php` / `db_connect.php`). You can change the connection details inside those files as needed.

## Usage

- Register a new account at `register.php`.
- Log in at `login.php` (authentication uses `password_hash` and `password_verify`).
- After login you'll be redirected to `dashboard.php` where you can:
  - Add a transaction (submitted to `add_transaction.php` and saved in `transactions`).
  - Edit profile income details (saves to `income_details` via `save_profile.php`).
  - Set tracker targets from the dashboard; targets are saved via `save_tracker_target.php` (AJAX JSON request).
- Use the logout button to end the session (`logout.php`).

## How Authentication & Sessions Work

- Registration (`register_process.php`) stores a hashed password using `password_hash`.
- Login (`login_process.php`) queries the `users` table and verifies the password with `password_verify`.
- On successful login, code sets `$_SESSION['user_id']` and `$_SESSION['user']` and redirects to `dashboard.php`.
- Protected pages check for `$_SESSION['user_id']` and redirect to `login.php` if not present.

## How Transactions & Trackers Are Handled

- Transactions: `dashboard.php` provides a form that posts to `add_transaction.php`, which inserts into the `transactions` table for the logged-in user.
- Recent transactions are loaded server-side in `dashboard.php` (SQL SELECT) and rendered on the page.
- Trackers: `save_tracker_target.php` accepts a JSON POST (AJAX) and INSERTs/UPDATEs `tracker_targets` for the user (using prepared statements).
- The front-end also stores transactions and tracker targets in `localStorage` for immediate UI feedback; this is not fully synchronized in all flows.

## Security Features Implemented

- Passwords are hashed with `password_hash()` and verified with `password_verify()`.
- Session-based authentication with `session_start()` and session checks on protected pages.
- Several endpoints use prepared statements (`save_tracker_target.php`, `save_profile.php`, `save_currency.php`) to prevent SQL injection for those flows.

## Known Limitations & Future Improvements

- SQL injection risk: Several places (e.g., `login_process.php`, `register_process.php`, `add_transaction.php`, `dashboard.php` queries) use interpolated SQL without prepared statements. Convert all DB queries to prepared statements.
- Cross-site scripting (XSS): Output is not consistently escaped when rendering user-provided values. Use `htmlspecialchars()` when printing data.
- CSRF protection: No CSRF tokens are used on forms or AJAX; add CSRF tokens to POST endpoints.
- Inconsistent DB APIs: `config.php` uses procedural `mysqli`, while `db_connect.php` uses object-oriented `mysqli`. Standardize to one style.
- Profile update behavior: `save_profile.php` INSERTs rather than UPDATEs existing profile rows; consider using UPSERT or UPDATE for edits.
- Front-end / back-end sync: The dashboard uses both DB-sourced transactions and localStorage-based transactions. Decide on a single source of truth and sync them.
- Password/email validation and rate-limiting for login attempts could be improved.

## Author

- Project created by the repository owner.

---

If you'd like, I can:
- Convert all DB queries to prepared statements,
- Add a proper profile UPDATE flow,
- Harden authentication (CSRF tokens, escaping), or
- Create a SQL export with sample seed data for testing.

Tell me which follow-up you'd like me to implement next.
