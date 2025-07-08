# CashiRu BACKEND - Laravel API Backend for POS Mobile App

This project is a backend API built with Laravel, designed for a Point of Sale (POS) or cashier mobile application developed in Flutter. It also includes an admin panel powered by Filament for easy management.

## Features

- RESTful API for POS/Cashier mobile apps (Flutter)
- Secure authentication and user management
- Product, inventory, and transaction management
- Admin panel using Filament for easy administration

## Requirements

- PHP >= 8.1
- Composer
- MySQL or compatible database
- Laravel Framework
- Node.js & npm (for frontend assets, if needed)

## Installation

1. Clone the repository:
    ```
    git clone https://github.com/yourusername/your-repo.git
    cd your-repo
    ```

2. Install dependencies:
    ```
    composer install
    ```

3. Copy `.env.example` to `.env` and configure your environment variables.

4. Generate application key:
    ```
    php artisan key:generate
    ```

5. Run migrations:
    ```
    php artisan migrate
    ```

6. (Optional) Install Filament Admin Panel:
    ```
    composer require filament/filament
    php artisan filament:install
    ```

7. Start the development server:
    ```
    php artisan serve
    ```

## Usage

- Use the provided API endpoints in your Flutter mobile app for POS operations.
- Access the admin panel at `/admin` for management tasks.

## License

This project is open-source and available under the [MIT License](LICENSE).