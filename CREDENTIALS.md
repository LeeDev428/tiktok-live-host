# TikTok Live Host Agency - Default Login Credentials

## Admin Account
- **Role:** Administrator
- **Username:** admin
- **Email:** admin@tiktok-live-host.com
- **Password:** admin123
- **Full Name:** System Administrator

## Live Seller Accounts

### Seller 1
- **Role:** Live Seller
- **Username:** seller1
- **Email:** seller1@tiktok-live-host.com
- **Password:** seller123
- **Full Name:** Jane Doe

### Seller 2
- **Role:** Live Seller
- **Username:** seller2
- **Email:** seller2@tiktok-live-host.com
- **Password:** seller456
- **Full Name:** John Smith

### Demo Seller
- **Role:** Live Seller
- **Username:** demo_seller
- **Email:** demo@tiktok-live-host.com
- **Password:** demo2024
- **Full Name:** Demo Seller

---

## Usage Instructions

### To view credentials:
```bash
php sql/seeder.php show
```

### To seed the database:
```bash
php sql/seeder.php seed
```

### To view in browser:
Visit: `http://localhost/tiktok-live-host/sql/seeder.php`

---

**Note:** All passwords are hashed using PHP's `password_hash()` function before being stored in the database for security.