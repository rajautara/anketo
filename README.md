# AnkeTo - Customizable Form Builder

A JotForm-like drag & drop form builder built with CodeIgniter 4, MySQL, and Bootstrap with multi-user role-based access control.

## Features

- **Drag & Drop Form Builder** - Intuitive interface with 15+ field types
- **Conditional Logic** - Show/hide fields based on user responses
- **Multi-User System** - Role-based access control (Admin, User, Viewer)
- **Form Themes** - Customizable form styling
- **File Uploads** - Support for file uploads in forms
- **Email Notifications** - Get notified on form submissions
- **Export Data** - Download submissions in CSV format
- **Public Forms** - Share forms with anyone via public URL

## Tech Stack

- **Backend**: CodeIgniter 4.x (PHP Framework)
- **Database**: MySQL 8.0+
- **Frontend**: Bootstrap 5.x, jQuery, SortableJS
- **Authentication**: Session-based with password hashing (bcrypt)

## Installation

### Prerequisites

- PHP 8.0 or higher
- MySQL 8.0 or higher
- Composer
- Web server (Apache/Nginx)

### Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/anketo.git
   cd anketo
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp env.sample .env
   ```
   Edit `.env` file and update database credentials:
   ```
   database.default.hostname = localhost
   database.default.database = anketo
   database.default.username = your_username
   database.default.password = your_password
   ```

4. **Create database**
   ```sql
   CREATE DATABASE anketo CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
   ```

5. **Run migrations**
   ```bash
   php spark migrate
   ```

6. **Set file permissions**
   ```bash
   chmod -R 775 writable
   chmod -R 664 writable/**/*
   ```

7. **Start development server**
   ```bash
   php spark serve
   ```

8. **Access the application**
   - URL: http://localhost:8080
   - Default Admin: admin@anketo.com / admin123

## Database Schema

The application uses the following tables:

- `roles` - User roles and permissions
- `users` - User accounts
- `form_themes` - Form theme configurations
- `forms` - Form definitions
- `form_fields` - Field configurations for forms
- `form_submissions` - Form submissions
- `submission_data` - Data for each submission
- `form_notifications` - Email notification settings

## User Roles

### Admin
- Full access to all features
- User management
- Role management
- View all forms and submissions

### User
- Create, edit, delete own forms
- View own submissions
- Export submission data

### Viewer
- Read-only access to assigned forms
- View submissions

## Field Types

- Text Input
- Email Input
- Number Input
- Password Input
- Text Area
- Checkbox
- Radio Button
- Select Dropdown
- File Upload
- Date Picker
- Time Picker
- Date Time Picker
- Hidden Field
- Paragraph
- Divider

## API Endpoints

### Forms
- `POST /api/forms` - Create form
- `GET /api/forms` - List forms
- `GET /api/forms/{id}` - Get form details
- `PUT /api/forms/{id}` - Update form
- `DELETE /api/forms/{id}` - Delete form

### Fields
- `POST /api/forms/{id}/fields` - Add field
- `PUT /api/fields/{id}` - Update field
- `DELETE /api/fields/{id}` - Delete field
- `POST /api/fields/reorder` - Reorder fields

### Submissions
- `GET /api/forms/{id}/submissions` - Get form submissions
- `GET /api/submissions/{id}` - Get submission details
- `POST /api/forms/{id}/submit` - Submit form

### Files
- `POST /api/upload` - Upload file

## Security Features

- CSRF Protection
- XSS Prevention
- SQL Injection Protection
- Password Hashing (bcrypt)
- Session Management
- Role-Based Access Control

## Project Structure

```
anketo/
├── app/
│   ├── Config/          # Configuration files
│   ├── Controllers/     # Application controllers
│   ├── Database/        # Database migrations
│   ├── Filters/         # Route filters
│   ├── Libraries/       # Custom libraries
│   ├── Models/          # Database models
│   └── Views/           # View templates
├── public/
│   ├── assets/          # CSS, JS, images
│   └── index.php        # Entry point
├── writable/            # Writable directories
│   ├── cache/
│   ├── logs/
│   └── uploads/
└── env                  # Environment configuration
```

## Development

### Running Migrations
```bash
php spark migrate
```

### Rolling Back Migrations
```bash
php spark migrate:rollback
```

### Clear Cache
```bash
php spark cache:clear
```

### View Logs
```bash
php spark logs --tail
```

## Production Deployment

### Prerequisites for Production

- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache with mod_rewrite enabled OR Nginx
- cPanel-based shared hosting OR VPS/dedicated server with SSH access

> **Important — CodeIgniter 4 Folder Structure on Shared Hosting:**
> Only the `public/` folder should be accessible from the web (inside `public_html`).
> All other folders (`app/`, `vendor/`, `writable/`, `.env`) **must be placed outside `public_html`**
> to prevent exposing sensitive files to the internet.

### Step-by-Step Production Setup

#### 1. Prepare Your Local Project

```bash
# Remove development files
rm -rf writable/cache/*
rm -rf writable/logs/*
rm -rf writable/uploads/*
rm -rf writable/session/*

# Create production .env file
cp env.sample .env
```

Edit `.env` for production:
```ini
app.baseURL = 'https://yourdomain.com/'
app.forceGlobalSecureRequests = true

database.default.hostname = localhost
database.default.database = your_database_name
database.default.username = your_database_user
database.default.password = your_secure_password

CI_ENVIRONMENT = production
```

#### 2. Upload Files to Server

**Correct Folder Structure on Shared Hosting:**
```
home/yourusername/          ← server home (not web accessible)
├── anketo/                 ← project root (app/, vendor/, writable/, .env go here)
│   ├── app/
│   ├── vendor/
│   ├── writable/
│   └── .env
└── public_html/            ← web root (web accessible)
    ├── index.php           ← copied/modified from anketo/public/
    ├── .htaccess
    └── assets/
```

**Option A: Using FTP/SFTP**
1. Upload the entire project folder to `~/anketo/` (one level above `public_html`)
2. Copy contents of `anketo/public/` into `public_html/`
3. Edit `public_html/index.php` — update the paths to point to the project root:
   ```php
   // Change these two lines:
   $pathsConfig = FCPATH . '../anketo/app/Config/Paths.php';
   // or set ROOTPATH to the project root:
   define('ROOTPATH', realpath(__DIR__ . '/../anketo') . DIRECTORY_SEPARATOR);
   ```

**Option B: Using Git (Recommended — requires SSH access)**
```bash
# Clone project OUTSIDE public_html
cd ~
git clone https://github.com/yourusername/anketo.git anketo

# Copy public folder contents to public_html
cp -r ~/anketo/public/. ~/public_html/

# Edit index.php to point to correct project root
nano ~/public_html/index.php
```

**Option C: Using cPanel File Manager**
1. Compress project files to ZIP (excluding `vendor/` — install via Composer on server)
2. Upload ZIP to home directory (one level above `public_html`), **not** inside `public_html`
3. Extract there, resulting in `~/anketo/`
4. Move contents of `~/anketo/public/` into `~/public_html/`
5. Update paths in `public_html/index.php`

#### 3. Set File Permissions

**Via SSH:**
```bash
cd ~/anketo
chmod -R 755 .
chmod -R 775 writable
chmod -R 664 writable/**/*
chmod 640 .env
```

**Via cPanel:**
- Use File Manager
- Right-click folders > Change Permissions > Set to 755
- Set `writable` folder and its subfolders to 775
- Set `.env` file to 640 (owner read/write, group read only)

#### 4. Create Database

**Via cPanel:**
1. Go to MySQL Database Wizard
2. Create database: `yourusername_anketo`
3. Create database user with strong password
4. Add user to database with ALL PRIVILEGES

**Via phpMyAdmin:**
```sql
CREATE DATABASE yourusername_anketo CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'yourusername_dbuser'@'localhost' IDENTIFIED BY 'StrongPassword123!';
GRANT ALL PRIVILEGES ON yourusername_anketo.* TO 'yourusername_dbuser'@'localhost';
FLUSH PRIVILEGES;
```

#### 5. Import Database Schema

**Option A: Run Migrations via SSH**
```bash
cd ~/anketo
php spark migrate
```

**Option B: Import SQL via phpMyAdmin**
1. On your **local machine**, export the database:
   ```bash
   mysqldump -u root -p anketo > anketo_schema.sql
   ```
2. Upload `anketo_schema.sql` to the server
3. Open **phpMyAdmin** on your hosting > select your database > go to **Import** tab
4. Choose the `.sql` file and click **Go**

#### 6. Configure Apache (if needed)

Create `.htaccess` inside `public_html/` (the web root, **not** the project root):
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php/$1 [L]
</IfModule>
```

