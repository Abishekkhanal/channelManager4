# Grand Hotel Booking Engine

A fully functional Hotel Booking Engine Web Application built with PHP, MySQL, HTML, CSS, and JavaScript. This system includes a complete Property Management System (PMS) with OTA (Online Travel Agency) integration capabilities.

## Features

### 🏠 Public Website (index.php)
- **Hotel Overview**: Display hotel information, room categories, and image gallery
- **Contact Information**: Complete contact details and location
- **Booking Inquiry Form**: Public-facing form for booking requests
- **OTA Integration**: Links to WhatsApp and external booking platforms
- **Responsive Design**: Mobile-friendly interface

### 👤 Admin Authentication System
- **Secure Registration** (`signup.php`): Admin account creation with password hashing
- **Login System** (`login.php`): Secure authentication with PHP sessions
- **Session Management** (`logout.php`): Secure logout functionality
- **Access Control**: Protected admin area with session validation

### 🏨 Property Management System (PMS)

#### Room Management (`admin/rooms.php`)
- **CRUD Operations**: Add, Edit, Delete rooms
- **Room Details**: Name, type, description, pricing, occupancy
- **Amenities Management**: Checkbox-based amenity selection
- **Image Upload**: Multiple image support with primary image selection
- **Cancellation Policies**: Customizable cancellation terms

#### Booking Management (`admin/bookings.php`)
- **Booking Overview**: List all bookings with filtering options
- **Status Management**: Update booking status (pending, confirmed, cancelled)
- **Guest Information**: Complete guest details and contact info
- **Revenue Tracking**: Total amount and payment status
- **OTA Source Tracking**: Identify booking source (website, OTA partners)

#### Availability Management (`admin/availability.php`)
- **Calendar View**: Visual availability calendar
- **Bulk Updates**: Mass availability updates for date ranges
- **Real-time Status**: Available, limited, or fully booked indicators
- **Booking Integration**: Automatic availability reduction on confirmed bookings

#### Settings Management (`admin/settings.php`)
- **OTA Configuration**: API credentials for major OTA providers
- **Endpoint Management**: Configure API endpoints for each OTA
- **Connection Testing**: Test API connections before activation
- **Multi-OTA Support**: Booking.com, Expedia, Agoda, Airbnb, and custom integrations

### 📅 Channel Manager

#### Booking Reception (`receive_booking.php`)
- **Multi-format Support**: JSON and XML booking data parsing
- **API Security**: API key validation and IP whitelisting
- **Automatic Processing**: Guest info extraction and room assignment
- **Availability Checking**: Real-time availability validation
- **Duplicate Prevention**: Prevent duplicate bookings from same OTA

#### OTA Synchronization (`admin/sync_ota.php`)
- **Rate & Availability Sync**: Push current rates and availability to OTAs
- **Multi-OTA Support**: Format data according to each OTA's specifications
- **Batch Processing**: Sync multiple rooms and date ranges
- **Error Handling**: Comprehensive error logging and reporting
- **Manual & Automated Sync**: On-demand or scheduled synchronization

### 📊 Admin Dashboard (`admin/dashboard.php`)
- **Key Metrics**: Total bookings, revenue, occupancy rates
- **Recent Activity**: Latest bookings and inquiries
- **Quick Actions**: Direct links to common tasks
- **Statistics Overview**: Visual representation of hotel performance

## Technical Specifications

### Backend
- **PHP 7.4+**: Server-side scripting
- **MySQL 5.7+**: Database management
- **PDO**: Prepared statements for SQL injection prevention
- **Session Management**: Secure admin authentication
- **cURL**: External API communication

### Frontend
- **HTML5**: Semantic markup
- **CSS3**: Modern styling with Grid and Flexbox
- **Vanilla JavaScript**: Interactive functionality
- **Responsive Design**: Mobile-first approach

### Security Features
- **Password Hashing**: PHP `password_hash()` and `password_verify()`
- **SQL Injection Prevention**: Prepared statements throughout
- **XSS Protection**: Input sanitization and output escaping
- **CSRF Protection**: Session-based form validation
- **API Security**: Key-based authentication for OTA endpoints

## Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- XAMPP/WAMP (for local development)

### Database Setup
1. Create a new MySQL database named `hotel_booking`
2. Import the database schema:
   ```bash
   mysql -u root -p hotel_booking < database.sql
   ```

### Configuration
1. Update database credentials in `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'hotel_booking');
   ```

2. Create uploads directory with write permissions:
   ```bash
   mkdir uploads
   chmod 755 uploads
   ```

### First Time Setup
1. Access the application: `http://localhost/hotel-booking/`
2. Create admin account: `http://localhost/hotel-booking/signup.php`
3. Login to admin panel: `http://localhost/hotel-booking/login.php`
4. Configure OTA settings in Admin > Settings

## OTA Integration

### Supported OTA Providers
- **Booking.com**: XML API integration
- **Expedia**: EQC API support
- **Agoda**: XML API integration
- **Airbnb**: REST API support
- **Custom APIs**: Flexible integration framework

### API Endpoints
- **Receive Bookings**: `POST /receive_booking.php`
- **Sync Data**: Triggered from admin panel

### Sample OTA Booking Request
```json
{
  "api_key": "your_api_key",
  "guest_name": "John Doe",
  "guest_email": "john@example.com",
  "room_type": "Deluxe",
  "check_in": "2024-03-15",
  "check_out": "2024-03-17",
  "guests_count": 2,
  "total_amount": 300.00,
  "booking_id": "OTA123456"
}
```

## File Structure
```
hotel-booking/
├── admin/
│   ├── dashboard.php
│   ├── rooms.php
│   ├── bookings.php
│   ├── availability.php
│   ├── settings.php
│   └── sync_ota.php
├── config/
│   └── database.php
├── uploads/
├── index.php
├── signup.php
├── login.php
├── logout.php
├── receive_booking.php
├── database.sql
└── README.md
```

## Usage Guide

### For Hotel Managers
1. **Initial Setup**: Create admin account and configure basic settings
2. **Room Management**: Add rooms with details, images, and pricing
3. **Availability**: Set room availability for future dates
4. **OTA Setup**: Configure API credentials for booking platforms
5. **Monitoring**: Use dashboard to track bookings and revenue

### For Developers
1. **Customization**: Modify templates and add new features
2. **OTA Integration**: Add new OTA providers using existing framework
3. **API Extensions**: Extend booking reception for new data formats
4. **Security**: Implement additional security measures for production

## Testing

### Test OTA Booking Reception
Use the provided test script to simulate OTA bookings:
```bash
curl -X POST http://localhost/hotel-booking/receive_booking.php \
  -H "Content-Type: application/json" \
  -d '{"api_key":"test_key","guest_name":"Test Guest","check_in":"2024-03-15","check_out":"2024-03-17"}'
```

## Production Deployment

### Security Checklist
- [ ] Change default database credentials
- [ ] Enable SSL/HTTPS
- [ ] Set proper file permissions
- [ ] Configure firewall rules
- [ ] Enable PHP error logging
- [ ] Set up regular database backups
- [ ] Configure OTA API rate limiting

### Performance Optimization
- [ ] Enable PHP OPcache
- [ ] Configure MySQL query cache
- [ ] Implement CDN for static assets
- [ ] Set up database indexing
- [ ] Configure web server caching

## Support & Maintenance

### Regular Tasks
- Monitor OTA sync logs
- Update room availability
- Review booking confirmations
- Backup database regularly
- Update OTA API credentials

### Troubleshooting
- Check error logs in `/var/log/apache2/` or `/var/log/nginx/`
- Verify database connections
- Test OTA API endpoints
- Review PHP error logs

## License
This project is open-source and available under the MIT License.

## Contributing
Contributions are welcome! Please read the contributing guidelines and submit pull requests for any improvements.

---

**Note**: This is a demonstration system. For production use, implement additional security measures, error handling, and performance optimizations based on your specific requirements.