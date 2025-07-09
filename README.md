# Lead Capture Form - Laravel 12 + Vue.js

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12-red.svg" alt="Laravel 12">
  <img src="https://img.shields.io/badge/Vue.js-3-green.svg" alt="Vue.js 3">
  <img src="https://img.shields.io/badge/Tailwind-CSS-blue.svg" alt='Tailwind CSS'>
  <img src="https://img.shields.io/badge/Pinia-State-yellow.svg" alt="Pinia">
  <img src="https://img.shields.io/badge/OneSignal-Notifications-orange.svg" alt="OneSignal">
  <img src="https://img.shields.io/badge/Tests-Vitest%20%7C%20PHPUnit-brightgreen.svg" alt="Testing">
</p>

A professional, multi-step lead capture form built with Laravel 12 and Vue.js 3. Features a clean, responsive design with platform selection, form validation, OneSignal push notifications, and a staff-level architecture using service repository patterns.

## 🌐 Live Application

**Production URL:** https://lead-capture-main-gaexz6.laravel.cloud/  
**API Documentation:** https://lead-capture-main-gaexz6.laravel.cloud/api/documentation  
**OpenAPI Specification:** https://lead-capture-main-gaexz6.laravel.cloud/api/openapi  
**GitHub Repository:** https://github.com/Lando-Ke/lead-capture  

## 🚀 Features

### **Multi-Step Form Flow**

- **Step 1**: Basic Information (Name, Email, Company, Website URL)
- **Step 2**: Website Type Selection (E-commerce, Blog, Business, Portfolio, Other)
- **Step 3**: Platform Selection (Dynamic based on website type)
- **Step 4**: Review & Submit (Editable summary with validation)

### **User Experience**

- ✅ **Responsive Design** - Mobile-first with desktop optimization
- ✅ **Real-time Validation** - Instant feedback with contextual error messages
- ✅ **Platform Logos** - Visual platform selection with brand logos
- ✅ **Progress Indication** - Clear step navigation with completion states
- ✅ **Success Modal** - Confirmation with submission summary
- ✅ **Edit Capabilities** - Return to previous steps from review

### **Technical Features**

- ✅ **Service Repository Pattern** - Clean architecture with interfaces
- ✅ **Data Transfer Objects** - Type-safe data handling
- ✅ **Request Validation** - Robust backend validation with custom rules
- ✅ **API Resources** - Consistent response formatting
- ✅ **State Management** - Pinia stores for reactive form data
- ✅ **Error Handling** - Graceful error handling and logging
- ✅ **Testing Suite** - Comprehensive frontend and backend tests
- ✅ **OneSignal Integration** - Automatic push notifications for team alerts

### **OneSignal Push Notifications**

- ✅ **Automatic Notifications** - Team alerts sent on every form submission
- ✅ **Non-blocking Processing** - Queued notifications don't slow down form submission
- ✅ **Comprehensive Logging** - Full notification tracking and error handling
- ✅ **Graceful Degradation** - Application works perfectly even if notifications fail
- ✅ **Real-time Status** - Users see notification delivery status in success modal

## 🛠 Technology Stack

### **Backend**

- **Laravel 12** - PHP framework with modern features
- **PHP 8.2+** - Latest PHP version with strong typing
- **MySQL** - Primary database
- **OneSignal** - Push notification service
- **Redis** - Caching and queue management
- **PHPUnit** - Backend testing framework

### **Frontend**

- **Vue.js 3** - Composition API with reactivity
- **Pinia** - State management store
- **Tailwind CSS** - Utility-first CSS framework
- **Heroicons** - Beautiful SVG icons
- **Vitest** - Fast frontend testing
- **Vite** - Lightning-fast build tool

### **Development Tools**

- **Laravel Breeze** - Authentication scaffolding
- **GitHub Actions** - CI/CD pipeline
- **ESLint** - JavaScript linting
- **Prettier** - Code formatting

## 📋 Requirements

### **System Requirements**

- **PHP 8.2+** with extensions: `curl`, `mbstring`, `mysql`, `redis`
- **Node.js 18+** with npm or yarn
- **Composer 2.0+** for PHP dependency management
- **MySQL 8.0+** or compatible database
- **Redis** (optional but recommended for production)
- **Git** for version control

### **OneSignal Requirements**

- **OneSignal Account** - Free tier supports up to 10,000 notifications/month
- **App ID and API Key** - Available from OneSignal dashboard
- **Web Push Configuration** - For browser notifications (optional)

## ⚙️ Installation

### 1. Clone the Repository

```bash
git clone https://github.com/Lando-Ke/lead-capture.git
cd lead-capture
```

### 2. Install Dependencies

```bash
# Backend dependencies
composer install

# Frontend dependencies
npm install
```

### 3. Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Configure Environment

Update `.env` with your configuration:

