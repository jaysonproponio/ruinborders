# Ruin Borders - Boarding House Management System

A comprehensive PHP and MySQL-based system for managing boarding house payments and boarder accounts.

## 🏠 Features

### Admin Features
- **User Management**: Add, edit, and delete boarder accounts
- **Payment Management**: Track monthly payments (January-December) with year filtering
- **Receipt Approval**: Review and approve/reject payment receipts uploaded by boarders
- **Announcement System**: Post updates and announcements for all boarders
- **Dashboard**: View statistics and recent activity

### Boarder Features
- **Payment Status**: View monthly payment history with visual indicators
- **Profile Management**: Update personal information and profile picture
- **Receipt Upload**: Upload payment receipts for admin verification
- **Responsive Design**: Works on desktop and mobile devices

## 🎨 Design Features
- **Modern UI**: Clean, professional interface with gradient backgrounds
- **Smooth Animations**: Hover effects, transitions, and interactive elements
- **Responsive Design**: Mobile-first approach with collapsible sidebar
- **Color Palette**: Professional blue-purple gradient theme
- **Interactive Elements**: Floating shapes, card hover effects, and smooth transitions

## 🚀 Quick Start

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

### Installation

1. **Clone/Download** the project files
2. **Set up database**:
   ```sql
   -- Import the database/schema.sql file
   mysql -u username -p database_name < database/schema.sql
   ```

3. **Configure database** in `config/database.php`:
   ```php
   private $host = 'localhost';
   private $db_name = 'ruin_borders';
   private $username = 'your_username';
   private $password = 'your_password';
   ```

4. **Update base URL** in `config/config.php`:
   ```php
   define('BASE_URL', 'http://your-domain.com/ruinborders/');
   ```

5. **Set permissions**:
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/profiles/
   chmod 755 uploads/receipts/
   ```

6. **Access the system**:
   - Go to `http://your-domain.com/ruinborders/`
   - Default admin login: `admin` / `password`

## 📁 Project Structure

```
ruinborders/
├── admin/                 # Admin panel pages
│   ├── dashboard.php     # Admin dashboard
│   ├── users.php         # User management
│   ├── payments.php      # Payment management
│   ├── receipts.php      # Receipt approval
│   └── announcements.php # Announcement system
├── user/                 # User panel pages
│   ├── dashboard.php     # User dashboard
│   ├── payments.php      # Payment status
│   ├── receipts.php      # Receipt upload
│   └── profile.php       # Profile management
├── auth/                 # Authentication
│   ├── login.php         # Login page
│   └── logout.php        # Logout handler
├── config/               # Configuration files
│   ├── config.php        # Main configuration
│   └── database.php      # Database connection
├── database/             # Database files
│   └── schema.sql        # Database schema
├── uploads/              # File uploads
│   ├── profiles/         # Profile pictures
│   └── receipts/         # Payment receipts
└── index.php            # Main entry point
```

## 🔐 Security Features

- **Password Hashing**: All passwords are securely hashed using PHP's `password_hash()`
- **SQL Injection Protection**: All database queries use prepared statements
- **XSS Protection**: User input is sanitized and escaped
- **CSRF Protection**: Forms include CSRF tokens
- **File Upload Security**: File type and size validation
- **Session Management**: Secure session handling

## 🎯 User Roles

### Admin
- Full system access
- Manage all boarders
- Approve/reject payments
- Post announcements
- View all statistics

### Boarder
- View own payment status
- Upload payment receipts
- Manage own profile
- View announcements

## 📱 Mobile Responsiveness

The system is fully responsive and includes:
- Collapsible sidebar navigation
- Touch-friendly interface
- Optimized layouts for mobile devices
- Responsive grid systems

## 🚀 Deployment

For InfinityFree hosting, see the detailed [Deployment Guide](DEPLOYMENT_GUIDE.md).

## 🛠️ Technical Details

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Styling**: Custom CSS with modern design patterns
- **Icons**: Font Awesome 6.0
- **Fonts**: Google Fonts (Poppins)

## 📊 Database Schema

### Tables
- `users` - Boarder information
- `admins` - Admin accounts
- `payments` - Monthly payment records
- `payment_receipts` - Uploaded receipt images
- `announcements` - System announcements

## 🎨 Customization

### Colors
The system uses a blue-purple gradient theme. To change colors, update the CSS variables in each file:
```css
background: linear-gradient(135deg, #667eea, #764ba2);
```

### Adding Features
The modular structure makes it easy to add new features:
1. Create new PHP files in appropriate directories
2. Add database tables if needed
3. Update navigation menus
4. Follow existing code patterns

## 🔧 Troubleshooting

### Common Issues
1. **Database Connection**: Check credentials in `config/database.php`
2. **File Uploads**: Ensure upload directories exist and have proper permissions
3. **Session Issues**: Check PHP session configuration
4. **Permission Errors**: Set correct file/folder permissions

### Debug Mode
To enable debug mode, add this to `config/config.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 📝 License

This project is created for educational purposes. Feel free to modify and use as needed.

## 🤝 Support

For support or questions:
1. Check the code comments
2. Review the deployment guide
3. Test each feature thoroughly

---

**Created with ❤️ for efficient boarding house management**
# ruinborders
