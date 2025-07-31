<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# BPKAD Meeting Scheduler API

This project is a Laravel-based backend application designed to manage meeting schedules, including integration with Zoom for online meetings. It provides a robust API for creating, updating, and deleting meetings, handling participant management, and ensuring no time conflicts for meeting locations.

## Features

- **Meeting Management**: Create, read, update, and delete various types of meetings (online, offline, hybrid).
- **Zoom Integration**: Seamlessly create and manage Zoom meetings directly from the application.
- **Participant Management**: Attach and manage participants for each meeting.
- **Location Conflict Detection**: Prevents scheduling conflicts for physical meeting locations.
- **Multiple Zoom Account Support**: Configure and utilize multiple Zoom accounts to handle concurrent meeting limits.
- **Role-Based Access Control**: Secure API access with roles and permissions using Spatie Laravel Permission.
- **API Authentication**: Uses Laravel Sanctum for stateless API authentication.
- **Docker Support**: Comes with a Docker configuration for easy setup and deployment.

## Technologies Used

- **[Laravel](https://laravel.com/)**: PHP Framework for the backend API.
- **[MySQL](https://www.mysql.com/)/[PostgreSQL](https://www.postgresql.org/)**: Database for storing meeting and user data.
- **[Zoom API](https://marketplace.zoom.us/docs/api-reference/zoom-api)**: For online meeting creation and management.
- **[Docker](https://www.docker.com/)**: For containerization and simplified development environment.
- **[Laravel Sanctum](https://laravel.com/docs/sanctum)**: For API authentication.
- **[Spatie Laravel Permission](https://spatie.be/docs/laravel-permission/v6/introduction)**: For role and permission management.

## Getting Started

You can set up the project using either Docker (recommended) or a manual local setup.

### Prerequisites

- PHP >= 8.2
- Composer
- Docker (for Docker-based setup)
- A local database server (for manual setup)

### Docker Setup (Recommended)

1.  **Clone the repository**:
    ```bash
    git clone <repository-url>
    cd bpkad-zoom-laravel-be
    ```

2.  **Copy Environment File**:
    ```bash
    cp .env.example .env
    ```
    *Ensure the `DB_HOST` in the `.env` file is set to the name of your database service in `docker-compose.yml` (e.g., `postgres` or `mysql`).*

3.  **Build and Run the Containers**:
    ```bash
    docker-compose -f docker/docker-compose.yml up -d --build
    ```

4.  **Install Dependencies and Run Migrations**:
    ```bash
    docker-compose -f docker/docker-compose.yml exec app composer install
    docker-compose -f docker/docker-compose.yml exec app php artisan key:generate
    docker-compose -f docker/docker-compose.yml exec app php artisan migrate --seed
    ```

The application will be accessible at `http://localhost:8080` (or as configured in your `docker-compose.yml`).

### Manual Installation

1.  **Clone the repository**:
    ```bash
    git clone <repository-url>
    cd bpkad-zoom-laravel-be
    ```

2.  **Install PHP Dependencies**:
    ```bash
    composer install
    ```

3.  **Copy Environment File**:
    ```bash
    cp .env.example .env
    ```

4.  **Generate Application Key**:
    ```bash
    php artisan key:generate
    ```

5.  **Configure Environment Variables**:
    Open the `.env` file and configure your database connection and Zoom API credentials.

6.  **Run Database Migrations and Seeders**:
    ```bash
    php artisan migrate --seed
    ```
    This will set up your database tables and seed initial data, including roles and permissions.

7.  **Start the Development Server**:
    ```bash
    php artisan serve
    ```
    The application will be accessible at `http://127.0.0.1:8000`.

## Configuration

Key configuration details are in the `.env` file:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bpkad_zoom_meetings
DB_USERNAME=root
DB_PASSWORD=
```

**Zoom API Credentials**: Unlike other settings, Zoom API credentials are not configured in the `.env` file. They must be configured through the [Application Settings API](./docs/api/settings.md) after the initial setup. This allows for managing multiple Zoom accounts dynamically.

## API Documentation

Detailed API documentation can be found in the [`docs/api/`](./docs/api/) directory. You can also import the Postman collection located at `docs/bpkad_meetings_api.postman_collection.json` to test the API endpoints.

- [Authentication](./docs/api/authentication.md)
- [Meetings](./docs/api/meetings.md)
- [Meeting Locations](./docs/api/meeting_locations.md)
- [Roles & Permissions](./docs/api/roles_permissions.md)
- [And more...](./docs/api_documentation.md)

## Deployment

For deployment instructions using Docker, please refer to the [Docker Deployment Guide](./docker/docker.md).

## Contributing

Thank you for considering contributing! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within this project, please send an e-mail to [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