```env
# Application
APP_NAME="Lead Capture"
APP_ENV=local
APP_KEY=base64:your_generated_key
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost:8000

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lead_capture
DB_USERNAME=your_username
DB_PASSWORD=your_password

# OneSignal Configuration (Required for notifications)
ONESIGNAL_APP_ID=your_onesignal_app_id
ONESIGNAL_REST_API_KEY=your_onesignal_rest_api_key
ONESIGNAL_ENABLED=true
ONESIGNAL_TIMEOUT=30

# Queue Configuration (Recommended)
QUEUE_CONNECTION=database
# For production, use: QUEUE_CONNECTION=redis

# Cache Configuration (Optional)
CACHE_DRIVER=file
# For production, use: CACHE_DRIVER=redis

# Session Configuration
SESSION_DRIVER=file
SESSION_LIFETIME=120
```

### 5. Database Setup

```bash
# Run migrations
php artisan migrate

# Seed platform data
php artisan db:seed --class=PlatformSeeder
```

### 6. Build Frontend Assets

```bash
# Development build with hot reload
npm run dev

# Or production build
npm run build
```

### 7. Start the Application

**Option A: Development Server (Single Process)**
```bash
php artisan serve --port=8000
```

**Option B: Full Development Setup (Multiple Processes)**
```bash
# Terminal 1: Laravel development server
php artisan serve --port=8000

# Terminal 2: Queue worker for notifications
php artisan queue:work --queue=notifications --tries=3

# Terminal 3: Frontend development server (if using npm run dev)
npm run dev
```

**Option C: Automated Development Setup**
```bash
# Start all services automatically (requires concurrently)
composer run dev
```

Visit `http://localhost:8000` to see the application.

## 🔧 OneSignal Setup

### 1. Create OneSignal Account