> CodeIgniter 4 already ships this `.htaccess` inside the `public/` folder.
> It will be in place automatically if you copied `public/` contents to `public_html/`.

#### 7. Configure Nginx (if using Nginx)

Add to your Nginx config:
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/anketo/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }
}
```

#### 8. SSL Certificate (HTTPS)

**Via cPanel:**
1. Go to SSL/TLS Status
2. Click "Run AutoSSL"
3. Wait for certificate installation

**Via Let's Encrypt (SSH):**
```bash
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

#### 9. Test Your Application

1. Visit `https://yourdomain.com`
2. Login with default admin: `admin@anketo.com` / `admin123`
3. **IMPORTANT**: Change default password immediately!

#### 10. Post-Deployment Security

1. **Change default admin password**
   - Login as admin
   - Go to profile settings
   - Change password

2. **Secure sensitive files**
   ```bash
   # Do NOT remove .git if you plan to use git pull for updates
   # Instead, restrict access via .htaccess or server config
   chmod 640 .env
   ```

3. **Set up backups**
   - Configure automated database backups
   - Backup `writable/uploads` directory

4. **Monitor logs**
   ```bash
   tail -f writable/logs/log-*.log
   ```

5. **Update .env security**
   ```ini
   # Disable debug toolbar
   CI_ENVIRONMENT = production
   
   # Enable force HTTPS
   app.forceGlobalSecureRequests = true
   ```

