# WebEngineering Project

A comprehensive web application for tracking applicants and managing application processes with a robust API system.

## Overview

This project is a PHP-based web application that provides a platform for tracking and managing applicants. It features a complete user management system, an API with authentication, and a responsive dashboard interface.

## Features

- **User Management**
  - Registration and authentication
  - User profile management
  - Password recovery
  
- **Applicant Tracking**
  - View and manage applicants
  - Categories and seasons
  - Detailed applicant information
  
- **API System**
  - RESTful API endpoints
  - API key management
  - Comprehensive documentation
  
- **Administration**
  - User management
  - API key oversight
  - File uploading system
  
- **Reporting**
  - Custom report generation
  - Data visualization

## Installation

1. Clone the repository:
   ```
   git clone https://github.com/yourusername/WebEngineering.git
   ```

2. Install dependencies:
   ```
   composer install
   ```

3. Set up your database:
   - Create a new MySQL database
   - Configure connection details in `database/db_connect.php`

4. Configure your web server:
   - Point your web server to the project root
   - Ensure `.htaccess` is properly configured

5. Visit the application in your browser and follow setup instructions

## Project Structure

- `api` - API endpoints and middleware
- `components` - Reusable UI components
- `database` - Database connection and models
- `pages` - Application views and controllers
- `utils` - Utility functions
- `vendor` - Composer dependencies

## API Documentation

The API documentation is available within the application at `pages/documentation.php`. It provides detailed information about:

- Available endpoints
- Authentication methods
- Request/response formats
- Example usage in multiple languages

## Technologies

- PHP
- MySQL
- Bootstrap 5
- JavaScript
- Font Awesome
- PHPMailer

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer

## Security

- API requests are authenticated via API keys
- Password reset functionality with email verification
- Session-based authentication
- HTTPS recommended for production

## Team Members (Omada1)
- Andreas Nikitas
- Antreas Pelekanos
- Dimitrios Kaouris
- Kirikos Stavrides
- Theodosis Xenophontos
- Panayotis Antoniou

## License

This project is proprietary and confidential. Unauthorized copying, modification, distribution, or use is strictly prohibited.
