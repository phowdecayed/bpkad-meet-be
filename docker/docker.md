# Dockerization Setup for BPKAD Zoom Laravel BE

This document provides an overview of the Docker setup for the BPKAD Zoom Laravel BE application.

## Table of Contents

- [Dockerization Setup for BPKAD Zoom Laravel BE](#dockerization-setup-for-bpkad-zoom-laravel-be)
  - [Table of Contents](#table-of-contents)
  - [Overview](#overview)
  - [Services](#services)
    - [app](#app)
    - [nginx](#nginx)
    - [db](#db)
    - [redis](#redis)
  - [Getting Started](#getting-started)
  - [Database Configuration](#database-configuration)

## Overview

The Docker setup uses Docker Compose to orchestrate a multi-container environment for the application. The environment consists of four services: `app`, `nginx`, `db`, and `redis`.

## Services

### app

-   **Dockerfile:** `docker/Dockerfile`
-   **Image:** `php:8.4-fpm`
-   **Description:** This service builds the PHP application container. It installs the necessary system dependencies and PHP extensions, and runs the `php-fpm` server.

### nginx

-   **Image:** `nginx:alpine`
-   **Description:** This service acts as the web server, routing requests to the `app` service. The configuration is located in `docker/nginx.conf`.

### db

-   **Image:** `postgres:13-alpine`
-   **Description:** This service runs the PostgreSQL database. The database credentials are managed through the `docker/db.env` file.

### redis

-   **Image:** `redis:alpine`
-   **Description:** This service provides a Redis instance for caching and session storage.

## Getting Started

To get the application running, follow these steps:

1.  **Build and Run the Containers:**
    ```bash
    docker compose -f docker/docker-compose.yml up -d --build
    ```

2.  **Install Composer Dependencies:**
    ```bash
    docker compose -f docker/docker-compose.yml exec app composer install
    ```

3.  **Generate Application Key:**
    ```bash
    docker compose -f docker/docker-compose.yml exec app php artisan key:generate
    ```

4.  **Run Database Migrations and Seed:**
    ```bash
    docker compose -f docker/docker-compose.yml exec app php artisan migrate --seed
    ```

After running these commands, the application should be accessible at `http://localhost:8000`.

## Database Configuration

The database credentials are not hard-coded in the `docker-compose.yml` file. Instead, they are stored in `docker/db.env`. This file is used by both the `app` and `db` services to ensure they use the same credentials.

To get started, copy the example environment file:

```bash
cp docker/db.env.example docker/db.env
```

You can then customize the values in `docker/db.env` as needed.
