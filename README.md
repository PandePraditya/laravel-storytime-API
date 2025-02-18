# Laravel Project
**Laravel Storytime**

## Introduction
This is a Laravel-based web application. Users can use it to read or manage their own stories, bookmark their favorites, and access various stories from each category.

## Features
- User authentication
- Create, Read, Update, Delete stories
- Toggle bookmarks and Get bookmarked stories
- Users profile for managing stories
- Stories management with building-wise categorization
- Stories filtering and sorting:
  - Get Stories Data by Categories
  - Get Stories Data by Authenticated Users
  - Get Stories Data by Popular sort of bookmakrs count
  - Get Stories Data by sort Descending of Title books
  - Get Stories Data by sort Ascending of Title Books
  - Get Stories Data by sort Newest of books latest

## Requirements
- PHP 8.1+
- Composer
- Laravel 10+
- MySQL 8+
- Apache or Nginx

## Installation

### 1. Clone the Repository
```bash
git clone https://github.com/yourusername/your-laravel-project.git
cd your-laravel-project
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Setup Environment
Copy the `.env.example` file and set up your environment variables.
```bash
cp .env.example .env
```
Or copy and paste it normally

Generate the application key:
```bash
php artisan key:generate
```

### 4. Configure Database
Edit the `.env` file and update database details:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```
Then, run migrations and seeders:
```bash
php artisan migrate --seed
```

### 5. Run the Application
```bash
php artisan serve
```
Your application should now be running at `http://127.0.0.1:8000`

## API Endpoints

### Authentication
| Method | Endpoint | Description |
|--------|---------|-------------|
| POST | /api/register | Register a new user |
| POST | /api/login | User login |
| POST | /api/logout | Logout (Requires authentication) |

### Stories
| Method | Endpoint | Description |
|--------|---------|-------------|
| GET | /api/stories | Fetch all stories (Public) |
| GET | /api/stories/{id} | Fetch a single story (Public) |
| GET | /api/user/stories | Get user stories (Authenticated) |
| POST | /api/stories | Create a new story (Authenticated) |
| PUT | /api/stories/{id} | Update a story (Authenticated) |
| DELETE | /api/stories/{id} | Delete a story (Authenticated) |

### Categories
| Method | Endpoint | Description |
|--------|---------|-------------|
| GET | /api/categories | Fetch all categories (Public) |

### User Profile
| Method | Endpoint | Description |
|--------|---------|-------------|
| GET | /api/user/details | Get user details (Authenticated) |
| PUT | /api/user/update-profile | Update user profile (Authenticated) |
| POST | /api/user/update-profile-image | Update profile image (Authenticated) |

### Bookmarks
| Method | Endpoint | Description |
|--------|---------|-------------|
| POST | /api/bookmarks/toggle | Toggle bookmark (Authenticated) |
| GET | /api/bookmarks | Get all bookmarks (Authenticated) |

## Contribution Guidelines
1. Fork the repository
2. Create a new branch (`your-branch-name`)
3. Commit your changes with descriptive messages
4. Submit a pull request

## License
This project is licensed under the MIT License.