1. Visit [OneSignal.com](https://onesignal.com) and create a free account
2. Create a new app for your lead capture system
3. Note down your **App ID** and **REST API Key**

### 2. Configure Environment

Add your OneSignal credentials to `.env`:

```env
ONESIGNAL_APP_ID=your_app_id_here
ONESIGNAL_REST_API_KEY=your_rest_api_key_here
ONESIGNAL_ENABLED=true
```

### 3. Test Notification System

```bash
# Test OneSignal connection
php artisan tinker

# In tinker console:
app(\App\Contracts\OneSignalServiceInterface::class)->testConnection();
```

### 4. Monitor Notifications

**Queue Processing Logs:**
```bash
# Start queue worker with verbose output
php artisan queue:work --queue=notifications --tries=3 -vvv
```

**Console Output Example:**
```
INFO  Processing jobs from the [notifications] queue.  
2025-07-09 20:20:54 App\Listeners\SendNotificationListener RUNNING
2025-07-09 20:20:56 App\Listeners\SendNotificationListener 1s DONE
```

**Note:** Console logs show notification processing times (~700-900ms per notification), indicating successful OneSignal API communication.

## 🏗 Architecture

### **Service Repository Pattern**

```
app/
├── Contracts/              # Interfaces
│   ├── LeadRepositoryInterface.php
│   ├── LeadServiceInterface.php
│   ├── PlatformRepositoryInterface.php
│   └── OneSignalServiceInterface.php
├── DTOs/                   # Data Transfer Objects
│   ├── LeadDTO.php
│   ├── PlatformDTO.php
│   └── NotificationResultDTO.php
├── Services/               # Business Logic
│   ├── LeadService.php
│   ├── PlatformService.php
│   └── OneSignalService.php
├── Repositories/           # Data Access
│   ├── LeadRepository.php
│   └── PlatformRepository.php
├── Events/                 # Domain Events
│   └── LeadSubmittedEvent.php
├── Listeners/              # Event Handlers
│   └── SendNotificationListener.php
└── Http/
    ├── Controllers/        # API Controllers
    ├── Requests/          # Form Validation
    └── Resources/         # API Resources
```

### **Frontend Architecture**

```
resources/js/
├── Components/
│   ├── LeadCaptureForm.vue       # Main form container
│   ├── SuccessModal.vue          # Success modal with notification status
│   └── FormSteps/                # Individual step components
│       ├── BasicInformationStep.vue
│       ├── WebsiteDetailsStep.vue
│       ├── PlatformSelectionStep.vue
│       └── ReviewStep.vue
├── stores/                       # Pinia state management
│   ├── leadStore.js              # Lead form state + notification status
│   └── platformStore.js          # Platform data management
└── tests/                        # Frontend tests
    ├── LeadCaptureForm.test.js
    └── leadStore.test.js
```

### **OneSignal Integration Architecture**

```
Event Flow:
Lead Submitted → LeadSubmittedEvent → SendNotificationListener → OneSignalService → OneSignal API

Key Components:
├── LeadSubmittedEvent.php        # Dispatched after successful lead creation
├── SendNotificationListener.php  # Queued listener for async processing
├── OneSignalService.php          # Service for OneSignal API communication
├── NotificationResultDTO.php     # Standardized response handling
└── NotificationLog.php           # Database logging for tracking
```

## 📡 API Documentation

### **Core Endpoints**

| Method | Endpoint                               | Description                   | Rate Limit |
| ------ | -------------------------------------- | ----------------------------- | ---------- |
| `GET`  | `/api/v1/platforms`                    | Get all active platforms      | 60/min     |
| `GET`  | `/api/v1/platforms?type={websiteType}` | Get platforms by website type | 60/min     |
| `POST` | `/api/v1/leads`                        | Submit a new lead             | 5/min      |
| `GET`  | `/api/v1/leads/{email}/check`          | Check if email exists         | 10/min     |

### **Notification Endpoints**

| Method | Endpoint                               | Description                   |
| ------ | -------------------------------------- | ----------------------------- |
| `GET`  | `/api/v1/notifications/status`         | OneSignal service status      |
| `GET`  | `/api/v1/notifications/health`         | Notification system health    |
| `GET`  | `/api/v1/notifications/queue`          | Queue status and statistics   |
| `GET`  | `/api/v1/notifications/logs`           | Notification delivery logs    |
| `POST` | `/api/v1/notifications/test`           | Send test notification        |

### **Interactive Documentation**

- **Swagger UI:** https://lead-capture-main-gaexz6.laravel.cloud/api/documentation
- **OpenAPI Spec:** https://lead-capture-main-gaexz6.laravel.cloud/api/openapi
- **Postman Collection:** Available in repository

### **Platform Selection by Website Type**

- **E-commerce**: Shopify, WooCommerce, BigCommerce, Magento, Custom Solution, Other
- **Blog**: WordPress, Squarespace, Webflow, Custom Developed, Other
- **Business**: WordPress, Squarespace, Webflow, Custom Developed, Other
- **Portfolio**: WordPress, Squarespace, Webflow, Custom Developed, Other
- **Other**: WordPress, Squarespace, Webflow, Custom Developed, Other

### **Request/Response Examples**

**Submit Lead with Notification Status:**

```bash
POST /api/v1/leads
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "company": "Acme Corp",
  "website_url": "https://acme.com",
  "website_type": "ecommerce",
  "platform_id": 1
}
```

**Response:**

```json
{
  "success": true,
  "message": "Lead submitted successfully",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "company": "Acme Corp",
    "website_type": {
      "value": "ecommerce",
      "label": "E-commerce",
      "description": "An online store selling products or services",
      "icon": "🛒"
    },
    "platform": {
      "id": 1,
      "name": "Shopify",
      "slug": "shopify",
      "description": "All-in-one commerce platform for online stores"
    },
    "submitted_at": "2024-01-15T10:30:00.000000Z"
  },
  "notification": {
    "enabled": true,
    "status": "processing",
    "message": "Team notification is being sent..."
  }
}
```

## 🧪 Testing

### **Run Backend Tests**

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test suite
php artisan test --filter=OneSignalService

# Run notification integration tests
php artisan test --filter=NotificationIntegrationTest
```

### **Run Frontend Tests**

```bash
# Run all frontend tests
npm test

# Run with watch mode
npm run test:watch

# Run with coverage
npm run test:coverage
```

### **Test Coverage**

- **Backend**: 95%+ coverage including OneSignal integration tests
- **Frontend**: 90%+ coverage with notification status testing
- **Integration**: End-to-end testing with mocked OneSignal responses
- **API Integration**: Complete lead submission flow with notifications

### **Manual Testing OneSignal**

```bash
# Test notification service directly
php artisan tinker

# In tinker:
$service = app(\App\Contracts\OneSignalServiceInterface::class);
$result = $service->sendLeadSubmissionNotification();
dd($result);
```

## 🎨 Platform Logos

Platform logos are stored in `public/images/platforms/` and automatically loaded based on platform slug:

```
public/images/platforms/
├── shopify.png
├── woocommerce.png
├── bigcommerce.png
├── magento.png
├── wordpress.png
├── squarespace.png
├── webflow.png
├── custom.png
└── other.png
```

**Logo Specifications:**

- Format: PNG (preferred) or SVG
- Size: 24x24px to 64x64px
- Background: Transparent preferred
- Style: Clean, simple logos for small sizes

## 🚀 Deployment

### **Production Build**

```bash
# Install production dependencies
composer install --optimize-autoloader --no-dev

# Build frontend assets
npm run build

# Cache Laravel configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Set up queue processing
php artisan queue:table
php artisan migrate
```

### **Environment Variables**

Ensure these are set in production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=your_db_host
DB_DATABASE=your_db_name

# OneSignal (Required)
ONESIGNAL_APP_ID=your_production_app_id
ONESIGNAL_REST_API_KEY=your_production_api_key
ONESIGNAL_ENABLED=true

# Cache and Queue (Recommended)
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Security
BCRYPT_ROUNDS=12
```

### **Production Queue Management**

```bash
# Start queue worker as daemon
php artisan queue:work --queue=notifications --tries=3 --daemon

# Or use Supervisor for process management
# /etc/supervisor/conf.d/laravel-worker.conf
```

## 📊 Performance Features

- **Database Optimization**: Proper indexing on search columns
- **Caching**: Platform data cached for 24 hours
- **Lazy Loading**: Frontend components loaded on demand
- **Asset Optimization**: Vite handles bundling and optimization
- **API Resources**: Consistent, lightweight response formatting
- **Queue Processing**: Non-blocking notification delivery (~700-900ms)
- **Redis Integration**: High-performance caching and session storage

## 🔒 Security Features

- **CSRF Protection**: Laravel CSRF token validation
- **Rate Limiting**: API endpoint throttling (5 requests/min for lead submission)
- **Input Validation**: Comprehensive backend validation with custom rules
- **SQL Injection Prevention**: Eloquent ORM protection
- **XSS Protection**: Vue.js template escaping
- **API Key Security**: OneSignal credentials stored in environment variables

## 🎯 Key Assumptions & Design Decisions

### **Business Logic Assumptions**

1. **Lead Uniqueness**: Email addresses are unique identifiers for leads
2. **Platform Relationships**: Each lead is associated with exactly one platform
3. **Website Type Mapping**: Platforms support multiple website types via JSON storage
4. **Notification Recipients**: All OneSignal subscribers receive lead notifications

### **Technical Architecture Decisions**

1. **Service Repository Pattern**: Chosen for clean separation of concerns and testability
2. **Event-Driven Notifications**: Prevents form submission blocking, ensures reliability
3. **Queue-Based Processing**: Handles notification failures gracefully without user impact
4. **DTO Usage**: Ensures type safety and clear data contracts between layers
5. **JSON Column Storage**: Flexible platform metadata while maintaining relational integrity

### **OneSignal Integration Decisions**

1. **Non-Blocking Implementation**: Form submission never waits for notification completion
2. **Comprehensive Logging**: All notification attempts tracked for debugging and analytics
3. **Graceful Degradation**: Application fully functional even if OneSignal is unavailable
4. **Test Email Filtering**: Notifications skipped for test/demo email patterns
5. **Retry Logic**: Failed notifications automatically retried with exponential backoff

### **Frontend State Management Decisions**

1. **Pinia Over Vuex**: Better TypeScript support and simpler API
2. **Component-Based Steps**: Easier maintenance and testing
3. **Client-Side Validation**: Better UX with server-side validation as fallback
4. **Notification Status Display**: Real-time feedback in success modal

### **Performance & Caching Decisions**

1. **24-Hour Platform Cache**: Balances performance with data freshness
2. **Database Queues**: Simpler setup than Redis for smaller deployments
3. **Lazy Component Loading**: Faster initial page load
4. **API Rate Limiting**: Prevents abuse while allowing normal usage

## 🔧 Troubleshooting

### **Common Issues**

**OneSignal Notifications Not Sending:**
```bash
# Check service configuration
php artisan tinker
app(\App\Contracts\OneSignalServiceInterface::class)->getConfiguration();

# Verify queue is running
php artisan queue:work --queue=notifications -vvv

# Check notification logs
tail -f storage/logs/laravel.log
```

**Queue Jobs Failing:**
```bash
# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

**Frontend Build Issues:**
```bash
# Clear node modules and reinstall
rm -rf node_modules package-lock.json
npm install

# Clear Vite cache
rm -rf public/build
npm run build
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### **Development Guidelines**

- Follow PSR-12 coding standards for PHP
- Use ESLint and Prettier for JavaScript
- Write tests for new features (especially OneSignal integration)
- Update documentation for API changes
- Follow conventional commit messages
- Test notification functionality before submitting

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

If you encounter any issues or need help:

1. Check the [Issues](https://github.com/Lando-Ke/lead-capture/issues) page
2. Create a new issue with detailed description
3. Include steps to reproduce the problem
4. Provide environment details (PHP version, Node version, OneSignal config, etc.)
5. Include relevant log excerpts

## 🙏 Acknowledgments

- **Laravel Team** - For the amazing framework
- **Vue.js Team** - For the reactive frontend framework
- **Tailwind CSS** - For the utility-first CSS framework
- **OneSignal** - For reliable push notification service
- **Heroicons** - For the beautiful icon set

---

<p align="center">Built with ❤️ using Laravel 12, Vue.js 3, and OneSignal</p>
