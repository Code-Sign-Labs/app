# Code Sign Controller

This is an official repository for the Code Sign Controller, a tool designed to help developers manage their licensing and code signing processes more efficiently.
The Code Sign Controller provides a user-friendly interface that allows developers to easily create and assign licenses.

## Features
- **License Management**: Create, assign, and manage licenses for your software projects.
- **Stripe Integration**: Seamlessly integrate with Stripe for payment processing and subscription management.
- **User-Friendly Interface**: Intuitive design that simplifies the code signing and licensing workflow.
- **Activation System**: Platform allows customer to activate their license only on specific number of devices, ensuring compliance with licensing terms.

## Getting Started

To get started with the Code Sign Controller, follow these steps:

```bash
# 1. Copy the repository to your local machine
git clone https://github.com/Code-Sign-Labs/controller.git
cd controller

# 2. Copy the example .env file
cp .env.example .env

# 3. (Optionally) Adjust environment variables
nano .env

# 4. Run the containers
docker compose up -d --build

# 5. Install PHP dependencies
docker compose exec php composer install

# 6. Check if it works
curl http://localhost:8080/
```

### Container commands

```bash
# Enter the PHP container
docker compose exec php bash

# Logs
docker compose logs -f
docker compose logs -f php
docker compose logs -f nginx

# Restart
docker compose restart

# Rebuild (after changes to Dockerfile)
docker compose up -d --build

# List containers
docker compose ps

# Check database status
docker compose exec mariadb mysql -u codesign -pcodesign codesign -e "SHOW TABLES;"
```

### Configure ports

By default, app runs on port 8080, but you can change it by modifying the `HTTP_PORT` variable in the `.env` file.

```bash
# In the .env file
HTTP_PORT=9090
```