#### 11. Common Issues & Solutions

**Issue: 500 Internal Server Error**
- Check file permissions (writable must be 777)
- Check PHP version (requires 8.0+)
- Check .env file permissions (644)
- Review error logs: `writable/logs/`

**Issue: Database Connection Failed**
- Verify database credentials in .env
- Check if database exists
- Verify user has proper privileges
- Test connection via phpMyAdmin

**Issue: Routes Not Working**
- Ensure mod_rewrite is enabled
- Check .htaccess file exists
- Verify Apache configuration
- For Nginx, check try_files directive

**Issue: File Uploads Not Working**
- Check `writable/uploads` permissions (777)
- Verify PHP upload_max_filesize and post_max_size
- Check disk space on server

#### 12. Performance Optimization

**Enable OPcache**
```ini
; In php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
```

**Configure Database Caching**
```ini
; In .env
cache.driver = file
cache.ttl = 3600
```

**Enable Gzip Compression**
```apache
; In .htaccess
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>
```

#### 13. Maintenance & Updates

**Regular Tasks:**
- Update dependencies: `composer update`
- Clear cache: `php spark cache:clear`
- Check logs: `tail -f writable/logs/log-*.log`
- Backup database regularly
- Monitor disk space

**Updating Application:**
```bash
# Backup current version
cp -r ~/anketo ~/anketo-backup-$(date +%Y%m%d)

# Pull latest changes (from project root, NOT public_html)
cd ~/anketo
git pull origin main

# Install any new dependencies
composer install --no-dev

# Run migrations
php spark migrate

# Clear cache
php spark cache:clear

# Re-copy any updated public assets to public_html
cp -r ~/anketo/public/assets/. ~/public_html/assets/
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## License

This project is licensed under the MIT License.

## Support

For support, email support@anketo.com or open an issue on GitHub.

## Credits

- Built with [CodeIgniter 4](https://codeigniter.com/)
- UI by [Bootstrap 5](https://getbootstrap.com/)
- Icons by [Font Awesome](https://fontawesome.com/)
- Drag & Drop by [SortableJS](https://sortablejs.github.io/Sortable/)