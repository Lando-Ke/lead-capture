# Lead Capture Form - Laravel 12 + Vue.js

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12-red.svg" alt="Laravel 12">
  <img src="https://img.shields.io/badge/Vue.js-3-green.svg" alt="Vue.js 3">
  <img src="https://img.shields.io/badge/Tailwind-CSS-blue.svg" alt="Tailwind CSS">
  <img src="https://img.shields.io/badge/Pinia-State-yellow.svg" alt="Pinia">
  <img src="https://img.shields.io/badge/Tests-Vitest%20%7C%20PHPUnit-brightgreen.svg" alt="Testing">
</p>

A professional, multi-step lead capture form built with Laravel 12 and Vue.js 3. Features a clean, responsive design with platform selection, form validation, and a staff-level architecture using service repository patterns.

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

## 🛠 Technology Stack

### **Backend**

- **Laravel 12** - PHP framework with modern features
- **PHP 8.2+** - Latest PHP version with strong typing
- **MySQL** - Primary database
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

- **PHP 8.2+**
- **Node.js 18+**
- **Composer 2.0+**
- **MySQL 8.0+**
- **Git**

## ⚙️ Installation

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/lead-capture.git
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

Update `.env` with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lead_capture
DB_USERNAME=your_username
DB_PASSWORD=your_password
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

```bash
# Laravel development server
php artisan serve --port=8001

# Frontend development server (separate terminal)
npm run dev
```

Visit `http://localhost:8001` to see the application.

## 🏗 Architecture

### **Service Repository Pattern**

```
app/
├── Contracts/              # Interfaces
│   ├── LeadRepositoryInterface.php
│   ├── LeadServiceInterface.php
│   └── PlatformRepositoryInterface.php
├── DTOs/                   # Data Transfer Objects
│   ├── LeadDTO.php
│   └── PlatformDTO.php
├── Services/               # Business Logic
│   ├── LeadService.php
│   └── PlatformService.php
├── Repositories/           # Data Access
│   ├── LeadRepository.php
│   └── PlatformRepository.php
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
│   └── FormSteps/                # Individual step components
│       ├── BasicInformationStep.vue
│       ├── WebsiteDetailsStep.vue
│       ├── PlatformSelectionStep.vue
│       └── ReviewStep.vue
├── stores/                       # Pinia state management
│   ├── leadStore.js
│   └── platformStore.js
└── tests/                        # Frontend tests
    ├── LeadCaptureForm.test.js
    └── leadStore.test.js
```

## 📡 API Documentation

### **Endpoints**

| Method | Endpoint                               | Description                   |
| ------ | -------------------------------------- | ----------------------------- |
| `GET`  | `/api/v1/platforms`                    | Get all active platforms      |
| `GET`  | `/api/v1/platforms?type={websiteType}` | Get platforms by website type |
| `POST` | `/api/v1/leads`                        | Submit a new lead             |
| `GET`  | `/api/v1/leads/{email}/check`          | Check if email exists         |

### **Platform Selection by Website Type**

- **E-commerce**: Shopify, WooCommerce, BigCommerce, Magento, Custom Solution, Other
- **Blog**: WordPress, Squarespace, Webflow, Custom Developed, Other
- **Business**: WordPress, Squarespace, Webflow, Custom Developed, Other
- **Portfolio**: WordPress, Squarespace, Webflow, Custom Developed, Other
- **Other**: WordPress, Squarespace, Webflow, Custom Developed, Other

### **Request/Response Examples**

**Submit Lead:**

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
php artisan test --filter=LeadSubmissionTest
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

- **Backend**: Feature tests for API endpoints, unit tests for services
- **Frontend**: Component tests, store tests, integration tests
- **API Integration**: End-to-end API testing

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

# Cache (recommended)
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

## 📊 Performance Features

- **Database Optimization**: Proper indexing on search columns
- **Caching**: Platform data cached for 24 hours
- **Lazy Loading**: Frontend components loaded on demand
- **Asset Optimization**: Vite handles bundling and optimization
- **API Resources**: Consistent, lightweight response formatting

## 🔒 Security Features

- **CSRF Protection**: Laravel CSRF token validation
- **Rate Limiting**: API endpoint throttling
- **Input Validation**: Comprehensive backend validation
- **SQL Injection Prevention**: Eloquent ORM protection
- **XSS Protection**: Vue.js template escaping

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### **Development Guidelines**

- Follow PSR-12 coding standards for PHP
- Use ESLint and Prettier for JavaScript
- Write tests for new features
- Update documentation for API changes
- Follow conventional commit messages

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

If you encounter any issues or need help:

1. Check the [Issues](https://github.com/yourusername/lead-capture/issues) page
2. Create a new issue with detailed description
3. Include steps to reproduce the problem
4. Provide environment details (PHP version, Node version, etc.)

## 🙏 Acknowledgments

- **Laravel Team** - For the amazing framework
- **Vue.js Team** - For the reactive frontend framework
- **Tailwind CSS** - For the utility-first CSS framework
- **Heroicons** - For the beautiful icon set

---

<p align="center">Built with ❤️ using Laravel 12 and Vue.js 3</p>
