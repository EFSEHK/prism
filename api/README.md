# LASK (Laravel API Starter Kit)

**LASK** is a lightweight, reusable backend starter kit built on Laravel 12 and PHP 8.2, designed to accelerate your API-driven projects.  
It provides a robust foundation with essential features pre-configured, so you can focus on building your unique application modules faster.

---

## Features

- **Authentication** with Laravel Sanctum (token expiration configurable, default 30 minutes)  
- **Soft Deletes** included by default in models and migrations  
- **Comprehensive Logging**  
  - Basic file logs in `storage/app/logs`  
  - Activity logs (login, logout, failed attempts) stored in database  
  - Middleware to log every API request  
  - DB query update logging  
- **Role & Permission Management** using Laravel’s official roles/permissions package  
- **Dynamic Navigation** stored in DB for frontend consumption  
- **Logs Viewer** powered by Laravel Telescope and other Laravel logging packages  
  - Telescope report can be viewed at {{baseUrl}}/telescope-login
  - Laravel logs can be viewed at {{baseUrl}}/logs

---

## Requirements

- PHP ^8.2  
- Laravel Framework ^12.0  
- Composer  
- Database (MySQL, PostgreSQL, SQLite, etc.) supported by Laravel  

---

## Installation

1. Clone or copy LASK to your new project folder:  

    git clone <your-lask-repo-url> my-new-project  
    cd my-new-project

2. Install dependencies via Composer:  

    composer install

3. Copy `.env.example` to `.env` and configure your environment variables (database, Sanctum, etc.):  

    cp .env.example .env

4. Generate application key:  

    php artisan key:generate

5. Run database migrations:  

    php artisan migrate

6. Serve the application locally:  

    php artisan serve

---

## Usage

LASK is a backend-only starter kit. Any frontend framework (Vue, React, Angular, mobile apps) can consume APIs via REST calls.  
Extend or replace default models, controllers, middleware, and routes as needed for your application.

---

## Why LASK?

- Save time on boilerplate setup  
- Consistent, secure authentication & authorization out-of-the-box  
- Built-in logging for debugging and auditing  
- Scalable foundation for modular API projects  

---

## Contributing

LASK is designed as a personal reusable base, but contributions and suggestions are welcome.

---

Made with ❤️ by  
**Shaad Ahmed**  
Software/Web App Developer  
Rawalpindi, Pakistan  
[LinkedIn](https://www.linkedin.com/in/shaad93/) | [GitHub](https://github.com/shaadahmed)  

---

## Contact

For questions or collaboration, feel free to reach out to me at shaadahmed93@gmail.com  
