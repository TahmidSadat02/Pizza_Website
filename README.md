# 🍕 Pizza Delivery Website

A modern, responsive pizza delivery website built with PHP, MySQL, and Bootstrap. Features a beautiful dark theme with gold accents and complete order management system.

## 🌟 Features

### Customer Features
- **User Registration & Authentication** - Secure user accounts with password hashing
- **Interactive Menu** - Browse categorized food items with images
- **Shopping Cart** - Add/remove items with quantity management
- **Order Placement** - Complete checkout process with address management
- **Order History** - Track previous orders and their status
- **Responsive Design** - Works perfectly on all devices

### Admin Features
- **Admin Dashboard** - Overview of customers, orders, and food items
- **Food Management** - Add, edit, and manage food items with image uploads
- **Order Management** - View and update order status
- **User Management** - View registered customers
- **Analytics** - Basic statistics and reporting

## 🎨 Design

### Color Scheme
- **Background Charcoal**: `#2F3234` - Professional dark theme
- **Text White**: `#F9FBF7` - High contrast readability
- **Accent Gold**: `#CFB54F` - Premium branding elements
- **Call-to-Action Red**: `#B40614` - Conversion-focused buttons
- **Fresh Green**: `#34912A` - Food pricing and success states

### UI/UX Highlights
- Modern dark theme with gold accents
- Smooth hover animations and transitions
- Mobile-first responsive design
- Intuitive navigation and user flow
- Professional food presentation

## 🛠️ Technology Stack

- **Backend**: PHP 8.x
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Framework**: Bootstrap 5.3
- **Icons**: Font Awesome 6.4
- **Server**: Apache (XAMPP)
- **Version Control**: Git

## 📋 Prerequisites

- XAMPP (or LAMP/WAMP) with PHP 8.x
- MySQL 5.7+ or MariaDB
- Web browser (Chrome, Firefox, Safari, Edge)
- Git (for cloning)

## 🚀 Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/TahmidSadat02/Pizza_Website.git
   cd Pizza_Website
   ```

2. **Set up XAMPP**
   - Place the project folder in your XAMPP `htdocs` directory
   - Start Apache and MySQL services

3. **Database Setup**
   ```bash
   # Navigate to the project directory
   cd pizza_delivery
   
   # Run the database setup script
   php setup_database.php
   ```

4. **Configure Database** (if needed)
   - Edit `config/db.php` with your database credentials
   - Default settings work with XAMPP

5. **Access the Website**
   - Open browser and go to: `http://localhost/Pizza_Website/pizza_delivery/public/`

## 🔐 Default Login Credentials

### Admin Access
- **Email**: `admin@example.com`
- **Password**: `123456`

### Test Customer
- **Email**: `test@example.com`
- **Password**: `123456`

## 📁 Project Structure

```
pizza_delivery/
├── assets/
│   ├── css/
│   │   └── style.css           # Main stylesheet with color scheme
│   ├── images/                 # Static images
│   └── js/
│       └── script.js           # JavaScript functionality
├── config/
│   └── db.php                  # Database configuration
├── includes/
│   ├── header.php              # Common header
│   ├── footer.php              # Common footer
│   └── functions.php           # Utility functions
├── logs/
│   └── debug.log               # Application logs
├── public/
│   ├── admin/                  # Admin panel pages
│   │   ├── dashboard.php
│   │   ├── add_food.php
│   │   ├── manage_food.php
│   │   └── manage_orders.php
│   ├── uploads/food/           # Uploaded food images
│   ├── index.php               # Main menu page
│   ├── login.php               # User authentication
│   ├── register.php            # User registration
│   ├── cart.php                # Shopping cart
│   ├── checkout.php            # Order placement
│   └── order_history.php       # User order history
├── database.sql                # Database schema
├── setup_database.php          # Database setup script
└── test_connection.php         # Database connection test
```

## 🔧 Configuration

### Database Settings
Edit `config/db.php` for custom database configuration:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pizza_delivery');
```

### Upload Settings
- Image upload directory: `public/uploads/food/`
- Supported formats: JPG, JPEG, PNG, GIF
- Maximum file size: 5MB

## 🎯 Key Features Implemented

### Security
- ✅ Password hashing with PHP's `password_hash()`
- ✅ SQL injection prevention with prepared statements
- ✅ XSS protection with input sanitization
- ✅ Session management for authentication
- ✅ File upload validation and security

### User Experience
- ✅ Responsive design for all screen sizes
- ✅ AJAX-powered cart operations
- ✅ Real-time cart count updates
- ✅ Form validation and error handling
- ✅ Success/error messaging system

### Admin Panel
- ✅ Dashboard with statistics
- ✅ Food item management with image uploads
- ✅ Order status management
- ✅ Customer overview

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Ensure MySQL is running
   - Check database credentials in `config/db.php`
   - Run `test_connection.php` to verify connection

2. **Image Upload Issues**
   - Check folder permissions: `chmod 777 public/uploads/food/`
   - Verify PHP upload settings in `php.ini`

3. **Login/Register Not Working**
   - Clear browser cache and cookies
   - Check if sessions are enabled in PHP
   - Verify database tables exist

4. **Styling Issues**
   - Clear browser cache
   - Check if CSS file path is correct
   - Verify Bootstrap CDN is loading

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

## 👨‍💻 Author

**Tahmid Sadat**
- GitHub: [@TahmidSadat02](https://github.com/TahmidSadat02)

## 🙏 Acknowledgments

- Bootstrap team for the excellent CSS framework
- Font Awesome for the beautiful icons
- PHP community for the robust language features

---

**Live Demo**: [Add your live demo link here]
**Documentation**: [Add documentation link if available]
**Support**: [Add support email or contact]
