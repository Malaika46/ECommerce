# 🛍️ M-Shopping — E-Commerce Web App

A fully functional e-commerce web application built with **PHP**, **MySQL**, **HTML**, **CSS**, and **JavaScript**. Users can browse products, manage a shopping cart, save wishlists, update their profile, and complete payments — all in one place.

---

## 🚀 Live Demo

> Hosted on [InfinityFree / Ezyro](https://ezyro.com) — PHP + MySQL hosting

---

## 📸 Screenshots

| Home | Cart | Payment |
|------|------|---------|
| Product listings with search & filter | Live quantity update with +/- buttons | Secure bank transfer checkout |

---

## ✨ Features

- 🔐 **User Authentication** — Signup, Login, Logout with password hashing (`password_hash`)
- 🛒 **Shopping Cart** — Add, remove, update quantity with **live price update** (no page reload)
- ❤️ **Wishlist** — Save favourite products
- 📦 **Product Management** — Sellers can add, edit, delete products with image upload
- 💳 **Payment System** — Bank transfer via account ID/key/password with balance check
- 👤 **User Profile** — Update name, phone, and **upload profile picture**
- 📋 **Order History** — View past orders and their items
- 📱 **Responsive Design** — Works on mobile and desktop

---

## 🗂️ Project Structure

```
M-Shopping/
│
├── index1.php          # Home page — product listing, add to wishlist
├── signin.php          # Login page
├── signup.php          # Registration page
├── logout.php          # Session destroy & redirect
│
├── add to cart.php     # Cart page — live quantity +/- update via AJAX
├── payment.php         # Checkout & payment page
├── orders.php          # Order history
├── wishlist.php        # Wishlist page
├── profile.php         # User profile + profile picture upload
├── product.php         # Add new product (seller)
│
├── UPLOADS/            # Uploaded product & profile images
│
└── README.md
```

---

## 🗄️ Database Tables

| Table | Description |
|-------|-------------|
| `data1` | Users (name, email, phone, password, profile_pic) |
| `product` | Product listings (name, type, price, qty, image, seller email) |
| `mycard` | Shopping cart items |
| `wishlist` | User wishlists |
| `orders` | Placed orders |
| `order_items` | Items inside each order |
| `paymentdetails` | Buyer payment accounts |
| `seller` | Seller accounts & balances |
| `transactions` | Payment transaction log |

---

## ⚙️ Setup & Installation

### 1. Clone the repository

```bash
git clone https://github.com/your-username/m-shopping.git
cd m-shopping
```

### 2. Import the database

- Open **phpMyAdmin**
- Create a new database (e.g. `mshopping_db`)
- Import `ecommerce_database.sql`
- Then import `product_insert_queries.sql` (optional sample data)

### 3. Configure database connection

In each PHP file, update the connection credentials:

```php
$conn = mysqli_connect('sql106.ezyro.com', 'ezyro_41226653', 'examapp1234567890', 'ezyro_41226653_bgnupdvt_data_base');
```

### 4. Set up UPLOADS folder

Make sure the `UPLOADS/` folder exists and has write permissions:

```bash
mkdir UPLOADS
chmod 755 UPLOADS
```

### 5. Run locally

Use **XAMPP** or **WAMP**:
- Place the project in `htdocs/` folder
- Start Apache & MySQL
- Visit `https://exam-app.liveblog365.com/ECommerce/addtocard/index1.php`

---

## 🛠️ Tech Stack

| Technology | Usage |
|------------|-------|
| PHP 7+ | Backend logic, sessions, file uploads |
| MySQL / MariaDB | Database |
| HTML5 + CSS3 | Frontend structure & styling |
| JavaScript (Vanilla) | AJAX cart updates, live UI |
| Font Awesome 6 | Icons |
| Google Fonts (Poppins) | Typography |

---

## 🔒 Security Notes

- Passwords stored using PHP `password_hash()` (bcrypt)
- SQL queries use `mysqli_real_escape_string()` and prepared statements
- Sessions used for authentication (`$_SESSION['email']`)
- File uploads validated by MIME type and size (max 3MB)

> ⚠️ For production use, consider adding CSRF protection and input validation middleware.

---

## 👩‍💻 Developer

**Malaika** — [@malaikamunir](mailto:malaikamunir.dev@gmail.com)

---

## 📄 License

This project is for educational purposes. Feel free to use and modify.

---

> Made with ❤️ using PHP & MySQL
